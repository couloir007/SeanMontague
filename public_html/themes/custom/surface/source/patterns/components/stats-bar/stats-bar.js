/* jshint esversion: 6 */
(function () {
  "use strict";

  const STAT_MAP = [
    { key: 'distance',  label: 'Distance',  unit: 'miles',   decimals: 1 },
    { key: 'elev_gain', label: 'Elevation', unit: 'ft gain', decimals: 0 },
    { key: 'elev_loss', label: 'Loss',      unit: 'ft loss', decimals: 0 },
    { key: 'elev_min',  label: 'Min Elev',  unit: 'ft',      decimals: 0 },
    { key: 'elev_max',  label: 'Max Elev',  unit: 'ft',      decimals: 0 },
  ];

  function fmt(val, decimals) {
    if (val === null || val === undefined || isNaN(val)) return '—';
    return decimals > 0
      ? parseFloat(val).toFixed(decimals)
      : Math.round(val).toLocaleString();
  }

  document.addEventListener('surface-map-ready', function (e) {
    var detail = e.detail || {};
    var stats  = detail.stats;
    var mapId  = detail.mapId;
    if (!stats || !mapId) return;

    var bar = document.querySelector('.stats-bar[data-map-id="' + mapId + '"]');
    if (!bar) return;

    STAT_MAP.forEach(function (def) {
      var val = stats[def.key];
      if (val === undefined) return;

      var item = bar.querySelector('[data-stat="' + def.key + '"]');
      if (!item) {
        item = document.createElement('div');
        item.className = 'stats-bar__item';
        item.dataset.stat = def.key;
        item.innerHTML =
          '<span class="stats-bar__label">' + def.label + '</span>' +
          '<span class="stats-bar__value"></span>' +
          '<span class="stats-bar__unit">' + def.unit + '</span>';
        bar.appendChild(item);
      }

      var valueEl = item.querySelector('.stats-bar__value');
      if (valueEl) valueEl.textContent = fmt(val, def.decimals);
    });
  });

}());
