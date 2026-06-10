/**
 * elevation-profile.js
 *
 * Canvas elevation chart with hover scrub. Hover position is dispatched via the
 * 'surface-profile-hover' event; map.js owns the single shared "you are here"
 * marker (this file no longer touches Leaflet or the map).
 * Data sources (priority order):
 *   1. window._surfaceTracks[map_id]  — map.js per-track [{ route_type, coords }]
 *   2. 'surface-map-ready' event      — detail.tracks (per-track array)
 *   3. data-geojson-url               — fetch directly (no map present)
 *   4. data-elev                      — inline JSON array (Storybook)
 * Coords format: [lon, lat, elevation_m] — elevation is METERS; cumulative
 * distance is computed in KILOMETERS. Imperial (ft / mi) is display-only.
 * Mode-aware: only walking/hiking/cycling tracks get a profile; a lone track
 * always qualifies. 'surface-track-select' (per map_id) swaps the shown track,
 * hiding the profile when the selected track is ineligible (driving/ferry).
 * Works in Drupal (via Drupal.behaviors) and Storybook (via DOMContentLoaded).
 */
/* jshint esversion: 6 */
/* global Drupal */

(() => {
  function dist2d(a, b) {
    const dx = (b[0] - a[0]) * Math.cos((((a[1] + b[1]) / 2) * Math.PI) / 180) * 111.0;
    const dy = (b[1] - a[1]) * 111.0;
    return Math.sqrt(dx * dx + dy * dy); // kilometers
  }

  // Stored data is metric (meters, km). Imperial is a display-only conversion.
  const M_TO_FT = 3.28084;
  const KM_TO_MI = 0.621371;
  const UNITS = {
    metric:   { elev: 1,       elevLabel: 'm',  dist: 1,       distLabel: 'km' },
    imperial: { elev: M_TO_FT, elevLabel: 'ft', dist: KM_TO_MI, distLabel: 'mi' },
  };

  function safeLocalGet(key) {
    try { return window.localStorage ? window.localStorage.getItem(key) : null; }
    catch (e) { return null; }
  }

  function defaultUnit() {
    const stored = safeLocalGet('elevationUnit');
    if (stored === 'imperial' || stored === 'metric') return stored;
    const siteDefault = window.drupalSettings &&
      window.drupalSettings.trailMapper &&
      window.drupalSettings.trailMapper.elevationUnit;
    if (siteDefault === 'imperial' || siteDefault === 'metric') return siteDefault;
    const loc = (navigator.language || 'en-US');
    const region = (loc.split('-')[1] || '').toUpperCase();
    return ['US', 'LR', 'MM'].includes(region) ? 'imperial' : 'metric';
  }

  let currentUnit = defaultUnit(); // module-level, shared by all profiles

  /**
   * Returns true if elevData contains at least one coordinate with a
   * non-zero, finite Z (elevation) value. Driving and destination routes
   * exported from Google My Maps have Z=0 throughout — skip the profile.
   */
  function hasElevation(elevData) {
    if (!elevData || !elevData.length) return false;
    return elevData.some(function(c) {
      return c.length >= 3 && isFinite(c[2]) && c[2] !== 0;
    });
  }

  // Route types that get an elevation profile. Driving / ferry / etc. do not.
  const ELEVATION_MODES = new Set(['walking', 'hiking', 'cycling']);

  function isEligibleMode(routeType) {
    return ELEVATION_MODES.has(routeType);
  }

  // First eligible track in a [{ route_type, coords }] array. A lone track is
  // always eligible (a single-track article may carry no route_type); in a
  // multi-track array only walking/hiking/cycling qualify.
  function pickEligibleTrack(tracks) {
    if (!Array.isArray(tracks) || !tracks.length) return null;
    if (tracks.length === 1) return tracks[0];
    for (let i = 0; i < tracks.length; i++) {
      const t = tracks[i];
      if (t && isEligibleMode(t.route_type)) return t;
    }
    return null;
  }

  function hideProfile(el) {
    el.classList.add('elevation-profile--hidden');
  }

  function showProfile(el) {
    el.classList.remove('elevation-profile--hidden');
  }

  function renderProfile(el, elevData) {
    if (!hasElevation(elevData)) {
      hideProfile(el);
      return;
    }
    // Un-hide in case a previous (ineligible) selection hid the profile.
    showProfile(el);

    const canvas = el.querySelector('.elevation-profile__canvas');
    const tooltip = el.querySelector('.elevation-profile__tooltip');
    if (!canvas || !tooltip) return;

    // Build cumulative distances
    const cumDist = [0];
    for (let i = 1; i < elevData.length; i++) {
      cumDist.push(cumDist[i - 1] + dist2d(elevData[i - 1], elevData[i]));
    }
    const totalDist = cumDist[cumDist.length - 1];

    // The "you are here" marker is owned by map.js as a single shared marker —
    // the profile only dispatches hover position via 'surface-profile-hover'.

    function drawChart(hoverFraction) {
      const W = canvas.offsetWidth;
      const H = canvas.offsetHeight;
      const dpr = window.devicePixelRatio || 1;
      canvas.width = W * dpr;
      canvas.height = H * dpr;
      const ctx = canvas.getContext('2d');
      ctx.scale(dpr, dpr);

      const PAD = { top: 16, right: 20, bottom: 28, left: 52 };
      const w = W - PAD.left - PAD.right;
      const h = H - PAD.top - PAD.bottom;

      const elevs = elevData.map((d) => d[2]);
      const minE = Math.min.apply(null, elevs) - 60;
      const maxE = Math.max.apply(null, elevs) + 80;

      // Geometry stays native (km on x, meters on y); only label text converts.
      const U = UNITS[currentUnit];

      function xS(d) {
        return PAD.left + (d / totalDist) * w;
      }
      function yS(e) {
        return PAD.top + h - ((e - minE) / (maxE - minE)) * h;
      }

      // Grid lines + y labels
      ctx.strokeStyle = '#e8e4dd';
      ctx.lineWidth = 1;
      ctx.font = "9px 'DM Mono', monospace";
      ctx.fillStyle = '#a09890';
      ctx.textAlign = 'right';
      for (let yi = 0; yi <= 4; yi++) {
        const e = minE + (maxE - minE) * (yi / 4);
        const y = yS(e);
        ctx.beginPath();
        ctx.moveTo(PAD.left, y);
        ctx.lineTo(PAD.left + w, y);
        ctx.stroke();
        ctx.fillText(Math.round(e * U.elev) + ' ' + U.elevLabel, PAD.left - 6, y + 3);
      }

      // X distance labels
      ctx.textAlign = 'center';
      for (let xi = 0; xi <= 6; xi++) {
        const xd = (totalDist / 6) * xi;
        ctx.fillText((xd * U.dist).toFixed(1) + ' ' + U.distLabel, xS(xd), H - PAD.bottom + 14);
      }

      // Gradient fill
      const grad = ctx.createLinearGradient(0, PAD.top, 0, PAD.top + h);
      grad.addColorStop(0, 'rgba(58,90,64,0.22)');
      grad.addColorStop(1, 'rgba(58,90,64,0.02)');
      ctx.beginPath();
      ctx.moveTo(xS(cumDist[0]), yS(elevData[0][2]));
      for (let pi = 1; pi < elevData.length; pi++) {
        const cx = (xS(cumDist[pi - 1]) + xS(cumDist[pi])) / 2;
        ctx.bezierCurveTo(
          cx,
          yS(elevData[pi - 1][2]),
          cx,
          yS(elevData[pi][2]),
          xS(cumDist[pi]),
          yS(elevData[pi][2])
        );
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
        ctx.bezierCurveTo(
          lcx,
          yS(elevData[li - 1][2]),
          lcx,
          yS(elevData[li][2]),
          xS(cumDist[li]),
          yS(elevData[li][2])
        );
      }
      ctx.strokeStyle = '#3a5a40';
      ctx.lineWidth = 2.5;
      ctx.lineJoin = 'round';
      ctx.stroke();

      // Hover scrub line + dot
      if (hoverFraction !== undefined) {
        const sx = PAD.left + hoverFraction * w;
        const hd = hoverFraction * totalDist;
        let idx = 0;
        for (let si = 0; si < cumDist.length - 1; si++) {
          if (hd >= cumDist[si] && hd <= cumDist[si + 1]) {
            idx = si;
            break;
          }
        }
        const t =
          cumDist[idx + 1] > cumDist[idx]
            ? (hd - cumDist[idx]) / (cumDist[idx + 1] - cumDist[idx])
            : 0;
        const next = Math.min(idx + 1, elevData.length - 1);
        const elev = elevData[idx][2] + t * (elevData[next][2] - elevData[idx][2]);
        const sy = yS(elev);

        ctx.beginPath();
        ctx.moveTo(sx, PAD.top);
        ctx.lineTo(sx, PAD.top + h);
        ctx.strokeStyle = 'rgba(58,90,64,0.45)';
        ctx.lineWidth = 1;
        ctx.setLineDash([3, 3]);
        ctx.stroke();
        ctx.setLineDash([]);

        ctx.beginPath();
        ctx.arc(sx, sy, 4, 0, Math.PI * 2);
        ctx.fillStyle = '#3a5a40';
        ctx.fill();
        ctx.strokeStyle = '#fff';
        ctx.lineWidth = 2;
        ctx.stroke();
      }
    }

    drawChart();
    // Expose a redraw hook so a unit change can re-render this profile at base
    // state (the user is not mid-hover when toggling the nav).
    el._elevRedraw = () => drawChart();
    window.addEventListener('resize', () => {
      drawChart();
    });

    canvas.addEventListener('mousemove', (e) => {
      const rect = canvas.getBoundingClientRect();
      const PAD_L = 52,
        PAD_R = 20;
      const frac = Math.max(
        0,
        Math.min(1, (e.clientX - rect.left - PAD_L) / (canvas.offsetWidth - PAD_L - PAD_R))
      );
      const hd = frac * totalDist;
      let idx = 0;
      for (let i = 0; i < cumDist.length - 1; i++) {
        if (hd >= cumDist[i] && hd <= cumDist[i + 1]) {
          idx = i;
          break;
        }
      }
      const t =
        cumDist[idx + 1] > cumDist[idx]
          ? (hd - cumDist[idx]) / (cumDist[idx + 1] - cumDist[idx])
          : 0;
      const next = Math.min(idx + 1, elevData.length - 1);
      const elev = elevData[idx][2] + t * (elevData[next][2] - elevData[idx][2]);
      const lat = elevData[idx][1] + t * (elevData[next][1] - elevData[idx][1]);
      const lon = elevData[idx][0] + t * (elevData[next][0] - elevData[idx][0]);

      tooltip.style.opacity = '1';
      tooltip.style.left = e.clientX - rect.left + 12 + 'px';
      tooltip.style.top = e.clientY - rect.top - 36 + 'px';
      const U = UNITS[currentUnit];
      tooltip.textContent =
        (hd * U.dist).toFixed(1) + ' ' + U.distLabel + '  ·  ' +
        Math.round(elev * U.elev) + ' ' + U.elevLabel;

      if (isFinite(lat) && isFinite(lon)) {
        window.dispatchEvent(new CustomEvent('surface-profile-hover', {
          detail: { map_id: el.dataset.mapId, lat, lon },
        }));
      }
      drawChart(frac);
    });

    canvas.addEventListener('mouseleave', () => {
      tooltip.style.opacity = '0';
      window.dispatchEvent(new CustomEvent('surface-profile-hover', {
        detail: { map_id: el.dataset.mapId, clear: true },
      }));
      drawChart();
    });
  }

  function initProfile(el) {
    if (el._elevProfileInit) return;
    el._elevProfileInit = true;

    const mapId = el.dataset.mapId;

    // Render the first eligible track from a [{ route_type, coords }] array,
    // or hide the profile when none qualifies.
    const renderFromTracks = (tracks) => {
      const track = pickEligibleTrack(tracks);
      if (track && track.coords && track.coords.length) {
        renderProfile(el, track.coords);
      } else {
        hideProfile(el);
      }
    };

    // 3 & 4. Fallback: fetch geojson-url or parse inline elev.
    // Handles Storybook (data-elev) and elevation-profile present without a map.
    function tryFallback() {
      const geojsonUrl = el.dataset.geojsonUrl;
      if (geojsonUrl) {
        fetch(geojsonUrl)
          .then((r) => {
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            return r.json();
          })
          .then((geojson) => renderProfile(el, geojson.features[0].geometry.coordinates))
          .catch((err) => console.warn('elevation-profile: fetch failed', geojsonUrl, err));
        return;
      }

      const raw = el.dataset.elev;
      if (!raw) return;
      let elevData;
      try {
        elevData = JSON.parse(raw);
      } catch (e) {
        return;
      }
      renderProfile(el, elevData);
    }

    if (mapId) {
      // 1. map.js already built per-track data — reuse synchronously.
      if (window._surfaceTracks && window._surfaceTracks[mapId]) {
        renderFromTracks(window._surfaceTracks[mapId]);
      } else {
        // 2. Wait for map.js to finish; fall back if it reports no tracks.
        const onReady = (e) => {
          if (e.detail.map_id !== mapId) return;
          window.removeEventListener('surface-map-ready', onReady);
          if (Array.isArray(e.detail.tracks) && e.detail.tracks.length) {
            renderFromTracks(e.detail.tracks);
          } else {
            tryFallback();
          }
        };
        window.addEventListener('surface-map-ready', onReady);
      }

      // Track selection: clicking a track shows its profile when the route_type
      // is eligible, or hides the profile entirely (driving/ferry/etc.).
      window.addEventListener('surface-track-select', (e) => {
        if (e.detail.map_id !== mapId) return;
        if (isEligibleMode(e.detail.route_type) && e.detail.coords && e.detail.coords.length) {
          renderProfile(el, e.detail.coords);
        } else {
          hideProfile(el);
        }
      });
    } else {
      // No map / no _surfaceTracks — standalone Storybook (data-elev) path.
      tryFallback();
    }
  }

  function initAll(context) {
    const root = context || document;
    const els = root.querySelectorAll('.elevation-profile');
    for (let i = 0; i < els.length; i++) {
      initProfile(els[i]);
    }
  }

  // Live unit toggle (dispatched by the nav). Update the shared unit and redraw
  // every rendered profile with converted labels/values.
  window.addEventListener('surface-units-change', (e) => {
    const u = e.detail && e.detail.unit;
    if (u !== 'imperial' && u !== 'metric') return;
    currentUnit = u;
    document.querySelectorAll('.elevation-profile').forEach((el) => {
      if (typeof el._elevRedraw === 'function') el._elevRedraw();
    });
  });

  // Drupal
  if (typeof Drupal !== 'undefined' && typeof Drupal.behaviors !== 'undefined') {
    Drupal.behaviors.surfaceElevationProfile = {
      attach: (context) => {
        initAll(context);
      },
    };
  }

  // Storybook / standalone
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      initAll();
    });
  } else {
    setTimeout(() => {
      initAll();
    }, 0);
  }
})();
