/* eslint-disable */
/* jshint esversion: 6 */

'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const sitePrimary = document.querySelector('.site-primary');
  const menuOverview = document.querySelector('.menu-overview');

  if (!sitePrimary || !menuOverview) {
    return;
  }

  // How far down .menu-overview is from the top of the page (in pixels)
  const menuOverviewOffset = menuOverview.getBoundingClientRect().top + window.scrollY;

  window.addEventListener('scroll', () => {
    const sitePrimaryHeight = sitePrimary.offsetHeight;

    /*
      If our scroll position is beyond the point where .menu-overview
      hits the bottom of .site-primary, fix .menu-overview in place.
    */
    if ((window.scrollY + menuOverview.offsetHeight) >= (menuOverviewOffset - sitePrimaryHeight)) {
      menuOverview.classList.add('fixed');
      // Position it exactly below site-primary using a template literal
      menuOverview.style.top = `${sitePrimaryHeight}px`;
    } else {
      // Put .menu-overview back into normal flow
      menuOverview.classList.remove('fixed');
      menuOverview.style.top = '';
    }
  });

  // Grab all the links inside .section_link
  const sectionLinks = document.querySelectorAll('.overview-menu__sections a');

  // Define or select your "wrap" element (adjust selector as needed)
  const wrap = document.querySelector('.site-primary'); // Example
  const wrap2 = document.querySelector('.menu-overview');

  sectionLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const dest = link.getAttribute('href'); // The #id or URL
      const targetElem = document.querySelector(dest);

      if (!targetElem) return; // If we can't find the destination, exit.

      // Calculate the offset based on whether wrap and wrap2 have 'fixed'
      let offset = 305 + 60 + 16; // Default = 381

      if (wrap && wrap2) {
        const wrapIsFixed = wrap.classList.contains('fixed');
        const wrap2IsFixed = wrap2.classList.contains('fixed');

        if (wrapIsFixed && wrap2IsFixed) {
          offset = 118 + 60 + 16; // 194
        } else if (wrapIsFixed && !wrap2IsFixed) {
          offset = 210 + 60 + 16; // 286
        }
        // else fallback is already set to 305+60+16
      }

      // Smoothly scroll to the target minus the offset
      const targetPos = targetElem.getBoundingClientRect().top + window.scrollY - offset;

      // Native smooth scrolling (duration is not exactly 1.5s)
      window.scrollTo({
        top: targetPos,
        behavior: 'smooth'
      });

      // If .responsive-menu-toggle-icon.overview_menu is visible (display: block),
      // toggle the .overview-top__wrapper ul
      const menuIcon = document.querySelector('.responsive-menu-toggle-icon.overview_menu');
      if (menuIcon) {
        const displayVal = window.getComputedStyle(menuIcon).display;
        if (displayVal === 'block') {
          const wrapperUl = document.querySelector('.overview-top__wrapper ul');
          if (wrapperUl) {
            // A simple toggle (no animation).
            if (wrapperUl.style.display === 'none' || !wrapperUl.style.display) {
              wrapperUl.style.display = 'block';
            } else {
              wrapperUl.style.display = 'none';
            }
          }
        }
      }
      // No need to return any value here.
    });
  });
});


((Drupal, once) => {
  const menuContainer = document.querySelector('[data-drupal-selector="sections-navigation"]');
  // Sets variable to check the viewport width and if the width changes, we run the resize
  // function below.
  const deviceWidth = window.innerWidth;

  /* Sections Nav */
  Drupal.behaviors.rfaceMenusuSections = {
    attach: function attach(context) {
      Drupal.surfaceMenuSections.init(context);
    },
  };

  Drupal.surfaceMenuSections = {
    init: function (context) {
      once('surfaceMenuInitSections', '[data-drupal-selector="sections-navigation"]', context).forEach(() => {
        document.addEventListener('keyup', e => {
          if (this.menuIsVisible() && e.key === 'Escape' || e.key === 'Esc') {
            if (Drupal.surface.areAnySubNavsOpen()) {
              Drupal.surface.closeAllSubNav();
            }
            else {
              this.toggleMenuSections();
            }
          }
        });

        // Handles viewport width resize
        window.addEventListener('resize', () => {
          if(window.innerWidth !== deviceWidth) {
            if (this.menuIsVisible()) {
              this.toggleMenuSections();
            }

            Drupal.surface.closeAllSubNav();
          }
        });
      });

      // Menu toggle
      once('surfaceMenuToggleSections', '[data-drupal-selector="mobile-button-sections"]', context).forEach(el => el.addEventListener('click', e => {
        e.preventDefault();
        this.toggleMenuSections();
      }));
    },

    // Check if menu is visible
    menuIsVisible: () => {
      return menuContainer.classList.contains('is-active');
    },

    // Toggle menu
    toggleMenuSections: () => {
      if (Drupal.surfaceMenuSections.menuIsVisible()) {
        Drupal.surfaceMenuSections.collapseMenu();
      }
      else {
        Drupal.surfaceMenuSections.showMenu();
      }
    },

    // Show menu
    showMenu: () => {
      const mobileButton = document.querySelector('[data-drupal-selector="mobile-button-sections"]');
      const menuContainer = document.querySelector('[data-drupal-selector="sections-navigation"]');

      mobileButton.setAttribute('aria-expanded', 'true');
      menuContainer.classList.add('is-active');
    },

    // Collapse menu
    collapseMenu: () => {
      const mobileButton = document.querySelector('[data-drupal-selector="mobile-button-sections"]');
      const menuContainer = document.querySelector('[data-drupal-selector="sections-navigation"]');

      mobileButton.setAttribute('aria-expanded', 'false');
      menuContainer.classList.remove('is-active');
    },
  };
})(Drupal, once);
