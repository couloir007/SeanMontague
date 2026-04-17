/**
 * map.js
 *
 * Initializes Leaflet maps for the Surface theme.
 * Runs via Drupal.behaviors in Drupal, DOMContentLoaded/setTimeout in Storybook.
 *
 * Configure via data-attributes on the .map element:
 *   data-map-center="44.593,-71.918"     fallback center (used when no geojson)
 *   data-map-zoom="12"                   fallback zoom (used when no geojson)
 *   data-map-interactive="false"         non-interactive bg map (hero)
 *   data-map-geojson="/path/to.geojson"  fetch + draw track, fitBounds all data
 *   data-map-markers='[{...}]'           point markers [{lat,lon,color,label,entity_type,entity_id}]
 *   data-map-lines='[{...}]'             inline polylines (Storybook)
 *   data-map-tiles="usgs-topo"           tile set key (default: usgs-topo)
 *
 * Tile config priority:
 *   1. drupalSettings.trailMapper (set by hook_page_attachments from admin config)
 *   2. data-map-tiles attribute
 *   3. usgs-topo default
 *
 * After init:
 *   window._surfaceMaps[map_id]    — Leaflet map instance
 *   window._surfaceTracks[map_id]  — raw GeoJSON coordinates [lon, lat, ele_ft]
 *   'surface-map-ready' event      — dispatched with { map_id, map, coords }
 */

/* jshint esversion: 6 */
/* global L, Drupal */

