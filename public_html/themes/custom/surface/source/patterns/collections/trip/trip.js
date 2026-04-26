/* jshint esversion: 6 */
/* global L */

/**
 * trip.js
 *
 * Initialises small Leaflet mini-maps inside .trip__card-map elements.
 * Each card carries data-lat, data-lon, data-name, data-num attributes.
 * Non-interactive — tiles + a small destination marker only.
 */

(function () {
  'use strict';

  var tileUrl = 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png';
  var tileOpts = { maxZoom: 17, attribution: '', zIndex: 1 };

  function makeSmallPin() {
    return L.divIcon({
      html: '<div style="width:10px;height:10px;border-radius:50%;background:#3a5a40;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,0.28)"></div>',
      iconSize: [10, 10],
      iconAnchor: [5, 5],
      className: '',
    });
  }

  function initCardMaps(context) {
    var root = context || document;
    var cards = root.querySelectorAll('.trip__card-map:not([data-map-init])');

    cards.forEach(function (el) {
      el.setAttribute('data-map-init', '1');

      var lat = parseFloat(el.dataset.lat);
      var lon = parseFloat(el.dataset.lon);
      var name = el.dataset.name || '';

      if (!isFinite(lat) || !isFinite(lon)) return;

      var m = L.map(el, {
        center: [lat, lon],
        zoom: 10,
        zoomControl: false,
        scrollWheelZoom: false,
        dragging: false,
        touchZoom: false,
        doubleClickZoom: false,
        keyboard: false,
        attributionControl: false,
      });

      L.tileLayer(tileUrl, tileOpts).addTo(m);
      L.marker([lat, lon], { icon: makeSmallPin() })
        .addTo(m)
        .bindPopup('<strong>' + name + '</strong>');

      // Ensure tiles paint after container is visible
      setTimeout(function () { m.invalidateSize(); }, 200);
    });
  }

  // Drupal
  if (typeof Drupal !== 'undefined' && Drupal.behaviors) {
    Drupal.behaviors.surfaceTrip = {
      attach: function (context) {
        if (typeof L === 'undefined') return;
        initCardMaps(context);
      },
    };
  }

  // Storybook / standalone
  function waitForLeaflet(attempts) {
    if (typeof L !== 'undefined') {
      initCardMaps();
      return;
    }
    if (attempts > 20) return;
    setTimeout(function () { waitForLeaflet(attempts + 1); }, 100);
  }

  if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', function () { waitForLeaflet(0); });
    } else {
      waitForLeaflet(0);
    }
  }
}());
