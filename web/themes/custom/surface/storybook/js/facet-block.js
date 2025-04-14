/* jshint esversion: 6 */

'use strict';

document.addEventListener('DOMContentLoaded', function domContentLoadedHandler() {
  // Ensure this only runs once on relevant elements
  const elements = document.querySelectorAll('.search-filter .facet-block h3');

  elements.forEach(function elementIterator(element) {
    element.addEventListener('click', function clickHandler() {
      let sibling = this.nextElementSibling;

      while (sibling) {
        if (sibling.classList.contains('facets-widget-checkbox')) {
          // Capture the current sibling for use in callbacks
          const currentSibling = sibling;

          if (currentSibling.classList.contains('filter-open')) {
            currentSibling.style.transition = 'opacity 0.4s';
            currentSibling.style.opacity = 0;
            setTimeout(function hideElement() {
              currentSibling.style.display = 'none';
            }, 10);
          } else {
            currentSibling.style.display = 'block';
            setTimeout(function showElement() {
              currentSibling.style.transition = 'opacity 0.4s';
              currentSibling.style.opacity = 1;
            }, 10); // Ensure the transition starts after the display change
          }

          currentSibling.classList.toggle('filter-open');
          break; // Exit loop after finding the matching sibling
        }
        sibling = sibling.nextElementSibling; // Move to the next sibling
      }
    });
  });
});
