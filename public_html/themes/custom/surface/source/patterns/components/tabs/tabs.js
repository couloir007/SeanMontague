'use strict';

((Drupal, once) => {

  Drupal.behaviors.primaryTabs = {
    attach: function attach(context) {
      Drupal.primaryTabs.init(context);
    }
  };

  Drupal.primaryTabs = {
    init: function (context) {
      once('surfaceTabsInit', '[data-drupal-nav-primary-tabs]', context).forEach((el) => {
        const tabs = el.querySelector('.tabs');
        const activeTab = tabs.querySelector('.is-active');

        if (this.isTabsMobileLayout() && !activeTab.matches('.tabs__tab:first-child')) {
          const newActiveTab = activeTab.cloneNode(true);
          const firstTab = tabs.querySelector('.tabs__tab:first-child');
          tabs.insertBefore(newActiveTab, firstTab);
          tabs.removeChild(activeTab);
        }
      });

      // Tabs click
      once('surfaceTabs', '.tabs__trigger', context).forEach(el => el.addEventListener('click', e => {
        e.preventDefault();
        this.toggleTabs();
      }));
    },

    // Tabs in mobile layout.
    isTabsMobileLayout: () => {
      const tabs = document.querySelector('.tabs');
      return tabs.querySelector('.tabs__trigger').clientHeight > 0;
    },

    // Toggle tabs.
    toggleTabs: () => {
      const tabs = document.querySelector('.tabs');

      if (tabs.classList.contains('is-expanded')) {
        Drupal.primaryTabs.collapseTabs();
      }
      else {
        Drupal.primaryTabs.showTabs();
      }
    },

    // Collapse tabs.
    collapseTabs: () => {
      const tabs = document.querySelector('.tabs');
      const tabsTrigger = document.querySelector('.tabs__trigger');
      tabsTrigger.setAttribute('aria-expanded', 'false');
      tabs.classList.remove('is-expanded');
    },

    // Show tabs.
    showTabs: () => {
      const tabs = document.querySelector('.tabs');
      const tabsTrigger = document.querySelector('.tabs__trigger');
      tabsTrigger.setAttribute('aria-expanded', 'true');
      tabs.classList.add('is-expanded');
    }
  };
})(Drupal, once);