(() => {
  "use strict";

  const TILE_SETS = {
    'usgs-topo': {
      url: 'https://basemap.nationalmap.gov/arcgis/rest/services/USGSTopo/MapServer/tile/{z}/{y}/{x}',
      attr: 'Tiles &copy; <a href="https://usgs.gov">USGS</a> The National Map',
      maxZoom: 16,
    },
    'usgs-imagery': {
      url: 'https://basemap.nationalmap.gov/arcgis/rest/services/USGSImageryOnly/MapServer/tile/{z}/{y}/{x}',
      attr: 'Tiles &copy; <a href="https://usgs.gov">USGS</a> The National Map',
      maxZoom: 16,
    },
    'usgs-imagery-topo': {
      url: 'https://basemap.nationalmap.gov/arcgis/rest/services/USGSImageryTopo/MapServer/tile/{z}/{y}/{x}',
      attr: 'Tiles &copy; <a href="https://usgs.gov">USGS</a> The National Map',
      maxZoom: 16,
    },
    'usgs-shaded': {
      url: 'https://basemap.nationalmap.gov/arcgis/rest/services/USGSShadedReliefOnly/MapServer/tile/{z}/{y}/{x}',
      attr: 'Tiles &copy; <a href="https://usgs.gov">USGS</a> The National Map',
      maxZoom: 16,
    },
    'osm': {
      url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
      attr: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 19,
    },
  };

  const resolveTile = (el) => {
    // Priority 1 — drupalSettings from hook_page_attachments
    const ds = window.drupalSettings?.trailMapper;
    if (ds?.tileUrl) {
      return { url: ds.tileUrl, attr: ds.tileAttribution ?? '', maxZoom: ds.tileMaxZoom ?? 16 };
    }
    // Priority 2 — data-map-tiles per-element override
    const key = el.dataset.mapTiles ?? 'usgs-topo';
    return TILE_SETS[key] ?? TILE_SETS['usgs-topo'];
  };

  const pinIcon = (color) => L.divIcon({
    html: `<div style="width:10px;height:10px;border-radius:50%;background:${color};border:2px solid #fff;box-shadow:0 1px 6px rgba(0,0,0,0.3)"></div>`,
    iconSize: [10, 10],
    iconAnchor: [5, 5],
    className: '',
  });

  const parseJSON = (str, fallback) => {
    if (!str) return fallback;
    try { return JSON.parse(str); }
    catch { return fallback; }
  };

  const flattenCoords = (geojson) => {
    // Extract all [lon, lat, ele] points from any LineString or MultiLineString.
    // Handles 4-value coords [lon, lat, ele, time] by ignoring index 3+.
    const pts = [];
    geojson.features?.forEach(({ geometry }) => {
      if (!geometry) return;
      if (geometry.type === 'LineString') {
        geometry.coordinates.forEach((c) => pts.push(c));
      } else if (geometry.type === 'MultiLineString') {
        geometry.coordinates.forEach((line) => line.forEach((c) => pts.push(c)));
      }
      // Skip Point features — those are waypoints, not track coords
    });
    return pts;
  };

  const computeTrackStats = (coords) => {
    // coords = [[lon, lat, ele_ft, ...], ...]
    if (!coords || coords.length < 2) return null;

    let distance = 0;
    let gain = 0;
    let loss = 0;
    let minElev = Infinity;
    let maxElev = -Infinity;

    for (let i = 0; i < coords.length; i++) {
      const ele = coords[i][2];
      if (ele == null) continue;
      // Z values from trail_mapper are already in feet.
      // Raw uploaded GeoJSON may be in meters — trail_mapper normalizes on save.
      if (ele < minElev) minElev = ele;
      if (ele > maxElev) maxElev = ele;

      if (i > 0) {
        // Haversine distance in miles
        const [lon1, lat1] = coords[i - 1];
        const [lon2, lat2] = coords[i];
        const R = 3958.8; // Earth radius in miles
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a =
          Math.sin(dLat / 2) ** 2 +
          Math.cos(lat1 * Math.PI / 180) *
          Math.cos(lat2 * Math.PI / 180) *
          Math.sin(dLon / 2) ** 2;
        distance += R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

        // Elevation change
        const prevEle = coords[i - 1][2];
        const diff = ele - prevEle;
        if (diff > 0) gain += diff;
        else loss += Math.abs(diff);
      }
    }

    return {
      distance: Math.round(distance * 10) / 10,
      elev_gain: Math.round(gain),
      elev_loss: Math.round(loss),
      elev_min: minElev === Infinity ? null : Math.round(minElev),
      elev_max: maxElev === -Infinity ? null : Math.round(maxElev),
    };
  };

  const initLeaflet = (el, geojson, { lat, lon, zoom, interactive, markers, lines, tile, mapId }) => {
    // Guard against double-init (fetch error catch calling initLeaflet twice)
    if (el._leafletMapInstance) return;
    el._leafletMapInstance = true;

    const initLat = isFinite(lat) ? lat : 44.593;
    const initLon = isFinite(lon) ? lon : -71.918;
    const map = L.map(el, {
      center: [initLat, initLon],
      zoom,
      zoomControl: interactive,
      scrollWheelZoom: false,
      dragging: interactive,
      doubleClickZoom: interactive,
      keyboard: interactive,
    });

    L.tileLayer(tile.url, { maxZoom: tile.maxZoom, attribution: tile.attr }).addTo(map);

    // ── Inline polylines (Storybook fixture data) ──────────────────────────
    lines.forEach((line) => {
      L.geoJSON(
        { type: 'Feature', geometry: { type: 'LineString', coordinates: line.coords } },
        { style: { color: line.color ?? '#3a5a40', weight: line.weight ?? 3, opacity: 0.85, dashArray: line.dash ?? null } }
      ).addTo(map);
    });

    // ── Markers ────────────────────────────────────────────────────────────
    const markerLatLngs = [];
    markers.forEach((m) => {
      // Skip markers with invalid coordinates
      if (!isFinite(m.lat) || !isFinite(m.lon) || m.lat == null || m.lon == null) {
        console.warn('map: skipping marker with invalid coords', m);
        return;
      }
      const title = m.label?.replace(/<[^>]*>?/gm, '') ?? '';
      const marker = L.marker([m.lat, m.lon], { icon: pinIcon(m.color ?? '#3a5a40'), title })
        .addTo(map)
        .bindPopup(m.label ?? '');

      markerLatLngs.push([m.lat, m.lon]);

      if (m.entity_type && m.entity_id) {
        marker.on('click', () => {
          window.dispatchEvent(new CustomEvent('surface-map-marker-click', {
            detail: { entity_type: m.entity_type, entity_id: m.entity_id, map_id: mapId, lat: m.lat, lon: m.lon },
          }));
        });
      }
    });

    // ── GeoJSON track ──────────────────────────────────────────────────────
    // Add the layer to the map first — Leaflet computes correct bounds
    // from the rendered geometry, then fitBounds on track + markers.
    let coords = null;
    if (geojson) {
      // Filter to LineString/MultiLineString only — Point features are app
      // waypoints that crash Leaflet's marker renderer.
      const trackOnlyGeojson = {
        type: 'FeatureCollection',
        features: geojson.features.filter(({ geometry }) =>
          geometry?.type === 'LineString' || geometry?.type === 'MultiLineString'
        ),
      };

      const trackLayer = L.geoJSON(trackOnlyGeojson, {
        style: () => ({ color: '#3a5a40', weight: 3, opacity: 0.85 }),
      }).addTo(map);

      const bounds = trackLayer.getBounds();
      markerLatLngs.forEach((ll) => bounds.extend(ll));
      map.fitBounds(bounds, { padding: [32, 32] });

      // Flatten all LineString/MultiLineString coords for elevation profile + stats
      coords = flattenCoords(geojson);
      window._surfaceTracks = window._surfaceTracks ?? {};
      window._surfaceTracks[mapId] = coords;
    } else if (markerLatLngs.length > 1) {
      map.fitBounds(L.latLngBounds(markerLatLngs), { padding: [32, 32] });
    }

    // ── invalidateSize ─────────────────────────────────────────────────────
    requestAnimationFrame(() => map.invalidateSize());
    setTimeout(() => map.invalidateSize(), 150);
    setTimeout(() => map.invalidateSize(), 500);

    // ── Register ───────────────────────────────────────────────────────────
    window._surfaceMaps = window._surfaceMaps ?? {};
    window._surfaceMaps[mapId] = map;
    window._surfaceMapInstance ??= map;

    const stats = coords && coords.length ? computeTrackStats(coords) : null;

    window.dispatchEvent(new CustomEvent('surface-map-ready', {
      detail: { map_id: mapId, map, coords, stats },
    }));

    // ── Ctrl+scroll / touch gesture hints ─────────────────────────────────
    if (!interactive) return;

    let hintEl = null;
    let hintTimer = null;
    let lastHintTime = 0;
    let scrollWheelTimer = null;

    const getHint = () => {
      if (!hintEl) {
        hintEl = document.createElement('div');
        hintEl.setAttribute('aria-hidden', 'true');
        hintEl.style.cssText =
          'position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);' +
          'background:rgba(0,0,0,0.55);color:#fff;padding:8px 14px;border-radius:4px;' +
          'font-size:13px;line-height:1.3;pointer-events:none;white-space:nowrap;' +
          'opacity:0;transition:opacity 0.15s;z-index:1000;';
        el.appendChild(hintEl);
      }
      return hintEl;
    };

    const showHint = (text) => {
      if (Date.now() - lastHintTime < 3000) return;
      const hint = getHint();
      hint.textContent = text;
      hint.style.opacity = '1';
      clearTimeout(hintTimer);
      hintTimer = setTimeout(() => {
        hint.style.opacity = '0';
        lastHintTime = Date.now();
      }, 1500);
    };

    el.addEventListener('wheel', (e) => {
      if (e.ctrlKey) {
        e.preventDefault();
        map.scrollWheelZoom.enable();
        clearTimeout(scrollWheelTimer);
        scrollWheelTimer = setTimeout(() => map.scrollWheelZoom.disable(), 500);
      } else {
        showHint('Hold Ctrl to zoom');
      }
    }, { passive: false });

    if (window.matchMedia('(pointer: coarse)').matches) {
      el.addEventListener('touchstart', (e) => {
        if (e.touches.length === 1) showHint('Use two fingers to move the map');
      }, { passive: true });
    }
  };

  const initMap = async (el) => {
    if (el._surfaceMapInit) return;
    el._surfaceMapInit = true;

    if (typeof L === 'undefined') {
      console.warn('map: Leaflet not loaded');
      return;
    }

    const [latStr, lonStr] = (el.dataset.mapCenter ?? '44.593,-71.918').split(',');
    const lat = parseFloat(latStr);
    const lon = parseFloat(lonStr);
    const zoom = parseInt(el.dataset.mapZoom ?? '12', 10);
    const interactive = el.dataset.mapInteractive !== 'false';
    const markers = parseJSON(el.dataset.mapMarkers, []);
    const lines = parseJSON(el.dataset.mapLines, []);
    const geojsonUrl = el.dataset.mapGeojson ?? null;
    const mapId = el.id ?? 'map';
    const tile = resolveTile(el);

    const opts = { lat, lon, zoom, interactive, markers, lines, tile, mapId };

    if (geojsonUrl) {
      try {
        const r = await fetch(geojsonUrl);
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        const geojson = await r.json();
        initLeaflet(el, geojson, opts);
      } catch (err) {
        console.warn('map: GeoJSON fetch failed', geojsonUrl, err);
        initLeaflet(el, null, opts);
      }
    } else {
      initLeaflet(el, null, opts);
    }
  };

  const initAll = (context) => {
    const root = context ?? document;
    root.querySelectorAll('.map').forEach((el) => initMap(el));
  };

  // Drupal
  if (typeof Drupal !== 'undefined' && Drupal.behaviors) {
    Drupal.behaviors.surfaceMap = {
      attach: (context) => initAll(context),
    };
  }

  // Storybook / standalone
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initAll());
  } else {
    setTimeout(() => initAll(), 0);
  }
})();
