/* eslint-disable */
/* jshint esversion: 6 */

'use strict';

((Drupal, once) => {
  const menuContainer = document.querySelector('[data-drupal-selector="site-navigation"]');
  // Sets variable to check the viewport width and if the width changes, we run the resize
  // function below.
  const deviceWidth = window.innerWidth;

  Drupal.behaviors.surfaceMenu = {
    attach: function attach(context) {
      Drupal.surfaceMenu.init(context);
    },
  };

  Drupal.surfaceMenu = {
    init: function (context) {
      once('surfaceMenuInit', '[data-drupal-selector="site-navigation"]', context).forEach(() => {
        document.addEventListener('keyup', e => {
          if (this.menuIsVisible() && e.key === 'Escape' || e.key === 'Esc') {
            if (Drupal.surface.areAnySubNavsOpen()) {
              Drupal.surface.closeAllSubNav();
            }
            else {
              this.toggleMenu();
            }
          }
        });

        // Handles viewport width resize
        window.addEventListener('resize', () => {
          if(window.innerWidth !== deviceWidth) {
            if (this.menuIsVisible()) {
              this.toggleMenu();
            }

            Drupal.surface.closeAllSubNav();
          }
        });
      });

      // Menu toggle
      once('surfaceMenuToggle', '[data-drupal-selector="mobile-button"]', context).forEach(el => el.addEventListener('click', e => {
        e.preventDefault();
        this.toggleMenu('main');
      }));
    },

    // Check if menu is visible
    menuIsVisible: () => {
      return menuContainer.classList.contains('is-active');
    },

    // Toggle menu
    toggleMenu: () => {
      if (Drupal.surfaceMenu.menuIsVisible()) {
        Drupal.surfaceMenu.collapseMenu();
      }
      else {
        Drupal.surfaceMenu.showMenu();
      }
    },

    // Show menu
    showMenu: () => {
      const mobileButton = document.querySelector('[data-drupal-selector="mobile-button"]');
      const menuContainer = document.querySelector('[data-drupal-selector="site-navigation"]');

      mobileButton.setAttribute('aria-expanded', 'true');
      menuContainer.classList.add('is-active');
    },

    // Collapse menu
    collapseMenu: () => {
      const mobileButton = document.querySelector('[data-drupal-selector="mobile-button"]');
      const menuContainer = document.querySelector('[data-drupal-selector="site-navigation"]');

      mobileButton.setAttribute('aria-expanded', 'false');
      menuContainer.classList.remove('is-active');
    },
  };
})(Drupal, once);
