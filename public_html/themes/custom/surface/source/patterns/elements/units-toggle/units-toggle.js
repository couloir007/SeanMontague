/* jshint esversion: 6 */

/**
 * units-toggle.js
 *
 * Metric/imperial preference control for the site nav. Provides the UI, writes
 * localStorage.elevationUnit, and broadcasts 'surface-units-change'
 * { detail: { unit } }. elevation-profile.js listens for that event and reads
 * the same localStorage key on load, so a flip re-renders profiles live.
 *
 * A units preference is functional storage (ePrivacy-exempt) — no consent gate.
 * Dual-context: Drupal.behaviors + DOMContentLoaded; localStorage/drupalSettings
 * are null-guarded (absent or throwing in Storybook / privacy modes).
 */

(function () {
  'use strict';

  function safeLocalGet(key) {
    try { return window.localStorage ? window.localStorage.getItem(key) : null; }
    catch (e) { return null; }
  }

  function safeLocalSet(key, value) {
    try { if (window.localStorage) { window.localStorage.setItem(key, value); } }
    catch (e) { /* private mode / disabled — preference just will not persist */ }
  }

  // Same precedence as elevation-profile.js so the toggle's initial state
  // matches what the profiles show.
  function defaultUnit() {
    const stored = safeLocalGet('elevationUnit');
    if (stored === 'imperial' || stored === 'metric') { return stored; }
    const siteDefault = window.drupalSettings &&
      window.drupalSettings.trailMapper &&
      window.drupalSettings.trailMapper.elevationUnit;
    if (siteDefault === 'imperial' || siteDefault === 'metric') { return siteDefault; }
    const loc = (navigator.language || 'en-US');
    const region = (loc.split('-')[1] || '').toUpperCase();
    return ['US', 'LR', 'MM'].includes(region) ? 'imperial' : 'metric';
  }

  function reflect(toggle, unit) {
    toggle.querySelectorAll('.units-toggle__option').forEach(function (btn) {
      const active = btn.dataset.unit === unit;
      btn.classList.toggle('units-toggle__option--active', active);
      btn.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
  }

  function setUnit(toggle, unit) {
    if (unit !== 'imperial' && unit !== 'metric') { return; }
    safeLocalSet('elevationUnit', unit);
    window.dispatchEvent(new CustomEvent('surface-units-change', { detail: { unit: unit } }));
    reflect(toggle, unit);
  }

  function init(context) {
    context.querySelectorAll('.units-toggle:not([data-units-init])').forEach(function (toggle) {
      toggle.setAttribute('data-units-init', '1');
      reflect(toggle, defaultUnit());
      toggle.querySelectorAll('.units-toggle__option').forEach(function (btn) {
        btn.addEventListener('click', function () {
          setUnit(toggle, btn.dataset.unit);
        });
      });
    });
  }

  // Drupal context.
  if (typeof Drupal !== 'undefined' && Drupal.behaviors) {
    Drupal.behaviors.surfaceUnitsToggle = {
      attach: function (context) { init(context); },
    };
  }

  // Storybook / standalone context.
  if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', function () { init(document); });
    if (document.readyState !== 'loading') { init(document); }
  }
})();
