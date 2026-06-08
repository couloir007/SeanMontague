/**
 * @file dest-hybrid.js
 * Drupal behavior: initializes the Leaflet map, wires hover-to-fly,
 * click-to-expand, and marker-click-to-scroll on [data-dest-hybrid].
 *
 * Dependencies: Leaflet (window.L), surface/map library.
 */

(function (Drupal) {
  'use strict';

  Drupal.behaviors.destHybrid = {
    attach: function (context) {
      var sections = context.querySelectorAll('[data-dest-hybrid]:not(.dh-processed)');
      sections.forEach(function (section) {
        section.classList.add('dh-processed');
        initDestHybrid(section);
      });
    }
  };

  function initDestHybrid(section) {
    var mapEl = section.querySelector('.dest-hybrid__map');
    var timeline = section.querySelector('[data-dest-timeline]');
    var nodes = section.querySelectorAll('.dest-hybrid__node');

    if (!mapEl || !window.L) return;

    // Parse map config from data attrs
    var centerStr = mapEl.dataset.center || '53.3,-8.0';
    var parts = centerStr.split(',');
    var center = [parseFloat(parts[0]), parseFloat(parts[1])];
    var zoom = parseInt(mapEl.dataset.zoom, 10) || 7;

    // Init Leaflet
    var map = L.map(mapEl, {
      scrollWheelZoom: false,
      zoomControl: true
    }).setView(center, zoom);

    L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenTopoMap (CC-BY-SA), © OpenStreetMap',
      maxZoom: 17
    }).addTo(map);

    // Build markers + polyline from node data attrs
    var markers = {};
    var latlngs = [];

    nodes.forEach(function (node, i) {
      var slug = node.dataset.slug;
      var lat = parseFloat(node.dataset.lat);
      var lon = parseFloat(node.dataset.lon);
      if (isNaN(lat) || isNaN(lon)) return;

      latlngs.push([lat, lon]);
      var idx = String(i + 1).padStart(2, '0');

      var icon = L.divIcon({
        className: 'dest-hybrid-marker',
        html: '<span class="dest-hybrid-marker__pin"></span><span class="dest-hybrid-marker__num">' + idx + '</span>',
        iconSize: [32, 32],
        iconAnchor: [16, 16]
      });

      var marker = L.marker([lat, lon], { icon: icon })
        .addTo(map)
        .bindTooltip(idx + ' ' + node.querySelector('.dest-hybrid__node-name').textContent, {
          direction: 'top',
          offset: [0, -12]
        });

      // Click marker: activate + scroll timeline
      marker.on('click', function () {
        activateNode(node);
        expandNode(node);
        scrollToNode(node);
      });

      markers[slug] = marker;
    });

    if (latlngs.length > 1) {
      L.polyline(latlngs, { color: '#3a5a40', weight: 3, dashArray: '6 6' }).addTo(map);
      map.fitBounds(latlngs, { padding: [50, 50] });
    }

    // Node hover: fly map
    nodes.forEach(function (node) {
      node.addEventListener('mouseenter', function () {
        activateNode(node);
      });

      // Node click: toggle expand
      var btn = node.querySelector('.dest-hybrid__node-head');
      if (btn) {
        btn.addEventListener('click', function () {
          var isExpanded = btn.getAttribute('aria-expanded') === 'true';
          if (isExpanded) {
            collapseNode(node);
          } else {
            expandNode(node);
          }
        });
      }
    });

    function activateNode(node) {
      var slug = node.dataset.slug;
      var lat = parseFloat(node.dataset.lat);
      var lon = parseFloat(node.dataset.lon);

      // Clear active from all
      nodes.forEach(function (n) { n.classList.remove('dest-hybrid__node--active'); });
      node.classList.add('dest-hybrid__node--active');

      // Clear active from all markers
      Object.entries(markers).forEach(function (entry) {
        var el = entry[1].getElement();
        if (el) el.classList.toggle('dest-hybrid-marker--active', entry[0] === slug);
      });

      // Fly map
      if (!isNaN(lat) && !isNaN(lon)) {
        map.flyTo([lat, lon], 9, { duration: 0.8 });
      }
    }

    function expandNode(node) {
      // Collapse all others
      nodes.forEach(function (n) {
        if (n !== node) collapseNode(n);
      });

      var btn = node.querySelector('.dest-hybrid__node-head');
      var panel = node.querySelector('.dest-hybrid__panel');
      if (btn && panel) {
        btn.setAttribute('aria-expanded', 'true');
        panel.removeAttribute('hidden');
        node.querySelector('.dest-hybrid__chev').textContent = '\u2212';
      }
    }

    function collapseNode(node) {
      var btn = node.querySelector('.dest-hybrid__node-head');
      var panel = node.querySelector('.dest-hybrid__panel');
      if (btn && panel) {
        btn.setAttribute('aria-expanded', 'false');
        panel.setAttribute('hidden', '');
        node.querySelector('.dest-hybrid__chev').textContent = '+';
      }
    }

    function scrollToNode(node) {
      if (!timeline) return;
      var top = node.offsetTop - timeline.offsetTop - 12;
      timeline.scrollTo({ top: top, behavior: 'smooth' });
    }
  }

})(Drupal);
