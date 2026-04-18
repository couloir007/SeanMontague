/* jshint esversion: 11 */
/* global Drupal */

(() => {
  "use strict";

  // Stats bar is populated entirely by Twig from manual schema fields.
  // No JS population needed — this file exists as a placeholder for
  // future interactivity (unit toggling, etc).

  if (typeof Drupal !== 'undefined' && Drupal.behaviors) {
    Drupal.behaviors.surfaceStatsBar = {
      attach: () => {},
    };
  }

})();
