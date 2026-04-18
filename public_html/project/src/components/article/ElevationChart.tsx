import { useEffect, useRef, useCallback, useState } from 'react';
import { elevData } from '../../data/routeData';

function dist2d(a: [number, number, number], b: [number, number, number]) {
  const dx = (b[0] - a[0]) * Math.cos(((a[1] + b[1]) / 2) * (Math.PI / 180)) * 69.0;
  const dy = (b[1] - a[1]) * 69.0;
  return Math.sqrt(dx * dx + dy * dy);
}

const cumDist: number[] = [0];
for (let i = 1; i < elevData.length; i++) {
  cumDist.push(cumDist[i - 1] + dist2d(elevData[i - 1], elevData[i]));
}
const totalDist = cumDist[cumDist.length - 1];

const ELEV_SUMMARY = [
  { val: '2,600', unit: 'ft gain' },
  { val: '2,596', unit: 'ft loss' },
  { val: '811',   unit: 'ft min'  },
  { val: '1,595', unit: 'ft max'  },
];

interface Props {
  scrubMarkerRef: React.MutableRefObject<any>;
}

function getElevAtFraction(fraction: number) {
  const hd = fraction * totalDist;
  let idx = 0;
  for (let i = 0; i < cumDist.length - 1; i++) {
    if (hd >= cumDist[i] && hd <= cumDist[i + 1]) { idx = i; break; }
  }
  const t = cumDist[idx + 1] > cumDist[idx] ? (hd - cumDist[idx]) / (cumDist[idx + 1] - cumDist[idx]) : 0;
  const next = Math.min(idx + 1, elevData.length - 1);
  const elev = elevData[idx][2] + t * (elevData[next][2] - elevData[idx][2]);
  const lat  = elevData[idx][1] + t * (elevData[next][1] - elevData[idx][1]);
  const lon  = elevData[idx][0] + t * (elevData[next][0] - elevData[idx][0]);
  return { hd, elev, lat, lon };
}

