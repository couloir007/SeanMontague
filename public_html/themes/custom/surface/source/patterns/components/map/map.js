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
 *   window._surfaceTracks[map_id]  — array of per-track { route_type, coords,
 *                                    name, stats }, coords being [lon, lat, ele]
 *                                    points; stats are stored meters or null
 *   'surface-map-ready' event      — { map_id, map, tracks, coords } where
 *                                    coords is the first track (backward-compat)
 *   'surface-track-select' event   — { map_id, route_type, coords, name, stats }
 *                                    on track click
 */

/* jshint esversion: 11 */
/* global L, Drupal, console */

(() => {
  "use strict";

  // Route types that have a meaningful elevation profile (mirrors
  // elevation-profile.js). Used to decide whether to hint that tracks are
  // clickable for elevation.
  const ELEVATION_MODES = new Set(['walking', 'hiking', 'cycling']);

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
    'open-topo': {
      url: 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
      attr: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a> contributors, &copy; <a href="https://opentopomap.org">OpenTopoMap</a>',
      maxZoom: 17,
    },
    'esri-topo': {
      url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}',
      attr: '&copy; <a href="https://www.esri.com">Esri</a>',
      maxZoom: 18,
    },
    'esri-nat-geo': {
      url: 'https://server.arcgisonline.com/ArcGIS/rest/services/NatGeo_World_Map/MapServer/tile/{z}/{y}/{x}',
      attribution: '&copy; <a href="https://www.esri.com">Esri</a>',
      maxZoom: 18,
    },
  };

  const resolveTile = (el) => {
    const ds = window.drupalSettings?.trailMapper;
    const serverSets = ds?.tileSets ?? {};

    const lookup = (key) => {
      const t = serverSets[key] ?? TILE_SETS[key];
      if (!t) return null;
      return { url: t.url, attr: t.attribution ?? t.attr ?? '', maxZoom: t.maxZoom ?? 16 };
    };

    // Priority 1 — data-map-tiles per-element (field_map_tiles per-article override)
    const attrKey = el.dataset.mapTiles;
    if (attrKey) {
      const t = lookup(attrKey);
      if (t) return t;
    }

    // Priority 2 — global admin tile key from drupalSettings
    if (ds?.tileKey) {
      const t = lookup(ds.tileKey);
      if (t) return t;
    }
    if (ds?.tileUrl) {
      return { url: ds.tileUrl, attr: ds.tileAttribution ?? '', maxZoom: ds.tileMaxZoom ?? 16 };
    }

    // Priority 3 — usgs-topo default
    return TILE_SETS['usgs-topo'];
  };

  // Marker icon — tinted ring (E) + distinct inner shape (C) per entity type.
  // type: 'poi' (triangle), 'destination' (circle), 'lodging' (square),
  //       'trail' (diamond), 'place' (muted circle). Falls back to legacy color dot.
  const surfaceMarker = (type, color) => {
    const configs = {
      poi: {
        ring: '#3a5a40', fill: 'rgba(58,90,64,0.15)',
        inner: '<polygon points="12,5 19,17 5,17" fill="#3a5a40"/>',
      },
      destination: {
        ring: '#4a7c9e', fill: 'rgba(74,124,158,0.15)',
        inner: '<circle cx="12" cy="12" r="5" fill="#4a7c9e"/>',
      },
      lodging: {
        ring: '#a05a00', fill: 'rgba(160,90,0,0.15)',
        inner: '<rect x="7.5" y="7.5" width="9" height="9" rx="1.5" fill="#a05a00"/>',
      },
      trail: {
        ring: '#7a3410', fill: 'rgba(122,52,16,0.15)',
        inner: '<polygon points="12,5 19,12 12,19 5,12" fill="#7a3410"/>',
      },
      place: {
        ring: '#3a5a40', fill: 'rgba(58,90,64,0.1)',
        inner: '<circle cx="12" cy="12" r="5" fill="#3a5a40" opacity="0.5"/>',
      },
    };
    const c = configs[type];
    if (!c) {
      // Legacy fallback — plain colored dot for markers with no type
      const col = color || '#3a5a40';
      return L.divIcon({
        html: `<div style="width:10px;height:10px;border-radius:50%;background:${col};border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,0.25)"></div>`,
        iconSize: [10, 10],
        iconAnchor: [5, 5],
        className: '',
      });
    }
    const svg = `<svg width="28" height="28" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">`
      + `<circle cx="12" cy="12" r="11" fill="${c.fill}" stroke="${c.ring}" stroke-width="2"/>`
      + c.inner
      + `</svg>`;
    return L.divIcon({
      html: svg,
      className: '',
      iconSize: [28, 28],
      iconAnchor: [14, 14],
      popupAnchor: [0, -16],
    });
  };

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

  const initLeaflet = async (el, geojson, { lat, lon, zoom, interactive, markers, lines, tile, mapId, geojsonUrls, trackStats = [] }) => {
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
      const marker = L.marker([m.lat, m.lon], { icon: surfaceMarker(m.type, m.color), title })
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
    // Per-track data: [{ route_type, coords }, ...] in layer order, so an
    // elevation profile can be shown for a single selected track.
    let trackLayer = null;
    const allTrackLayers = [];
    const tracks = [];
    // Set true when the multi-track path builds at least one elevation-eligible
    // track — gates the "tap a route" hint shown below.
    let showTrackHint = false;
    window._surfaceTracks = window._surfaceTracks ?? {};

    // Dispatch a track selection so listeners (e.g. the elevation profile) can
    // swap to the clicked track.
    const selectTrack = (track) => {
      window.dispatchEvent(new CustomEvent('surface-track-select', {
        detail: { map_id: mapId, route_type: track.route_type, coords: track.coords, name: track.name, stats: track.stats },
      }));
    };

    // Single shared "you are here" marker, driven by the elevation profile's
    // hover events. One marker per map, moved (not recreated) and removed on
    // clear — so it can never leak or appear on two tracks.
    let hoverMarker = null;
    window.addEventListener('surface-profile-hover', (e) => {
      if (e.detail.map_id !== mapId) return;
      if (e.detail.clear || !isFinite(e.detail.lat) || !isFinite(e.detail.lon)) {
        if (hoverMarker) { map.removeLayer(hoverMarker); hoverMarker = null; }
        return;
      }
      const ll = [e.detail.lat, e.detail.lon];
      if (!hoverMarker) {
        hoverMarker = L.circleMarker(ll, {
          radius: 7, color: '#fff', weight: 2,
          fillColor: '#3a5a40', fillOpacity: 1, interactive: false,
        }).addTo(map);
      } else {
        hoverMarker.setLatLng(ll);
      }
    });

    if (geojson) {
      // Filter to LineString/MultiLineString only — Point features are app
      // waypoints that crash Leaflet's marker renderer.
      const trackOnlyGeojson = {
        type: 'FeatureCollection',
        features: geojson.features.filter(({ geometry }) =>
          geometry?.type === 'LineString' || geometry?.type === 'MultiLineString'
        ),
      };

      trackLayer = L.geoJSON(trackOnlyGeojson, {
        style: () => ({ color: '#3a5a40', weight: 3, opacity: 0.85 }),
      }).addTo(map);

      // Single track: flatten its coords; route_type + human name from the first
      // track feature (prefer title, then name).
      const trackName = trackOnlyGeojson.features[0]?.properties?.title
                     ?? trackOnlyGeojson.features[0]?.properties?.name
                     ?? null;
      const track = {
        route_type: trackOnlyGeojson.features[0]?.properties?.route_type ?? null,
        coords: flattenCoords(geojson),
        name: trackName,
        // Stored stats (meters) — informational; never overrides route_type.
        stats: trackStats[0] ?? null,
      };
      tracks.push(track);
      trackLayer.on('click', () => selectTrack(track));
      allTrackLayers.push(trackLayer);
    }

    // ── Multi-track GeoJSON (trip page) ───────────────────────────────────
    if (!geojson && geojsonUrls.length) {
      const results = await Promise.all(
        geojsonUrls.map((url) => fetch(url).then((r) => r.ok ? r.json() : null).catch(() => null))
      );

      // Per-feature styling from properties.route_type. The style map comes from
      // drupalSettings.trailMapper.routeStyles; Storybook has no drupalSettings,
      // so this resolves to {} and every feature uses the forest-green default.
      const routeStyles = window.drupalSettings?.trailMapper?.routeStyles ?? {};
      const styleFeature = (feature) => {
        const rs = routeStyles[feature?.properties?.route_type];
        const style = {
          color: rs?.color ?? '#3a5a40',
          weight: rs?.weight ?? 3,
          opacity: 0.85,
        };
        // Solid by default; only apply a dash pattern when the route type has one.
        if (rs?.dash) {
          style.dashArray = rs.dash;
        }
        return style;
      };

      results.forEach((gj, i) => {
        if (!gj) return;
        const trackOnly = {
          type: 'FeatureCollection',
          features: gj.features.filter(({ geometry }) =>
            geometry?.type === 'LineString' || geometry?.type === 'MultiLineString'
          ),
        };
        const layer = L.geoJSON(trackOnly, {
          style: styleFeature,
        }).addTo(map);
        allTrackLayers.push(layer);

        // Tracks are clickable (for their elevation profile) — show a pointer
        // cursor on the path elements. Markers are untouched.
        layer.eachLayer((p) => { if (p._path) p._path.style.cursor = 'pointer'; });

        // Keep this track's own coords + route_type + human name (from its first
        // feature; prefer title, then name).
        const trackName = trackOnly.features[0]?.properties?.title
                       ?? trackOnly.features[0]?.properties?.name
                       ?? null;
        const track = {
          // File is authoritative for route_type (eligibility + styling); the
          // template's stats.route_type is only informational and may be null.
          route_type: trackOnly.features[0]?.properties?.route_type ?? null,
          coords: flattenCoords(gj),
          name: trackName,
          // Stored stats (meters), index-matched to geojsonUrls / trackStats.
          stats: trackStats[i] ?? null,
        };
        tracks.push(track);
        layer.on('click', () => selectTrack(track));
      });

      // Hint only makes sense when at least one built track is elevation-eligible.
      showTrackHint = tracks.some((t) => ELEVATION_MODES.has(t.route_type));
    }

    if (tracks.length) {
      window._surfaceTracks[mapId] = tracks;
    }

    // ── invalidateSize + fitBounds after layout ────────────────────────────
    const fitToData = () => {
      if (!interactive) return;
      if (allTrackLayers.length) {
        const bounds = L.latLngBounds();
        allTrackLayers.forEach((layer) => bounds.extend(layer.getBounds()));
        markerLatLngs.forEach((ll) => bounds.extend(ll));
        if (bounds.isValid()) map.fitBounds(bounds, { padding: [32, 32] });
      } else if (markerLatLngs.length > 1) {
        map.fitBounds(L.latLngBounds(markerLatLngs), { padding: [32, 32] });
      }
    };

    requestAnimationFrame(() => { map.invalidateSize(); fitToData(); });
    setTimeout(() => { map.invalidateSize(); fitToData(); }, 150);
    setTimeout(() => map.invalidateSize(), 500);

    // ── Register ───────────────────────────────────────────────────────────
    window._surfaceMaps = window._surfaceMaps ?? {};
    window._surfaceMaps[mapId] = map;
    window._surfaceMapInstance ??= map;

    // tracks: full per-track array so listeners that load before any click can
    // pick the initial track. coords kept for backward-compat = first track.
    window.dispatchEvent(new CustomEvent('surface-map-ready', {
      detail: { map_id: mapId, map, coords: tracks[0]?.coords ?? null, tracks },
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

    // One-time hint that tracks are clickable for elevation. Only when the
    // multi-track path produced an eligible track; honours the showHint throttle
    // so it does not fight the zoom/touch hints. Same text on touch devices.
    if (showTrackHint) {
      showHint('Tap a route for its elevation');
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
    const geojsonUrls = parseJSON(el.dataset.mapGeojsonUrls, []);
    // Per-track stored stats (meters), index-matched to geojsonUrls by the node
    // template. Absent in Storybook → []. Informational only — never overrides
    // the file's route_type.
    const trackStats = parseJSON(el.dataset.mapTrackStats, []);
    const mapId = el.id ?? 'map';
    const tile = resolveTile(el);

    const opts = { lat, lon, zoom, interactive, markers, lines, tile, mapId, geojsonUrls, trackStats };

    // The multi-track array takes precedence: only run the single-URL fetch
    // when there is no geojson_urls array. In the article case both are set
    // (geojson_url is geojson_urls[0] for the elevation profile), so we fall
    // through to the multi-track block, which draws and styles every track.

    if (geojsonUrl && !geojsonUrls.length) {
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

  // Storybook / standalone — retry until Leaflet is available
  (function waitForLeaflet(attempts) {
    if (typeof L !== 'undefined') {
      initAll();
      return;
    }
    if (attempts > 20) {
      console.warn('map: Leaflet did not load after 2s');
      return;
    }
    setTimeout(() => waitForLeaflet(attempts + 1), 100);
  }(0));
})();
