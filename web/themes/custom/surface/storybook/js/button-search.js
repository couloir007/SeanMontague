/* eslint-disable */

const searchContainer = document.querySelector('[data-drupal-selector="site-search"]');

Drupal.behaviors.surfaceSearch = {
  attach: function (context, settings) {
    if (context === document) {
      this.initGlobalListeners(context);
    }
    this.initSearchToggle(context);
    this.initCloseModal(context);
  },

  initGlobalListeners: function (context) {
    const searchContainer = context.querySelector('[data-drupal-selector="site-search"]');
    if (searchContainer) {
      document.addEventListener('keyup', e => {
        if (this.searchIsVisible() && (e.key === 'Escape' || e.key === 'Esc')) {
          e.preventDefault();
          this.toggleSearch();
        }
      });
    }
  },

  initSearchToggle: function (context) {
    const searchOpenButtons = context.querySelectorAll('[data-drupal-selector="search-open"]');
    searchOpenButtons.forEach(el => {
      if (!el.hasAttribute('data-surface-search-processed')) {
        el.addEventListener('click', e => {
          e.preventDefault();
          this.toggleSearch();
        });
        el.setAttribute('data-surface-search-processed', 'true');
      }
    });
  },

  initCloseModal: function (context) {
    const searchCloseButtons = context.querySelectorAll('[data-drupal-selector="search-close"]');
    searchCloseButtons.forEach(el => {
      if (!el.hasAttribute('data-surface-search-processed')) {
        el.addEventListener('click', e => {
          e.preventDefault();
          this.collapseSearch();
        });
        el.setAttribute('data-surface-search-processed', 'true');
      }
    });
  },

  searchIsVisible: function() {
    return searchContainer.classList.contains('is-active');
  },

  modalIsVisible: function() {
    const searchType = document.body.getAttribute('data-search');
    return searchType === 'modal';
  },

  toggleSearch: function() {
    if (this.searchIsVisible()) {
      this.collapseSearch();
    } else {
      this.showSearch();
    }
  },

  showSearch: function() {
    const searchButton = document.querySelector('[data-drupal-selector="search-open"]');
    searchButton.setAttribute('aria-expanded', 'true');
    searchContainer.classList.add('is-active');
    searchContainer.addEventListener('transitionend', this.handleFocus, {
      once: true
    });
  },

  collapseSearch: function() {
    const searchButton = document.querySelector('[data-drupal-selector="search-open"]');
    searchButton.setAttribute('aria-expanded', 'false');
    searchContainer.classList.remove('is-active');
  },

  handleFocus: function() {
    const searchInput = searchContainer.querySelector('input[type="search"]');
    searchInput.focus();
  }
};
