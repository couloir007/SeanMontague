/* jshint esversion: 11 */
/* eslint-disable no-param-reassign */
/* eslint-disable func-names */
/* global L, Drupal, drupalSettings, console, jQuery */

(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * Entry point: waits for all page resources to be fully loaded.
   */
  window.addEventListener('load', function () {
    if (!drupalSettings.leaflet) {
      console.warn('No Leaflet maps found in drupalSettings.');
      return;
    }

    // Move zoom control into the custom container if present.
    const selectedElement = document.querySelector('.leaflet-control-zoom');
    const target = document.querySelector('.leaflet-control-container-parent .leaflet-top.leaflet-left');
    if (selectedElement && target) {
      target.prepend(selectedElement);
    }

    Object.keys(drupalSettings.leaflet).forEach(function (mapId) {
      const cfg = drupalSettings.leaflet[mapId];
      const lMap = cfg && cfg.lMap;
      const features = drupalSettings.leaflet[mapId].features;
      const theMap = drupalSettings.leaflet[mapId].map;
      const initialCenter = theMap.settings.center;

      let mapWidthPx = document.querySelector('.leaflet') ? document.querySelector('.leaflet').offsetWidth - 350 : 0;

      // Cache for map items GeoJSON — avoids repeated endpoint fetches.
      let cachedMapItems = null;

      adjustMapHeight();
      window.addEventListener('resize', () => adjustMapHeight());

      if (!lMap) {
        console.error(`Leaflet map ${mapId} not initialized after window.load.`);
        return;
      }

      lMap.options.worldCopyJump = false;

      if (mapWidthPx < 1320) {
        adjustMapView(mapWidthPx, theMap, lMap);
      }

      setupMarkers(features, lMap);
      setupCloseButton();
      setupResetViewButton();

      // Fetch map items from the configurable endpoint and wire up list interactions.
      const endpoint = drupalSettings.leaflet_full_page?.itemsEndpoint;
      if (endpoint) {
        fetch(endpoint)
          .then(res => {
            if (!res.ok) {
              throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.json();
          })
          .then(collection => {
            cachedMapItems = collection;

            const listContainer = document.querySelector('.leaflet__list');
            if (!listContainer) {
              console.warn('.leaflet__list container not found');
              return;
            }

            // Event delegation for list item clicks.
            listContainer.addEventListener('click', (e) => {
              const li = e.target.closest('li.map-item-list');
              if (!li) {
                return;
              }
              const itemId = li.getAttribute('data-item-id') || li.getAttribute('data-location-id');
              getContent(itemId);
            });

            // Prev / next arrows navigate sequentially through the list.
            document.querySelectorAll('.leaflet__content a.arrow-left').forEach(link => {
              link.addEventListener('click', function (e) {
                e.preventDefault();
                const nid = $('.leaflet__content').attr('maps-nid');
                const $cur = $(`#${nid}`);
                const itemId = $cur.is(':first-child')
                  ? $('.leaflet__list li').last().attr('id')
                  : $cur.prev().attr('id');
                getContent(itemId);
              });
            });

            document.querySelectorAll('.leaflet__content a.arrow-right').forEach(link => {
              link.addEventListener('click', function (e) {
                e.preventDefault();
                const nid = $('.leaflet__content').attr('maps-nid');
                const $cur = $(`#${nid}`);
                const itemId = $cur.is(':last-child')
                  ? $('.leaflet__list li').first().attr('id')
                  : $cur.next().attr('id');
                getContent(itemId);
              });
            });
          })
          .catch(err => console.error('Failed to load map items:', err));
      }

      /**
       * Fetches and displays content for the given item ID.
       * Uses cachedMapItems when available to avoid repeat requests.
       */
      function getContent(theID) {
        if (cachedMapItems) {
          processContentData(cachedMapItems, theID);
          return;
        }
        if (!endpoint) {
          console.warn('No itemsEndpoint configured in drupalSettings.leaflet_full_page');
          return;
        }
        fetch(endpoint)
          .then(res => {
            if (!res.ok) {
              throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.json();
          })
          .then(data => {
            cachedMapItems = data;
            processContentData(data, theID);
          })
          .catch(err => console.error(err));
      }

      /**
       * Populates the content panel from a GeoJSON FeatureCollection or a
       * legacy flat array (backward-compatible).
       */
      function processContentData(collection, theID) {
        let props;

        if (collection && collection.type === 'FeatureCollection') {
          const feature = collection.features.find(f => String(f.id) === String(theID));
          if (!feature) {
            return;
          }
          props = Object.assign({}, feature.properties, { _id: feature.id });
        } else if (Array.isArray(collection)) {
          const item = collection.find(obj =>
            String(obj.id) === String(theID) || String(obj.contentID) === String(theID)
          );
          if (!item) {
            return;
          }
          props = Object.assign({}, item, { _id: item.id || item.contentID });
        } else {
          return;
        }

        const titleEl = document.querySelector('.leaflet__content-title');
        if (titleEl) {
          titleEl.textContent = props.title || '';
        }

        const subTitleEl = document.querySelector('.leaflet__content-sub-title');
        if (subTitleEl && props.subtitle) {
          subTitleEl.innerHTML = decodeHtmlEntities(props.subtitle);
        }

        const main = document.querySelector('.leaflet__content-main');
        if (main) {
          main.innerHTML = '';
          if (props.description) {
            main.insertAdjacentHTML('beforeend', decodeHtmlEntities(props.description));
          }
          if (props.body) {
            main.insertAdjacentHTML('beforeend', decodeHtmlEntities(props.body));
          }

          if (window.injectMadsViewerStyles) {
            setTimeout(() => {
              window.injectMadsViewerStyles();
              initializeAccordion(main);
            }, 150);
          } else {
            initializeAccordion(main);
          }

          document.querySelectorAll('.leaflet__content-textarea a').forEach(link => {
            link.setAttribute('target', '_blank');
          });
        }

        document.querySelector('.leaflet__content')?.setAttribute('maps-nid', theID);
        document.querySelector('.leaflet__content-scrollable')?.scrollTo({ top: 0, behavior: 'smooth' });
        document.querySelector('.leaflet__list-container')?.classList.add('open');
        document.querySelector('.leaflet__list-container')?.classList.add('table-up');

        calculateScrollableHeight();
        setTimeout(() => calculateScrollableHeight(), 800);
        setTimeout(() => calculateScrollableHeight(), 1200);
      }

      /**
       * Flies the map to initialCenter, adjusting zoom for narrow viewports.
       */
      function adjustMapView(mapWidthPx, theMap, lMap) {
        const containerEl = document.querySelector('.leaflet__list-container');
        if (!containerEl || !containerEl.parentElement) {
          return;
        }

        const parentEl = containerEl.parentElement;
        const containerW = containerEl.getBoundingClientRect().width;
        const parentW = parentEl.getBoundingClientRect().width;
        const pct = parentW ? (containerW / parentW) * 100 : 0;

        let theTargetZoom = theMap.settings.zoom;
        const theTargetLat = initialCenter.lat;
        const theTargetLon = initialCenter.lon;

        if (pct === 100 && window.innerWidth < 800) {
          theTargetZoom = Math.max(theMap.settings.zoom - 1, 1);
        }

        lMap.flyTo([theTargetLat, theTargetLon], theTargetZoom, { duration: 1.0 });
        lMap.invalidateSize();
      }

      /**
       * Adjusts the map container height to fill the available viewport.
       */
      function adjustMapHeight() {
        const mapElement = document.getElementById(mapId);
        if (!mapElement || !lMap) {
          return;
        }

        const isFullscreen = document.fullscreenElement ||
          document.webkitFullscreenElement ||
          document.mozFullScreenElement ||
          document.msFullscreenElement ||
          document.querySelector('.leaflet')?.classList.contains('fullscreen');

        const windowHeight = window.innerHeight;
        let newHeight;

        if (isFullscreen) {
          newHeight = windowHeight;
        } else {
          const viewElement = document.querySelector('.view.find-your-place');
          const targetForOffset = viewElement || mapElement;
          const rect = targetForOffset.getBoundingClientRect();
          const offsetTop = rect.top + window.pageYOffset;
          newHeight = windowHeight - offsetTop;

          if (newHeight < 400) {
            newHeight = 600;
          }

          if (viewElement) {
            viewElement.style.height = `${newHeight}px`;
          }
        }

        mapElement.style.height = `${newHeight}px`;
        calculateScrollableHeight();
        lMap.invalidateSize();
        requestAnimationFrame(updateOvBarPosition);
      }

      /**
       * Repositions the overview bar when the panel is full-width.
       */
      function updateOvBarPosition() {
        const ovBar = document.querySelector('.ovBar');
        const containerEl = document.querySelector('.leaflet__list-container');
        if (!ovBar || !containerEl || !containerEl.parentElement) {
          return;
        }

        const containerW = containerEl.getBoundingClientRect().width;
        const parentW = containerEl.parentElement.getBoundingClientRect().width;
        const pct = parentW ? (containerW / parentW) * 100 : 0;

        if (pct === 100) {
          const gapPx = 10;
          const parent = ovBar.offsetParent;
          if (!parent) {
            return;
          }
          const parentRect = parent.getBoundingClientRect();
          const containerRect = containerEl.getBoundingClientRect();
          const bottomPx = Math.max(0, Math.round(parentRect.bottom - containerRect.top + gapPx));

          ovBar.classList.add('ovBar--pct100');
          ovBar.style.setProperty('--ovbar-left', '0px');
          ovBar.style.setProperty('--ovbar-bottom', `${bottomPx}px`);
        } else {
          ovBar.classList.remove('ovBar--pct100');
          ovBar.style.removeProperty('--ovbar-left');
          ovBar.style.removeProperty('--ovbar-bottom');
        }
      }

      /**
       * Recalculates the scrollable content area height within the panel.
       */
      function calculateScrollableHeight() {
        const contentContainer = document.querySelector('.leaflet__content');
        const scrollableElement = document.querySelector('.leaflet__content-scrollable');
        if (!contentContainer || !scrollableElement) {
          return;
        }

        requestAnimationFrame(() => {
          contentContainer.getBoundingClientRect();

          let totalNonScrollableHeight = 60;
          Array.from(contentContainer.children).forEach(child => {
            if (child !== scrollableElement) {
              totalNonScrollableHeight += child.offsetHeight;
            }
          });

          const calculatedHeight = contentContainer.offsetHeight - totalNonScrollableHeight;
          scrollableElement.style.height = Math.max(calculatedHeight, 100) + 'px';
        });
      }

      /**
       * Placeholder for marker click integration with the content panel.
       * Extend here to open the panel when a Leaflet marker is clicked.
       */
      function setupMarkers(features, lMap) {
        // No-op by default. To wire marker clicks to getContent(), iterate
        // lMap.eachLayer() after markers are added and call getContent(id).
      }

      /**
       * Clears the content panel and resets the map view.
       */
      function closeContent() {
        const main = document.querySelector('.leaflet__content-main');
        if (main) {
          main.innerHTML = '';
        }

        resetMap(lMap);

        const top = document.querySelector('.leaflet__top');
        if (top) {
          top.style.display = 'block';
        }
        const list = document.querySelector('.leaflet__list');
        if (list) {
          list.style.display = 'block';
        }

        const listContainer = document.querySelector('.leaflet__list-container');
        if (listContainer) {
          listContainer.classList.remove('open');
          listContainer.classList.remove('table-up');
        }

        document.querySelectorAll('li.map-item-list').forEach(item => {
          item.style.display = 'flex';
        });

        return null;
      }

      function setupCloseButton() {
        document.querySelectorAll('.leaflet__content a.x-button').forEach(button => {
          button.addEventListener('click', function (e) {
            e.preventDefault();
            closeContent();
          });
        });
      }

      function setupResetViewButton() {
        const resetButton = document.querySelector('.leaflet-control-resetview a');
        if (!resetButton) {
          return;
        }
        resetButton.addEventListener('click', function (e) {
          e.preventDefault();
          resetMap(lMap);
          closeContent();
        });
      }

      function resetMap(lMap) {
        lMap.setMinZoom(1);
        lMap.setMaxZoom(18);
        const mwpx = document.querySelector('.leaflet') ? document.querySelector('.leaflet').offsetWidth - 350 : 0;
        adjustMapView(mwpx, theMap, lMap);
      }

      function decodeHtmlEntities(text) {
        const txt = document.createElement('textarea');
        txt.innerHTML = text;
        return txt.value;
      }
    });

    // -------------------------------------------------------------------------
    // Accordion — initialised on dynamically inserted content.
    // -------------------------------------------------------------------------

    function initializeAccordion(container) {
      container.querySelectorAll('.accordion .accordion-item__title').forEach(function (title) {
        title.removeEventListener('click', handleAccordionClick);
        title.addEventListener('click', handleAccordionClick);
      });
    }

    function handleAccordionClick(e) {
      e.preventDefault();
      const title = e.currentTarget;
      const teaser = title.parentNode.querySelector('.accordion-item__teaser');

      if (teaser) {
        if (teaser.style.display === 'none' || !teaser.style.display) {
          teaser.style.display = 'block';
          if (window.jQuery) {
            window.jQuery(teaser).hide().slideDown(500);
          }
        } else {
          if (window.jQuery) {
            window.jQuery(teaser).slideUp(500, function () {
              teaser.style.display = 'none';
            });
          } else {
            teaser.style.display = 'none';
          }
        }
      }

      title.classList.toggle('is-open');
      title.setAttribute('aria-expanded', title.getAttribute('aria-expanded') === 'true' ? 'false' : 'true');
    }

    // -------------------------------------------------------------------------
    // Fullscreen toggle.
    // -------------------------------------------------------------------------

    function setupFullscreenButton() {
      const fullscreenButton = document.querySelector('.leaflet-control-fullscreen-button');
      const leafletContainer = document.querySelector('.leaflet');
      if (!fullscreenButton || !leafletContainer) {
        return;
      }

      fullscreenButton.addEventListener('click', function (e) {
        e.preventDefault();
        if (leafletContainer.classList.contains('fullscreen')) {
          exitFullscreen(leafletContainer, fullscreenButton);
        } else {
          enterFullscreen(leafletContainer, fullscreenButton);
        }
      });
    }

    function enterFullscreen(container, button) {
      container.classList.add('fullscreen');
      button.classList.add('fullscreen-active');
      button.title = 'Exit Fullscreen';
      if (container.requestFullscreen) {
        container.requestFullscreen();
      } else if (container.webkitRequestFullscreen) {
        container.webkitRequestFullscreen();
      } else if (container.mozRequestFullScreen) {
        container.mozRequestFullScreen();
      } else if (container.msRequestFullscreen) {
        container.msRequestFullscreen();
      }
    }

    function exitFullscreen(container, button) {
      container.classList.remove('fullscreen');
      button.classList.remove('fullscreen-active');
      button.title = 'View Fullscreen';
      if (document.exitFullscreen) {
        document.exitFullscreen();
      } else if (document.webkitExitFullscreen) {
        document.webkitExitFullscreen();
      } else if (document.mozCancelFullScreen) {
        document.mozCancelFullScreen();
      } else if (document.msExitFullscreen) {
        document.msExitFullscreen();
      }
    }

    function handleFullscreenChange() {
      const leafletContainer = document.querySelector('.leaflet');
      const button = document.querySelector('.leaflet-control-fullscreen-button');
      if (!leafletContainer || !button) {
        return;
      }
      const isFullscreen = document.fullscreenElement ||
        document.webkitFullscreenElement ||
        document.mozFullScreenElement ||
        document.msFullscreenElement;
      if (!isFullscreen && leafletContainer.classList.contains('fullscreen')) {
        leafletContainer.classList.remove('fullscreen');
        button.classList.remove('fullscreen-active');
        button.title = 'View Fullscreen';
      }
    }

    setupFullscreenButton();
    document.addEventListener('fullscreenchange', handleFullscreenChange);
    document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
    document.addEventListener('mozfullscreenchange', handleFullscreenChange);
    document.addEventListener('MSFullscreenChange', handleFullscreenChange);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'F11' || (e.ctrlKey && e.shiftKey && e.key === 'F')) {
        e.preventDefault();
        const fullscreenButton = document.querySelector('.leaflet-control-fullscreen-button');
        if (fullscreenButton) {
          fullscreenButton.click();
        }
      }
    });
  });

  // ---------------------------------------------------------------------------
  // Drupal behaviors.
  // ---------------------------------------------------------------------------

  Drupal.behaviors.leafletSearch = {
    attach: function (context) {
      const searchInput = $('#search input[type="text"]', context);
      const listContainer = $('.leaflet__list', context);

      if (searchInput.length && listContainer.length) {
        searchInput.off('input.leafletSearch').on('input.leafletSearch', function () {
          const searchTerm = $(this).val().toLowerCase().trim();
          const listItems = listContainer.children('li');
          let visibleCount = 0;

          listItems.each(function () {
            const $item = $(this);
            if ($item.hasClass('no-results')) {
              return;
            }
            const shouldShow = searchTerm === '' || $item.text().toLowerCase().includes(searchTerm);
            if (shouldShow) {
              $item.show().removeClass('filtered-out');
              visibleCount++;
            } else {
              $item.hide().addClass('filtered-out');
            }
          });

          handleNoResultsMessage(listContainer, visibleCount, searchTerm);
        });

        searchInput.off('keydown.leafletSearch').on('keydown.leafletSearch', function (e) {
          if (e.key === 'Escape') {
            $(this).val('').trigger('input');
            $(this).focus();
          }
        });
      }

      function handleNoResultsMessage($container, visibleCount, searchTerm) {
        const $noResultsMessage = $container.find('.no-results');
        if (visibleCount === 0 && searchTerm !== '') {
          if ($noResultsMessage.length === 0) {
            $container.append($('<li class="no-results"><div style="padding: 20px; text-align: center; color: #666; font-style: italic;">No results found for "' + searchTerm + '"</div></li>'));
          }
        } else {
          $noResultsMessage.remove();
        }
      }
    }
  };

  Drupal.behaviors.leafletChevronToggle = {
    attach: function (context) {
      const buttonShow = context.querySelector('#button-show');
      const listContainer = context.querySelector('.leaflet__list-container');
      if (buttonShow) {
        buttonShow.addEventListener('click', function (e) {
          e.preventDefault();
          listContainer.classList.toggle('table-up');
        });
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
