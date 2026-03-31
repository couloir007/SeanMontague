/**
 * surface-elevation-profile.js
 *
 * Canvas elevation chart with hover scrub + map marker sync.
 * Reads elevation data from data-elev attribute as JSON array of [lon, lat, ft].
 * Works in Drupal (via Drupal.behaviors) and Storybook (via DOMContentLoaded).
 */
/* jshint esversion: 6, browser: true */
/* global L, Drupal */

(function () {
  'use strict';

  function dist2d(a, b) {
    const dx = (b[0] - a[0]) * Math.cos((a[1] + b[1]) / 2 * Math.PI / 180) * 69.0;
    const dy = (b[1] - a[1]) * 69.0;
    return Math.sqrt(dx * dx + dy * dy);
  }

  function initProfile(el) {
    if (el._elevProfileInit) return;
    el._elevProfileInit = true;

    const raw = el.dataset.elev;
    if (!raw) return;

    let elevData;
    try { elevData = JSON.parse(raw); } catch (e) { return; }

    const canvas  = el.querySelector('.surface-elevation-profile__canvas');
    const tooltip = el.querySelector('.surface-elevation-profile__tooltip');
    if (!canvas || !tooltip) return;

    // Build cumulative distances
    const cumDist = [0];
    for (let i = 1; i < elevData.length; i++) {
      cumDist.push(cumDist[i - 1] + dist2d(elevData[i - 1], elevData[i]));
    }
    const totalDist = cumDist[cumDist.length - 1];

    // Scrub marker on map if Leaflet map exists
    let scrubMarker = null;

    function tryConnectMap() {
      if (typeof L === 'undefined' || scrubMarker) return;

      // Try to find an associated map by id, or fall back to global instance
      const mapId   = el.dataset.mapId || 'featured-map';
      const mapInst = (window._surfaceMaps && window._surfaceMaps[mapId]) ||
                    window._surfaceMapInstance;

      if (mapInst) {
        scrubMarker = L.circleMarker([elevData[0][1], elevData[0][0]], {
          radius: 7, color: '#fff', weight: 2,
          fillColor: '#3a5a40', fillOpacity: 1
        }).addTo(mapInst);
      }
    }

    tryConnectMap();
    // If not found yet, maybe the map is still initializing
    if (!scrubMarker) {
      setTimeout(tryConnectMap, 500);
      setTimeout(tryConnectMap, 2000);
    }

    function drawChart(hoverFraction) {
      const W = canvas.offsetWidth;
      const H = canvas.offsetHeight;
      const dpr = window.devicePixelRatio || 1;
      canvas.width  = W * dpr;
      canvas.height = H * dpr;
      const ctx = canvas.getContext('2d');
      ctx.scale(dpr, dpr);

      const PAD = { top: 16, right: 20, bottom: 28, left: 52 };
      const w = W - PAD.left - PAD.right;
      const h = H - PAD.top  - PAD.bottom;

      const elevs = elevData.map(function (d) { return d[2]; });
      const minE  = Math.min.apply(null, elevs) - 60;
      const maxE  = Math.max.apply(null, elevs) + 80;

      function xS(d) { return PAD.left + (d / totalDist) * w; }
      function yS(e) { return PAD.top  + h - ((e - minE) / (maxE - minE)) * h; }

      // Grid lines + y labels
      ctx.strokeStyle = '#e8e4dd';
      ctx.lineWidth   = 1;
      ctx.font        = "9px 'DM Mono', monospace";
      ctx.fillStyle   = '#a09890';
      ctx.textAlign   = 'right';
      for (let yi = 0; yi <= 4; yi++) {
        const e = minE + (maxE - minE) * (yi / 4);
        const y = yS(e);
        ctx.beginPath(); ctx.moveTo(PAD.left, y); ctx.lineTo(PAD.left + w, y); ctx.stroke();
        ctx.fillText(Math.round(e) + ' ft', PAD.left - 6, y + 3);
      }

      // X distance labels
      ctx.textAlign = 'center';
      for (let xi = 0; xi <= 6; xi++) {
        const xd = (totalDist / 6) * xi;
        ctx.fillText(xd.toFixed(1) + ' mi', xS(xd), H - PAD.bottom + 14);
      }

      // Gradient fill
      const grad = ctx.createLinearGradient(0, PAD.top, 0, PAD.top + h);
      grad.addColorStop(0, 'rgba(58,90,64,0.22)');
      grad.addColorStop(1, 'rgba(58,90,64,0.02)');
      ctx.beginPath();
      ctx.moveTo(xS(cumDist[0]), yS(elevData[0][2]));
      for (let pi = 1; pi < elevData.length; pi++) {
        const cx = (xS(cumDist[pi - 1]) + xS(cumDist[pi])) / 2;
        ctx.bezierCurveTo(cx, yS(elevData[pi-1][2]), cx, yS(elevData[pi][2]), xS(cumDist[pi]), yS(elevData[pi][2]));
      }
      ctx.lineTo(xS(totalDist), PAD.top + h);
      ctx.lineTo(xS(0), PAD.top + h);
      ctx.closePath();
      ctx.fillStyle = grad;
      ctx.fill();

      // Profile line
      ctx.beginPath();
      ctx.moveTo(xS(cumDist[0]), yS(elevData[0][2]));
      for (let li = 1; li < elevData.length; li++) {
        const lcx = (xS(cumDist[li - 1]) + xS(cumDist[li])) / 2;
        ctx.bezierCurveTo(lcx, yS(elevData[li-1][2]), lcx, yS(elevData[li][2]), xS(cumDist[li]), yS(elevData[li][2]));
      }
      ctx.strokeStyle = '#3a5a40';
      ctx.lineWidth   = 2.5;
      ctx.lineJoin    = 'round';
      ctx.stroke();

      // Hover scrub line + dot
      if (hoverFraction !== undefined) {
        const sx  = PAD.left + hoverFraction * w;
        const hd  = hoverFraction * totalDist;
        let idx = 0;
        for (let si = 0; si < cumDist.length - 1; si++) {
          if (hd >= cumDist[si] && hd <= cumDist[si + 1]) { idx = si; break; }
        }
        const t    = cumDist[idx+1] > cumDist[idx] ? (hd - cumDist[idx]) / (cumDist[idx+1] - cumDist[idx]) : 0;
        const next = Math.min(idx + 1, elevData.length - 1);
        const elev = elevData[idx][2] + t * (elevData[next][2] - elevData[idx][2]);
        const sy   = yS(elev);

        ctx.beginPath(); ctx.moveTo(sx, PAD.top); ctx.lineTo(sx, PAD.top + h);
        ctx.strokeStyle = 'rgba(58,90,64,0.45)';
        ctx.lineWidth   = 1;
        ctx.setLineDash([3, 3]);
        ctx.stroke();
        ctx.setLineDash([]);

        ctx.beginPath(); ctx.arc(sx, sy, 4, 0, Math.PI * 2);
        ctx.fillStyle   = '#3a5a40'; ctx.fill();
        ctx.strokeStyle = '#fff'; ctx.lineWidth = 2; ctx.stroke();
      }
    }

    drawChart();
    window.addEventListener('resize', function () { drawChart(); });

    canvas.addEventListener('mousemove', function (e) {
      const rect   = canvas.getBoundingClientRect();
      const PAD_L  = 52, PAD_R = 20;
      const frac   = Math.max(0, Math.min(1, (e.clientX - rect.left - PAD_L) / (canvas.offsetWidth - PAD_L - PAD_R)));
      const hd     = frac * totalDist;
      let idx    = 0;
      for (let i = 0; i < cumDist.length - 1; i++) {
        if (hd >= cumDist[i] && hd <= cumDist[i + 1]) { idx = i; break; }
      }
      const t    = cumDist[idx+1] > cumDist[idx] ? (hd - cumDist[idx]) / (cumDist[idx+1] - cumDist[idx]) : 0;
      const next = Math.min(idx + 1, elevData.length - 1);
      const elev = elevData[idx][2] + t * (elevData[next][2] - elevData[idx][2]);
      const lat  = elevData[idx][1] + t * (elevData[next][1] - elevData[idx][1]);
      const lon  = elevData[idx][0] + t * (elevData[next][0] - elevData[idx][0]);

      tooltip.style.opacity = '1';
      tooltip.style.left    = (e.clientX - rect.left + 12) + 'px';
      tooltip.style.top     = (e.clientY - rect.top  - 36) + 'px';
      tooltip.textContent   = hd.toFixed(1) + ' mi  ·  ' + Math.round(elev) + ' ft';

      tryConnectMap();
      if (scrubMarker) scrubMarker.setLatLng([lat, lon]);
      drawChart(frac);
    });

    canvas.addEventListener('mouseleave', function () {
      tooltip.style.opacity = '0';
      if (scrubMarker) scrubMarker.setLatLng([elevData[0][1], elevData[0][0]]);
      drawChart();
    });
  }

  function initAll(context) {
    const root = context || document;
    const els  = root.querySelectorAll('.surface-elevation-profile');
    for (let i = 0; i < els.length; i++) { initProfile(els[i]); }
  }

  // Drupal
  if (typeof Drupal !== 'undefined' && typeof Drupal.behaviors !== 'undefined') {
    Drupal.behaviors.surfaceElevationProfile = {
      attach: function (context) { initAll(context); }
    };
  }

  // Storybook / standalone
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { initAll(); });
  } else {
    setTimeout(function () { initAll(); }, 0);
  }

}());