export default function ElevationChart({ scrubMarkerRef }: Props) {
  const canvasRef  = useRef<HTMLCanvasElement>(null);
  const tooltipRef = useRef<HTMLDivElement>(null);
  const [scrubPos, setScrubPos] = useState<number | null>(null);
  const [liveText, setLiveText] = useState('');

  const draw = useCallback((hoverFraction?: number) => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const W = canvas.offsetWidth, H = canvas.offsetHeight;
    if (!W || !H) return;
    canvas.width  = W * devicePixelRatio;
    canvas.height = H * devicePixelRatio;
    const ctx = canvas.getContext('2d')!;
    ctx.scale(devicePixelRatio, devicePixelRatio);

    const PAD = { top: 16, right: 20, bottom: 28, left: 52 };
    const w = W - PAD.left - PAD.right;
    const h = H - PAD.top - PAD.bottom;
    const elevs = elevData.map(d => d[2]);
    const minE = Math.min(...elevs) - 60;
    const maxE = Math.max(...elevs) + 80;
    const xS = (d: number) => PAD.left + (d / totalDist) * w;
    const yS = (e: number) => PAD.top + h - ((e - minE) / (maxE - minE)) * h;

    ctx.strokeStyle = '#ddd8d0'; ctx.lineWidth = 1;
    ctx.font = "9px 'DM Mono',monospace"; ctx.fillStyle = '#7a7568'; ctx.textAlign = 'right';
    for (let i = 0; i <= 4; i++) {
      const e = minE + (maxE - minE) * (i / 4), y = yS(e);
      ctx.beginPath(); ctx.moveTo(PAD.left, y); ctx.lineTo(PAD.left + w, y); ctx.stroke();
      ctx.fillText(Math.round(e) + ' ft', PAD.left - 6, y + 3);
    }
    ctx.textAlign = 'center'; ctx.fillStyle = '#7a7568';
    for (let i = 0; i <= 6; i++) {
      const d = (totalDist / 6) * i;
      ctx.fillText(d.toFixed(1) + ' mi', xS(d), H - PAD.bottom + 14);
    }

    const grad = ctx.createLinearGradient(0, PAD.top, 0, PAD.top + h);
    grad.addColorStop(0, 'rgba(45,74,51,0.2)');
    grad.addColorStop(1, 'rgba(45,74,51,0.02)');

    ctx.beginPath();
    ctx.moveTo(xS(cumDist[0]), yS(elevData[0][2]));
    for (let i = 1; i < elevData.length; i++) {
      const cx = (xS(cumDist[i - 1]) + xS(cumDist[i])) / 2;
      ctx.bezierCurveTo(cx, yS(elevData[i - 1][2]), cx, yS(elevData[i][2]), xS(cumDist[i]), yS(elevData[i][2]));
    }
    ctx.lineTo(xS(totalDist), PAD.top + h);
    ctx.lineTo(xS(0), PAD.top + h);
    ctx.closePath();
    ctx.fillStyle = grad; ctx.fill();

    ctx.beginPath();
    ctx.moveTo(xS(cumDist[0]), yS(elevData[0][2]));
    for (let i = 1; i < elevData.length; i++) {
      const cx = (xS(cumDist[i - 1]) + xS(cumDist[i])) / 2;
      ctx.bezierCurveTo(cx, yS(elevData[i - 1][2]), cx, yS(elevData[i][2]), xS(cumDist[i]), yS(elevData[i][2]));
    }
    ctx.strokeStyle = '#2d4a33'; ctx.lineWidth = 2.5; ctx.lineJoin = 'round'; ctx.stroke();

    ctx.font = "9px 'DM Mono',monospace"; ctx.fillStyle = '#5c574c'; ctx.textAlign = 'center';
    [{ idx: 0, text: 'Start' }, { idx: 42, text: 'High Pt' }, { idx: 84, text: 'Finish' }].forEach(l => {
      if (l.idx >= elevData.length) return;
      const x = xS(cumDist[l.idx]), y = yS(elevData[l.idx][2]);
      ctx.strokeStyle = '#c0b8b0'; ctx.lineWidth = 1;
      ctx.beginPath(); ctx.moveTo(x, y - 2); ctx.lineTo(x, y - 8); ctx.stroke();
      ctx.fillText(l.text, x, y - 12);
    });

    if (hoverFraction !== undefined) {
      const sx = PAD.left + hoverFraction * w;
      const { elev } = getElevAtFraction(hoverFraction);
      const sy = yS(elev);

      ctx.beginPath(); ctx.moveTo(sx, PAD.top); ctx.lineTo(sx, PAD.top + h);
      ctx.strokeStyle = 'rgba(45,74,51,0.45)'; ctx.lineWidth = 1;
      ctx.setLineDash([3, 3]); ctx.stroke(); ctx.setLineDash([]);

      ctx.beginPath(); ctx.arc(sx, sy, 4, 0, Math.PI * 2);
      ctx.fillStyle = '#2d4a33'; ctx.fill();
      ctx.strokeStyle = '#fff'; ctx.lineWidth = 2; ctx.stroke();
    }
  }, []);

  useEffect(() => {
    draw();
    const onResize = () => draw();
    window.addEventListener('resize', onResize);
    return () => window.removeEventListener('resize', onResize);
  }, [draw]);

  const handleScrub = useCallback((fraction: number, clientX: number, clientY: number, canvasRect: DOMRect) => {
    const { hd, elev, lat, lon } = getElevAtFraction(fraction);
    const tooltip = tooltipRef.current;
    if (tooltip) {
      tooltip.style.opacity = '1';
      tooltip.style.left = (clientX - canvasRect.left + 12) + 'px';
      tooltip.style.top  = (clientY - canvasRect.top  - 36) + 'px';
      tooltip.textContent = hd.toFixed(1) + ' mi  ·  ' + Math.round(elev) + ' ft';
    }
    if (scrubMarkerRef.current) scrubMarkerRef.current.setLatLng([lat, lon]);
    setScrubPos(fraction);
    setLiveText(`${hd.toFixed(1)} miles, elevation ${Math.round(elev)} feet`);
    draw(fraction);
  }, [draw, scrubMarkerRef]);

  const handleMouseMove = (e: React.MouseEvent<HTMLCanvasElement>) => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const rect = canvas.getBoundingClientRect();
    const PAD_L = 52, PAD_R = 20;
    const fraction = Math.max(0, Math.min(1, (e.clientX - rect.left - PAD_L) / (canvas.offsetWidth - PAD_L - PAD_R)));
    handleScrub(fraction, e.clientX, e.clientY, rect);
  };

  const handleMouseLeave = () => {
    const tooltip = tooltipRef.current;
    if (tooltip) tooltip.style.opacity = '0';
    if (scrubMarkerRef.current) scrubMarkerRef.current.setLatLng([elevData[0][1], elevData[0][0]]);
    setScrubPos(null);
    setLiveText('');
    draw();
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLCanvasElement>) => {
    const step = 0.02;
    let next = scrubPos ?? 0;
    if (e.key === 'ArrowRight' || e.key === 'ArrowUp') {
      e.preventDefault(); next = Math.min(1, next + step);
    } else if (e.key === 'ArrowLeft' || e.key === 'ArrowDown') {
      e.preventDefault(); next = Math.max(0, next - step);
    } else if (e.key === 'Home') {
      e.preventDefault(); next = 0;
    } else if (e.key === 'End') {
      e.preventDefault(); next = 1;
    } else { return; }
    const canvas = canvasRef.current!;
    const rect = canvas.getBoundingClientRect();
    const PAD_L = 52, PAD_R = 20;
    const canvasX = rect.left + PAD_L + next * (canvas.offsetWidth - PAD_L - PAD_R);
    handleScrub(next, canvasX, rect.top + canvas.offsetHeight / 2, rect);
  };

  return (
    <div style={{ background: 'var(--surface)', borderBottom: '1px solid var(--border)', position: 'relative' }}>
      {/* Header row */}
      <div style={{
        display: 'flex', alignItems: 'center', justifyContent: 'space-between',
        flexWrap: 'wrap', gap: 12,
        padding: '14px 24px', borderBottom: '1px solid var(--border)',
        background: 'var(--surface2)',
      }}>
        <span
          id="elev-label"
          style={{ fontFamily: "'DM Mono', monospace", fontSize: 9, letterSpacing: '0.28em', textTransform: 'uppercase', color: 'var(--muted)' }}
        >
          Elevation Profile
        </span>
        <dl style={{ display: 'flex', gap: 'clamp(16px, 3vw, 32px)', margin: 0, flexWrap: 'wrap' }}>
          {ELEV_SUMMARY.map(s => (
            <div key={s.unit} style={{ display: 'flex', alignItems: 'baseline', gap: 5 }}>
              <dt className="sr-only">{s.unit}</dt>
              <dd style={{ fontFamily: "'DM Mono', monospace", fontSize: 12, letterSpacing: '0.05em', color: 'var(--bright)', margin: 0 }}>
                {s.val}
              </dd>
              <span aria-hidden="true" style={{ fontFamily: "'DM Mono', monospace", fontSize: 9, letterSpacing: '0.1em', color: 'var(--muted)' }}>
                {s.unit}
              </span>
            </div>
          ))}
        </dl>
      </div>

      {/* Canvas */}
      <canvas
        id="elevation-chart"
        ref={canvasRef}
        role="img"
        aria-labelledby="elev-label"
        aria-describedby="elev-desc"
        tabIndex={0}
        style={{ display: 'block', width: '100%', height: 140, cursor: 'crosshair' }}
        onMouseMove={handleMouseMove}
        onMouseLeave={handleMouseLeave}
        onKeyDown={handleKeyDown}
        onBlur={handleMouseLeave}
      />

      {/* Static description for screen readers */}
      <p id="elev-desc" className="sr-only">
        Interactive elevation profile chart. The route covers {totalDist.toFixed(1)} miles with 2,600 feet of elevation gain and a high point of 1,595 feet.
        Use arrow keys to scrub through the profile when focused.
        {liveText && ` Current position: ${liveText}.`}
      </p>

      {/* Live region for keyboard scrubbing announcements */}
      <div
        aria-live="polite"
        aria-atomic="true"
        className="sr-only"
      >
        {liveText}
      </div>

      {/* Tooltip */}
      <div
        ref={tooltipRef}
        aria-hidden="true"
        style={{
          position: 'absolute', pointerEvents: 'none',
          background: 'var(--bright)', color: '#fff',
          fontFamily: "'DM Mono', monospace", fontSize: 10, letterSpacing: '0.08em',
          padding: '5px 10px', whiteSpace: 'nowrap',
          opacity: 0, transition: 'opacity 0.15s', zIndex: 10,
        }}
      />
    </div>
  );
}
