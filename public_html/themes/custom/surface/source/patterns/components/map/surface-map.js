/**
 * surface-map.js
 *
 * Initializes Leaflet maps for the Surface theme.
 * Matches the pattern used in the working HTML prototypes.
 * Runs via Drupal.behaviors in Drupal, and DOMContentLoaded/setTimeout in Storybook.
 *
 * Configure via data-attributes on the .surface-map element:
 *   data-map-center="44.593,-71.918"
 *   data-map-zoom="12"
 *   data-map-interactive="false"    non-interactive bg map (hero)
 *   data-map-markers='[{"lat":N,"lon":N,"color":"#hex","label":"html"}]'
 *   data-map-lines='[{"coords":[[lon,lat],...],"color":"#hex","weight":N,"dash":"N,N"}]'
 */
(function () {
  'use strict';

  var TILE_URL  = 'https://basemap.nationalmap.gov/arcgis/rest/services/USGSTopo/MapServer/tile/{z}/{y}/{x}';
  var TILE_ATTR = 'Tiles &copy; <a href="https://usgs.gov">USGS</a> The National Map';

  function pinIcon(color) {
    return L.divIcon({
      html: '<div style="width:10px;height:10px;border-radius:50%;background:' + color + ';border:2px solid #fff;box-shadow:0 1px 6px rgba(0,0,0,0.3)"></div>',
      iconSize: [10, 10],
      iconAnchor: [5, 5],
      className: ''
    });
  }

  function parseJSON(str, fallback) {
    if (!str) return fallback;
    try { return JSON.parse(str); } catch (e) { return fallback; }
  }

  function initMap(el) {
    if (el._surfaceMapInit) return;
    el._surfaceMapInit = true;

    if (typeof L === 'undefined') {
      console.warn('surface-map: Leaflet not loaded');
      return;
    }

    var parts       = (el.dataset.mapCenter || '44.593,-71.918').split(',');
    var lat         = parseFloat(parts[0]);
    var lon         = parseFloat(parts[1]);
    var zoom        = parseInt(el.dataset.mapZoom || '12', 10);
    var interactive = (el.dataset.mapInteractive !== 'false');
    var markers     = parseJSON(el.dataset.mapMarkers, []);
    var lines       = parseJSON(el.dataset.mapLines, []);

    var map = L.map(el, {
      center:          [lat, lon],
      zoom:            zoom,
      zoomControl:     interactive,
      scrollWheelZoom: interactive,
      dragging:        interactive,
      doubleClickZoom: interactive,
      keyboard:        interactive
    });

    L.tileLayer(TILE_URL, {
      maxZoom: 16,
      attribution: TILE_ATTR
    }).addTo(map);

    lines.forEach(function (line) {
      L.geoJSON({
        type: 'Feature',
        geometry: { type: 'LineString', coordinates: line.coords }
      }, {
        style: {
          color:     line.color   || '#3a5a40',
          weight:    line.weight  || 3,
          opacity:   line.opacity || 0.85,
          dashArray: line.dash    || null
        }
      }).addTo(map);
    });

    markers.forEach(function (m) {
      L.marker([m.lat, m.lon], { icon: pinIcon(m.color || '#3a5a40') })
        .addTo(map)
        .bindPopup(m.label || '');
    });

    // invalidateSize — critical for maps in position:absolute/hidden containers
    requestAnimationFrame(function () { map.invalidateSize(); });
    setTimeout(function () { map.invalidateSize(); }, 150);
    setTimeout(function () { map.invalidateSize(); }, 500);

    window._surfaceMaps = window._surfaceMaps || {};
    window._surfaceMaps[el.id || 'map'] = map;
    if (!window._surfaceMapInstance) window._surfaceMapInstance = map;

    // Dispatch event for other components (like elevation profile) to connect
    window.dispatchEvent(new CustomEvent('surface-map-ready', {
      detail: { map_id: el.id, map: map }
    }));
  }

  function initAll(context) {
    var root = context || document;
    var els  = root.querySelectorAll('.surface-map');
    for (var i = 0; i < els.length; i++) { initMap(els[i]); }
  }

  // Drupal
  if (typeof Drupal !== 'undefined' && typeof Drupal.behaviors !== 'undefined') {
    Drupal.behaviors.surfaceMap = {
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
