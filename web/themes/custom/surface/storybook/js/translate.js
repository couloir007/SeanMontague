/* eslint-disable */

const translateContainer = document.querySelector('[data-drupal-selector="translate-container"]');

Drupal.behaviors.surfaceTranslate = {
  attach: function (context, settings) {
    if (context === document) {
      this.initGlobalListeners(context);
    }
    this.initTranslateToggle(context);
  },

  initGlobalListeners: function (context) {
    const translateElement = context.querySelector('[data-drupal-selector="translate"]');
    if (translateElement) {
      document.addEventListener('keyup', e => {
        if (this.translateIsVisible() && (e.key === 'Escape' || e.key === 'Esc')) {
          e.preventDefault();
          this.toggleTranslate();
        }
      });
    }
  },

  initTranslateToggle: function (context) {
    const translateButtons = context.querySelectorAll('[data-drupal-selector="translate-button"]');
    translateButtons.forEach(el => {
      if (!el.hasAttribute('data-surface-translate-processed')) {
        el.addEventListener('click', e => {
          e.preventDefault();
          this.toggleTranslate();
        });
        el.setAttribute('data-surface-translate-processed', 'true');
      }
    });
  },

  translateIsVisible: function() {
    return translateContainer.classList.contains('is-active');
  },

  toggleTranslate: function() {
    if (this.translateIsVisible()) {
      this.collapseTranslate();
    } else {
      this.showTranslate();
    }
  },

  showTranslate: function() {
    const translateButton = document.querySelector('[data-drupal-selector="translate-button"]');
    translateButton.setAttribute('aria-expanded', 'true');
    translateContainer.classList.add('is-active');
  },

  collapseTranslate: function() {
    const translateButton = document.querySelector('[data-drupal-selector="translate-button"]');
    translateButton.setAttribute('aria-expanded', 'false');
    translateContainer.classList.remove('is-active');
  }
};


