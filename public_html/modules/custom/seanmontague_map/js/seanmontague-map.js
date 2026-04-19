/* jshint esversion: 11 */
/* global L, Drupal, drupalSettings */

(function (Drupal, drupalSettings) {
  'use strict';

  /**
   * SeanMontague Map behavior.
   *
   * Fetches the aggregated GeoJSON endpoint and overlays four typed layer groups
   * (places, pois, articles, trips) onto any Leaflet maps on the page.
   * Runs after window.load so Leaflet maps are fully initialized.
   */
  window.addEventListener('load', function () {
    if (!drupalSettings.leaflet || !drupalSettings.seanMap) {
      return;
    }

    const config   = drupalSettings.seanMap;
    const endpoint = config.endpoint || '/sean-map/items';
    const layers   = config.layers  || {};

    Object.keys(drupalSettings.leaflet).forEach(function (mapId) {
      const lMap = drupalSettings.leaflet[mapId].lMap;
      if (!lMap) {
        return;
      }
      initSeanMap(lMap, layers, endpoint);
    });
  });

  function initSeanMap(lMap, layerConfig, endpoint) {
    // One LayerGroup per content type.
    const layerGroups = {
      place:   L.layerGroup(),
      poi:     L.layerGroup(),
      article: L.layerGroup(),
      trip:    L.layerGroup(),
    };

    // Add all layers to the map immediately (they start empty).
    Object.values(layerGroups).forEach(function (lg) {
      lg.addTo(lMap);
    });

    // Fetch the aggregated FeatureCollection.
    fetch(endpoint)
      .then(function (res) {
        if (!res.ok) {
          throw new Error('seanmontague_map: HTTP ' + res.status);
        }
        return res.json();
      })
      .then(function (collection) {
        if (!collection || collection.type !== 'FeatureCollection') {
          return;
        }
        collection.features.forEach(function (feature) {
          addFeature(feature, layerGroups);
        });
      })
      .catch(function (err) {
        console.error('seanmontague_map:', err);
      });

    // Build Leaflet layer control overlay labels with color swatches.
    const overlayMaps = {};
    const typeMap = {
      place:   'places',
      poi:     'pois',
      article: 'articles',
      trip:    'trips',
    };
    Object.entries(typeMap).forEach(function ([type, key]) {
      const cfg = layerConfig[key];
      if (!cfg) {
        return;
      }
      const label =
        '<span class="sean-map-swatch" style="background:' + cfg.color + '"></span>' +
        cfg.label;
      overlayMaps[label] = layerGroups[type];
    });

    L.control.layers(null, overlayMaps, { collapsed: false }).addTo(lMap);
  }

  function addFeature(feature, layerGroups) {
    const props    = feature.properties || {};
    const type     = props.type;
    const color    = props.color  || '#888';
    const popup    = props.popup_html || '<strong>' + (props.title || '') + '</strong>';
    const geom     = feature.geometry;
    const lg       = layerGroups[type];

    if (!geom || !lg) {
      return;
    }

    if (geom.type === 'Point') {
      const [lon, lat] = geom.coordinates;
      const marker = L.circleMarker([lat, lon], circleOptions(color));
      marker.bindPopup(popup, { maxWidth: 280, className: 'sean-map-popup' });
      marker.addTo(lg);

    } else if (geom.type === 'LineString') {
      const coords = geom.coordinates.map(function ([lon, lat]) {
        return [lat, lon];
      });
      const line = L.polyline(coords, lineOptions(color));
      line.bindPopup(popup, { maxWidth: 280, className: 'sean-map-popup' });
      // Open popup near the midpoint of the line.
      line.on('click', function (e) {
        line.openPopup(e.latlng);
      });
      line.addTo(lg);
    }
  }

  function circleOptions(color) {
    return {
      radius:      7,
      fillColor:   color,
      color:       '#ffffff',
      weight:      2,
      opacity:     1,
      fillOpacity: 0.9,
    };
  }

  function lineOptions(color) {
    return {
      color:   color,
      weight:  3,
      opacity: 0.85,
    };
  }

})(Drupal, drupalSettings);
