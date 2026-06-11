/* jshint esversion: 11 */
/* global Drupal */

/**
 * stats-bar.js
 *
 * Converts the top summary bar's DISTANCE stats from their metric baseline to
 * the user's unit preference, live with the nav toggle. Distance items carry the
 * raw value in data-meters; this rewrites .stats-bar__value + .stats-bar__unit
 * (m → km ÷1000, or miles ÷1609.344, one decimal). Items without data-meters
 * (e.g. the POI count) are never touched.
 *
 * Unit precedence mirrors elevation-profile.js / units-toggle.js. Dual-context
 * (Drupal.behaviors + DOMContentLoaded); localStorage/drupalSettings null-guarded.
 */

(() => {
  "use strict";

  const M_PER_MI = 1609.344;

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
    const region = ((navigator.language || 'en-US').split('-')[1] || '').toUpperCase();
    return ['US', 'LR', 'MM'].includes(region) ? 'imperial' : 'metric';
  }

  let currentUnit = defaultUnit();

  // Distance only — meters → km / mi, one decimal.
  function formatDistance(meters, unit) {
    return unit === 'imperial'
      ? { value: (meters / M_PER_MI).toFixed(1), unit: 'mi' }
      : { value: (meters / 1000).toFixed(1), unit: 'km' };
  }

  function convertItem(item) {
    const meters = parseFloat(item.getAttribute('data-meters'));
    if (!isFinite(meters)) return;
    const out = formatDistance(meters, currentUnit);
    const valEl = item.querySelector('.stats-bar__value');
    const unitEl = item.querySelector('.stats-bar__unit');
    if (valEl) valEl.textContent = out.value;
    if (unitEl) unitEl.textContent = out.unit;
  }

  function init(context) {
    (context || document)
      .querySelectorAll('.stats-bar__item[data-meters]')
      .forEach(convertItem);
  }

  // Drupal context.
  if (typeof Drupal !== 'undefined' && Drupal.behaviors) {
    Drupal.behaviors.surfaceStatsBar = {
      attach: (context) => { init(context); },
    };
  }

  // Storybook / standalone context.
  if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => init(document));
    if (document.readyState !== 'loading') init(document);
  }

  // Live unit toggle from the nav.
  window.addEventListener('surface-units-change', (e) => {
    const u = e.detail && e.detail.unit;
    if (u !== 'imperial' && u !== 'metric') return;
    currentUnit = u;
    init(document);
  });
})();
