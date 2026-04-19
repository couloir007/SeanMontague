/* jshint esversion: 11 */
/* eslint-disable no-param-reassign */
/* eslint-disable func-names */
/* jshint camelcase:false */
/* global L, Drupal, drupalSettings, console, setupOverviewBarInteraction, ray, jQuery */
// eslint-disable-next-line no-redeclare

(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * @typedef {Object} DrupalSettings
   * @property {Object} leaflet_full_page
   * @property {string} leaflet_full_page.currentDisplay
   */

  /**
   * Entry point: Waits for all page resources (DOM, images, scripts) to be fully loaded.
   * Ensures Leaflet maps are available in Drupal settings before proceeding.
   */
  window.addEventListener('load', function () {
    // Check if Leaflet maps exist in Drupal settings.
    if (!drupalSettings.leaflet) {
      console.warn('No Leaflet maps found in drupalSettings.');
      return; // Exit if no maps found.
    }

    // Get the query parameter "fips" from the current URL.
    let params = new URLSearchParams(window.location.search);
    let fips = params.get('fips');

    const selectedElement = document.querySelector('.leaflet-control-zoom');
    const target = document.querySelector('.leaflet-control-container-parent .leaflet-top.leaflet-left');

    if (selectedElement && target) {
      target.prepend(selectedElement);
    }

    // Iterate through each Leaflet map config found in drupalSettings.
    Object.keys(drupalSettings.leaflet).forEach(function (mapId) {
      const cfg = drupalSettings.leaflet[mapId];
      const lMap = cfg && cfg.lMap;
      const features = drupalSettings.leaflet[mapId].features;
      const theMap = drupalSettings.leaflet[mapId].map;

      const initialCenter = theMap.settings.center;

      let mapWidthPx = document.querySelector('.leaflet').offsetWidth - 350;

      let usaGeoJsonLayer = null;

      function addGeoJsonLayer(lMap) {
        fetch('/modules/custom/leaflet_full_page/includes/usa.geojson')
          .then(res => res.json())
          .then(data => {
            // Remove 'const' here - assign to the global variable instead
            usaGeoJsonLayer = L.geoJSON(data, {
              style: () => ({
                color: 'gray',
                weight: 2,
                fillColor: '#bfbfbf',
                fillOpacity: 0,
              }),
              onEachFeature: (feature, layer) => {
                if (feature.properties?.NAME) {
                  labelCreate(feature, layer);
                }
                layer.on('click', (e) => {
                  const stateId = feature.properties.STATE;
                  handleStateNavigation(stateId, (targetZoom) => {
                    findAndHighlightState(stateId, targetZoom);
                    getContent(stateId, 'map');
                  });
                });
              },
            });

            // Setup overview bar interaction after GeoJSON is loaded (call only if available)
            if (typeof setupOverviewBarInteraction === 'function') {
              setupOverviewBarInteraction(lMap);
            }

            usaGeoJsonLayer.addTo(lMap);

            // Check if fips should be handled now
            if (typeof attemptFipsAction === 'function') {
              attemptFipsAction();
            }
          })
          .catch(console.error);
      }

      // Initial map height adjustment.
      adjustMapHeight();

      // Adjust map height dynamically as window resizes.
      window.addEventListener('resize', () => {
        adjustMapHeight();
      });

      if (!lMap) {
        // Guard clause: Leaflet map instance must be initialized.
        console.error(`Leaflet map ${mapId} not initialized after window.load.`);
        return;
      }

      // Disable world copy jump to prevent GeoJSON overlay issues
      lMap.options.worldCopyJump = false;

      // Adjust map view based on screen width
      if (mapWidthPx < 1320) {
        adjustMapView(mapWidthPx, theMap, lMap);
      }

      /**
       * Initializes and adds marker elements to the Leaflet map.
       *
       * @param {Array|Object} features - The geographic feature data (e.g., points) to be represented as markers on the map.
       * @param lMap - The Leaflet map instance where the markers will be added.
       */
      setupMarkers(features, lMap);

      /**
       * Load GeoJSON file (e.g. boundaries or trails) asynchronously,
       * then add it as a styled layer to the Leaflet map.
       * line 37
       */
      addGeoJsonLayer(lMap);

      /**
       * Fetches the map items JSON endpoint which provides
       * data entries to populate the interactive list.
       */
      // const currentDisplay = drupalSettings.leaflet_full_page?.currentDisplay || 'default_view_name';
      // const currentPath = drupalSettings.leaflet_full_page?.currentPath || 'default_view_name';

      // Cache the map items globally to avoid repeated fetches
      let cachedMapItems = null;

      // If a FIPS is provided in the URL, mimic clicking the matching list item
      // Example URL: ?fips=1 or ?fips=01
      function attemptFipsAction() {
        if (!fips) {
          return;
        }

        // If fips is a single digit, prepend a leading zero unless it's already formatted.
        if (/^[1-9]$/.test(fips)) {
          fips = fips.padStart(2, '0');
        }

        const selectedStateId = String(fips).trim();

        // Use the same shared function as the overview bar and state clicks
        handleStateNavigation(selectedStateId, (targetZoom) => {
          // console.log('handleStateNavigation URL: ' + selectedStateId + ' - ' + targetZoom);
          findAndHighlightState(selectedStateId, targetZoom);
          getContent(selectedStateId, 'map');
        });
      }

      // ray(currentPath);

      fetch('/sites/default/files/find_your_place/interactive-us-map_mapitems.json')
        .then(res => {
          if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
          }
          return res.json();
        })
        .then(items => {
          // Store items in cache for getContent() to use
          cachedMapItems = items;

          const listContainer = document.querySelector('.leaflet__list');
          if (!listContainer) {
            console.warn('.leaflet__list container not found');
            return; // Exit if list container is missing.
          }

          /**
           * Event delegation:
           * Attach a single click listener to the list container to handle clicks
           * on any of the dynamically created `.map-item-list` list items.
           */
          listContainer.addEventListener('click', (e) => {
            const li = e.target.closest('li.map-item-list');
            if (!li) {
              return;
            } // Ignore clicks outside list items.

            const stateId = li.getAttribute('data-location-id');

            // Use the shared function with custom pan behavior
            console.log('clicked 1');

            handleStateNavigation(stateId, (targetZoom) => {
              findAndHighlightState(stateId, targetZoom);

              getContent(stateId, 'map');
            });
          });


          // Add event listeners to left/right, or prev/next
          document.querySelectorAll('.leaflet__content a.arrow-left').forEach(link => {
            link.addEventListener('click', function (e) {
              e.preventDefault();

              const $leafletContent = $('.leaflet__content');
              const nid = $leafletContent.attr('maps-nid');
              const $currentListItem = $(`#${nid}`);
              let stateId = 0;

              if ($currentListItem.is(':first-child')) {
                stateId = $('.leaflet__list li').last().attr('id');
              } else {
                stateId = $currentListItem.prev().attr('id');
              }

              // Use the shared function with custom pan behavior
              handleStateNavigation(stateId, (targetZoom) => {
                navigateToStateAndShowContent(stateId, targetZoom);
              });

              return null;
            });
          });

          document.querySelectorAll('.leaflet__content a.arrow-right').forEach(link => {
            link.addEventListener('click', function (e) {
              e.preventDefault();

              const $leafletContent = $('.leaflet__content');
              const nid = $leafletContent.attr('maps-nid');
              const $currentListItem = $(`#${nid}`);
              let stateId = 0;

              if ($currentListItem.is(':last-child')) {
                stateId = $('.leaflet__list li').first().attr('id');
              } else {
                stateId = $currentListItem.next().attr('id');
              }

              // Use the shared function with custom pan behavior
              handleStateNavigation(stateId, (targetZoom) => {
                navigateToStateAndShowContent(stateId, targetZoom);
              });

              return null;
            });
          });

          // Add event listeners to overview bar links
          document.querySelectorAll('.ovBar a.ov').forEach(link => {
            link.addEventListener('click', function (e) {
              e.preventDefault();

              const stateId = this.getAttribute('data-state');
              if (!stateId) {
                console.warn('No data-state attribute found');
                return;
              }

              // Use the shared function
              handleStateNavigation(stateId, (targetZoom) => {
                navigateToStateAndShowContent(stateId, targetZoom);
              });
            });
          });

          // Add event listener to state select dropdown
          const stateSelect = document.getElementById('state-select');
          if (stateSelect) {
            stateSelect.addEventListener('change', function(e) {
              const selectedStateId = e.target.value;

              if (!selectedStateId) {
                return;
              }

              // Use the same shared function as the overview bar and state clicks
              handleStateNavigation(selectedStateId, (targetZoom) => {
                navigateToStateAndShowContent(selectedStateId, targetZoom);
              });
            });
          }

        })
        .catch(err => {
          console.error('Failed to load map items:', err);
        });

      /* CloseButton */
      setupCloseButton();

      // Initialize reset button
      setupResetViewButton();

      function setupCloseButton() {
        document.querySelectorAll('.leaflet__content a.x-button').forEach(button => {
          button.addEventListener('click', function (e) {
            e.preventDefault();
            closeContent();
          });
        });
      }

      function setupMarkers(features) {
        features.forEach(feature => {
          if (feature.type === 'point') {
            // Extract integer from tooltip value, handling both plain integers and HTML debug output
            let tooltipInteger = null;
            if (feature.tooltip && feature.tooltip.value) {
              const match = feature.tooltip.value.toString().match(/\b(\d+)\b/);
              tooltipInteger = match ? parseInt(match[1], 10) : null;
            }

            // Store entity_id on marker directly as custom property
            feature.state_id = tooltipInteger;
          }
        });
      }

      /**
       * Placeholder function:
       * Intended to fetch and display detailed content for a given item.
       * User should implement AJAX or DOM manipulation here.
       * @param {string} theID - The ID of the content to fetch.
       * @param which - The source of click (e.g., 'map' or 'list').
       */
      function getContent(theID, which) {
        // If we have cached items, use them instead of fetching
        if (cachedMapItems) {
          processContentData(cachedMapItems, theID, which);
          return;
        }

        // Fallback to fetch if cache not available yet
        // const currentDisplay = drupalSettings.leaflet_full_page?.currentDisplay || 'default_view_name';
        // const currentPath = drupalSettings.leaflet_full_page?.currentPath || 'default_view_name';

        let contentFilter;
        if (which === 'map') {
          contentFilter = 'stateID';
        } else {
          contentFilter = 'stateID';
        }

        fetch('/sites/default/files/find_your_place/interactive-us-map_mapitems.json')
          .then(res => res.json())
          .then(data => {
            cachedMapItems = data;
            processContentData(data, theID, which);
          })
          .catch(err => console.error(err));
      }

      /**
       * Process content data from either cache or fetch
       */
      function processContentData(theData, theID, which) {
        let stateID = theID;
        let contentFilter;
        if (which === 'map') {
          contentFilter = 'stateID';
        } else {
          contentFilter = 'stateID';
        }

        // Process the data directly without additional wrapper
        let item;


        if (which === 'map') {
          let items = theData.filter(item => item.stateID === stateID);

          // use filteredItems as needed
          if (items.length > 1) {
            document.querySelectorAll('li.map-item-list').forEach(el => el.style.display = 'none');

            items.forEach(item => {
              // document.querySelector('.leaflet__list-container').classList.remove('open');
              document.getElementById(item.stateID).style.display = 'flex';
              document.querySelector('.leaflet__top h1').style.display = 'none';
              document.querySelector('.leaflet__top h3').innerHTML = item.title;
              document.querySelector('.leaflet__top h3').style.display = 'block';
            });
          } else {
            item = items[0];
            items = null;
            stateID = item.stateID;
          }
        } else {
          item = theData.find(obj => Number(obj.stateID) === Number(stateID));
        }

        let findMapItem = theData.find(obj => String(obj.contentID).trim() === 'findMap');

        if (item) {
          if (stateID != null) {
                const images = item.field_edan_object;

                const scrollableContainer = document.querySelector('.leaflet__content-main');
                const slickContainer = document.querySelector('.edan-viewer__slick-container');
                scrollableContainer.innerHTML = ''; // Clear previous content
                slickContainer.innerHTML = ''; // Clear previous content

                slickContainer.insertAdjacentHTML('beforeend', '<div id="thumbs" class="edan_object_thumbs slider-thumbs slider-nav"></div>');
                const thumbs = document.querySelector('#thumbs');

                // Loop through images array to create thumbnails
                if (Array.isArray(images)) {
                  images.forEach((image, key) => {
                    const irn = image.irn || '';
                    const thumbnailHTML = `
                      <figure class="edan_object_thumbnail thumb-${key}">
                        <a href="${image.idsId}" irn="${irn || ''}" data-credit="${image.credit || ''}" target="_blank" data="${image.data || ''}" type="${image.type || ''}">
                          <img src="${image.thumb}" alt="${image.alt || ''}">
                        </a>
                      </figure>
                    `;
                    thumbs.insertAdjacentHTML('beforeend', thumbnailHTML);
                  });
                }
                document.querySelector('.leaflet__content-title').textContent = item.title;
                document.querySelector('.leaflet__content-sub-title').innerHTML = decodeHtmlEntities(item.field_sub_title || '');
                // scrollableContainer.insertAdjacentHTML('afterbegin', decodeHtmlEntities(item.field_listing_image_media || ''));
                scrollableContainer.insertAdjacentHTML('beforeend', decodeHtmlEntities(item.field_body || ''));
                // scrollableContainer.insertAdjacentHTML('beforeend', decodeHtmlEntities(item.field_content || ''));
                scrollableContainer.insertAdjacentHTML('beforeend', decodeHtmlEntities(item.field_object_content || ''));

                const ul = document.querySelector('ul.find_map');

                if (ul && findMapItem?.pdf) {
                  // Inserts the HTML string right AFTER the <ul class="find_map">...</ul>
                  ul.insertAdjacentHTML('afterend', findMapItem.pdf);
                }

                // Check if first element is embedded-entity and remove margin-top if so
                const firstChild = scrollableContainer.firstElementChild;
                if (firstChild && firstChild.classList.contains('embedded-entity')) {
                  firstChild.style.marginTop = '0';
                }

                // After inserting content that may contain mads-viewer elements,
                // trigger the style injection
                if (window.injectMadsViewerStyles) {
                  // Add a small delay to ensure the mads-viewer elements are fully rendered
                  setTimeout(() => {
                    window.injectMadsViewerStyles();
                    // After loading your accordion content dynamically
                    // Drupal.attachBehaviors(document);
                    initializeAccordion(scrollableContainer);

                  }, 150);
                }

                document.querySelectorAll('.leaflet__content-textarea a').forEach(link => {
                  link.setAttribute('target', '_blank');
                });

                const theWidth = getTheWidth();

                if (theWidth === '100%') {
                  if (item.image_url_mobile) {
                    document.querySelector('.leaflet__content-main-image').src = item.image_url_mobile;
                  }
                } else {
                  if (item.image_url_big) {
                    document.querySelector('.leaflet__content-main-image').src = item.image_url_big;
                  }
                }

                if (item.credit) {
                  const figcaption = document.querySelector('.leaflet__content-main-figure figcaption');
                  figcaption.innerHTML = `<strong>Credit: </strong>${item.credit}`;
                  figcaption.style.display = 'block';
                }

                document.querySelector('.leaflet__content').setAttribute('maps-nid', stateID);
                document.querySelector('.leaflet__content-scrollable').scrollTo({ top: 0, behavior: 'smooth' });

                const scrollableElement = document.querySelector('.leaflet__content-scrollable');
                scrollableElement.scrollTo({ top: 0, behavior: 'smooth' });

                document.querySelector('.leaflet__list-container').classList.add('open');
                document.querySelector('.leaflet__list-container').classList.add('table-up');

                calculateScrollableHeight();

                // Add a small delay to ensure container is rendered and CSS transition completes
                setTimeout(() => {
                  calculateScrollableHeight();
                }, 800); // Match or exceed the CSS transition duration (750ms)

                // Add another calculation after the longer delay for any remaining layout shifts
                setTimeout(() => {
                  if (typeof setHeight === 'function') {
                    calculateScrollableHeight();
                  }
                }, 1200); // Increased delay

                // Defer EDAN iframe loading to avoid blocking the UI
                const edanViewer = document.querySelector('#edan_viewer');
                const edanViewerWrapper = document.querySelector('.edan-viewer--wrapper');
                let idsId = (images && images.length > 0) ? images[0].idsId : null;

                // Load EDAN viewer after a delay so the panel opens quickly
                setTimeout(() => {
                  if (edanViewer && (idsId != null)) {
                    edanViewer.onerror = function() {
                      console.error('Failed to load EDAN viewer for ID:', idsId);
                      // Optionally display user-friendly error message
                    };

                    edanViewer.src = '/leaflet-full-page/edan-proxy?id=' + encodeURIComponent(idsId);
                    edanViewerWrapper.style.display = 'block';
                  } else if (edanViewer) {
                    edanViewer.src = '';
                    edanViewerWrapper.style.display = 'none';
                  }
                }, 100); // Small delay to let the panel open first
                // Add click event listener to thumbnail links
                document.querySelectorAll('.edan_object_thumbnail a').forEach(link => {
                  link.addEventListener('click', function (e) {
                    e.preventDefault();

                    const idsId = this.getAttribute('href');
                    const edanViewer = document.querySelector('#edan_viewer');

                    if (edanViewer && idsId) {
                      edanViewer.src = '/leaflet-full-page/edan-proxy?id=' + encodeURIComponent(idsId);
                    }
                  });
                });


              // setTimeout(() => {
              //   if (typeof setSliderNav === 'function') {
              //     setSliderNav('thumbs');
              //   }
              // }, 100); // Add a small delay to ensure container is rendered
          }
        }
      }

      function setSliderNav(thumbsID) {
        const $carousel = $('.edan_object_thumbs.slider-' + thumbsID + '.slider-nav');

        // Guard: ensure the element exists in the DOM
        if ($carousel.length === 0) {
          console.warn(`Carousel element not found for ID: ${thumbsID}`);
          return;
        }

        // Additional guard: check if the carousel element is properly rendered
        if (!$carousel[0] || !$carousel[0].parentElement) {
          console.warn(`Carousel element exists but is not properly attached to DOM`);
          return;
        }

        try {
          $carousel.slick({
            slidesToShow: 6,
            slidesToScroll: 3,
            infinite: false,
            dots: true,
          });

          // Multiple refresh attempts to ensure proper sizing
          setTimeout(() => $carousel.slick('refresh'), 100);
          setTimeout(() => $carousel.slick('refresh'), 500);
        } catch (error) {
          console.error(`Error initializing slick carousel for ${thumbsID}:`, error);
        }
      }

      function adjustMapView(mapWidthPx, theMap, lMap) {

        const containerEl = document.querySelector('.leaflet__list-container');
        const parentEl = containerEl.parentElement;

        const containerW = containerEl.getBoundingClientRect().width;
        const parentW = parentEl.getBoundingClientRect().width;

        const pct = parentW ? (containerW / parentW) * 100 : 0;

        let theTargetZoom = theMap.settings.zoom;

        let theTargetLat = initialCenter.lat;
        let theTargetLon = initialCenter.lon;
        if(pct === 100) {
          theTargetZoom = 4;
          theTargetLat = 30;
          theTargetLon = -96;

          const width = window.innerWidth;
          if (width < 800) {
            theTargetZoom = 3;
            theTargetLat = 37;
            theTargetLon = -96;
          }
        }

        // Fly to the adjusted center with the target zoom
        lMap.flyTo([theTargetLat, theTargetLon], theTargetZoom, {
          duration: 1.0, // Animation duration in seconds
        });

        // console.log(initialCenter);

        lMap.invalidateSize();
      }

      function getTheWidth() {
        const leafletEl = document.querySelector('.leaflet');
        const containerEl = document.querySelector('.leaflet__list-container');

        leafletEl.style.display = 'none';
        const theGetWidth = getComputedStyle(containerEl).width;
        leafletEl.style.display = '';

        return theGetWidth;
      }

      /**
       * Dynamically adjusts the Leaflet map container height to fill available viewport space.
       * Ensures map resizes correctly on window resize.
       */
      function adjustMapHeight() {
        const mapElement = document.getElementById(mapId);
        if (!mapElement) {
          return;
        } // Guard: element must exist.

        // Check if we're in fullscreen mode
        const isFullscreen = document.fullscreenElement ||
          document.webkitFullscreenElement ||
          document.mozFullScreenElement ||
          document.msFullscreenElement ||
          document.querySelector('.leaflet').classList.contains('fullscreen');

        const windowHeight = window.innerHeight;
        let newHeight;

        if (isFullscreen) {
          // In fullscreen, use the full viewport height
          newHeight = windowHeight;
        } else {
          // Normal mode, subtract offset
          const viewElement = document.querySelector('.view.find-your-place');
          const targetForOffset = viewElement || mapElement;
          const rect = targetForOffset.getBoundingClientRect();
          const offsetTop = rect.top + window.pageYOffset;
          newHeight = windowHeight - offsetTop;

          // Extra safety check for Safari on iPad
          if (newHeight < 400) {
            newHeight = 600; // Minimum usable height
          }

          if (viewElement) {
            viewElement.style.height = `${newHeight}px`;
          }
        }

        mapElement.style.height = `${newHeight}px`;
        calculateScrollableHeight();
        lMap.invalidateSize(); // Notify Leaflet to recalc map size.

        // IMPORTANT: run after layout changes
        requestAnimationFrame(updateOvBarPosition);
      }

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

          // ovBar's absolute positioning is relative to its offsetParent
          const parent = ovBar.offsetParent;
          if (!parent) {
            return;
          }

          const parentRect = parent.getBoundingClientRect();
          const containerRect = containerEl.getBoundingClientRect();

          // bottom (in parent's coordinate space) so ovBar sits above containerEl's TOP
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

      // Function to calculate scrollable content height
      function calculateScrollableHeight() {
        const contentContainer = document.querySelector('.leaflet__content');
        const scrollableElement = document.querySelector('.leaflet__content-scrollable');
        
        // Check if required elements exist
        if (!contentContainer || !scrollableElement) {
          return;
        }
        
        // Wait for next frame to ensure all layout is complete
        requestAnimationFrame(() => {
          // Force layout recalculation
          contentContainer.getBoundingClientRect();
          
          // Get all non-scrollable content heights
          let totalNonScrollableHeight = 60; // Your fixed padding/margin
          
          // Find all direct children of content container except the scrollable one
          Array.from(contentContainer.children).forEach(child => {
            if (child !== scrollableElement) {
              totalNonScrollableHeight += child.offsetHeight;
            }
          });
          
          const containerHeight = contentContainer.offsetHeight;
          const calculatedHeight = containerHeight - totalNonScrollableHeight;
          
          // Ensure minimum height
          const finalHeight = Math.max(calculatedHeight, 100);
          
          scrollableElement.style.height = finalHeight + 'px';
        });
      }
      // End scrollable height

      function decodeHtmlEntities(text) {
        const txt = document.createElement('textarea');
        txt.innerHTML = text;
        return txt.value;
      }

      // Map Interations - Start

      let stateLabelMarkers = []; // Add this to track label markers
      let previouslyClickedLayer = null;

      // Function to reset any filled state
      function resetStateFill() {
        previouslyClickedLayer.setStyle({
          fillOpacity: 0,
        });
        previouslyClickedLayer = null;
      }

      // Listen for zoom changes
      lMap.on('zoomend', () => {
        const zoom = parseInt(lMap.getZoom());

        stateLabelMarkers.forEach(item => {
          if (zoom >= 7 || zoom <= 4) {
            // Hide labels at zoom 7 and above
            if (item.type === 'marker' && lMap.hasLayer(item.marker)) {
              lMap.removeLayer(item.marker);
            } else if (item.type === 'tooltip') {
              item.layer.closeTooltip();
            }
          } else {
            // Show labels below zoom 7
            if (item.type === 'marker' && !lMap.hasLayer(item.marker)) {
              item.marker.addTo(lMap);
            } else if (item.type === 'tooltip') {
              item.layer.openTooltip();
            }
          }
        });
      });

      function closeContent() {
        const scrollableContainer = document.querySelector('.leaflet__content-main');
        const slickContainer = document.querySelector('.edan-viewer__slick-container');
        scrollableContainer.innerHTML = ''; // Clear previous content
        slickContainer.innerHTML = ''; // Clear previous content

        resetMap(lMap, previouslyClickedLayer);

        document.querySelector('.leaflet__top').style.display = 'block';
        document.querySelector('.leaflet__list').style.display = 'block';

        document.querySelector('.leaflet__list-container').classList.remove('open');
        document.querySelector('.leaflet__list-container').classList.remove('table-up');
        document.querySelectorAll('li.map-item-list').forEach(item => item.style.display = 'flex');

        return null;
      }


      function labelCreate(feature, layer) {
        const stateName = feature.properties.NAME;
        const stateID = feature.properties.STATE; // Get the state ID

        layer.on('mouseover', () => {
          if (previouslyClickedLayer !== layer) {
            layer.setStyle({
              fillOpacity: 0.65,
            });
          }
        });

        layer.on('mouseout', () => {
          if (previouslyClickedLayer !== layer) {
            layer.setStyle({
              fillOpacity: 0,
            });
          }
        });

        let labelPosition;

        if (window.manualPositions && window.manualPositions[stateName]) {
          labelPosition = L.latLng(window.manualPositions[stateName]);
        } else {
          // Use the default tooltip center for other states
          layer.bindTooltip(stateName, {
            permanent: true,
            direction: 'center',
            className: 'state-label-text',
          });

          // Add click event to tooltip-based labels
          layer.on('click', function () {
            // Use the shared function with custom pan behavior
            console.log('clicked 1');
            handleStateNavigation(stateID, (targetZoom) => {
              findAndHighlightState(stateID, targetZoom, theMap);
              getContent(stateID, 'map');
            });
          });

          stateLabelMarkers.push({ type: 'tooltip', layer: layer, stateID: stateID });
          return; // Skip creating a marker for these states
        }

        // Create marker only for manually positioned states
        const labelMarker = L.marker(labelPosition, {
          icon: L.divIcon({
            className: 'state-label-marker',
            html: stateName,
            iconSize: null,
          }),
        });

        labelMarker.on('mouseover', () => {
          if (previouslyClickedLayer !== layer) {
            layer.setStyle({
              fillOpacity: 0.65,
            });
          }
        });

        labelMarker.on('mouseout', () => {
          if (previouslyClickedLayer !== layer) {
            layer.setStyle({
              fillOpacity: 0,
            });
          }
        });

        // Add click event to marker-based labels
        labelMarker.on('click', function () {
          // Use the shared function with custom pan behavior
          console.log('clicked 3');
          handleStateNavigation(stateID, (targetZoom) => {
            findAndHighlightState(stateID, targetZoom, theMap);
            getContent(stateID, 'map');
          });
        });

        labelMarker.addTo(lMap);

        // Track markers for zoom-based visibility and store stateID
        stateLabelMarkers.push({ type: 'marker', marker: labelMarker, stateID: stateID });

        layer.bindPopup(feature.properties.NAME);
        layer.on('click', () => {
          // Get the state ID from properties to match overview bar logic
          const stateId = feature.properties.STATE;

          // Use the shared function with custom pan behavior
          handleStateNavigation(stateId, (targetZoom) => {
            findAndHighlightState(stateId, targetZoom);
            getContent(feature.properties.STATE, 'map');
          });
        });
      }

      // Create a shared function to handle state navigation
      function handleStateNavigation(stateId, customPanFunction = null) {
        // console.log('handleStateNavigation:', stateId);

        const containerEl = document.querySelector('.leaflet__list-container');
        const parentEl = containerEl.parentElement;

        const containerW = containerEl.getBoundingClientRect().width;
        const parentW = parentEl.getBoundingClientRect().width;

        const pct = parentW ? (containerW / parentW) * 100 : 0;

        // console.log('pct:', pct);

        let theTargetZoom = 7;
        if(pct === 100) {
          theTargetZoom = 6;
        }

        // Define arrays for different state categories
        // const easternStates = ['09', '10', '11', '24', '25', '33', '34', '42', '44', '50', '15'];

        const westernStates = {
          'California': '06',
          'Idaho': '16',
          'Michigan': '26',
          'Minnesota': '27',
          'Montana': '30',
          'Nevada': '32',
          'Texas': '48'
        };

        const smallStates = {
          'Connecticut': '09',
          'Delaware': '10',
          'Maryland': '24',
          'Massachusetts': '25',
          'New Hampshire': '33',
          'New Jersey': '34',
          'Rhode Island': '44',
          'Vermont': '50',
          'Washington D.C.': '11',
        };

        // Temporarily remove max zoom restriction for navigation
        lMap.setMaxZoom(18);
        const width = window.innerWidth;

        if (stateId === '60' || stateId === '66') {
          // Use custom pan function or default panToStateById
          theTargetZoom = 8;
        } else if (stateId === '02') {
          theTargetZoom = 4;
          if(pct === 100) {
            theTargetZoom = 3;
          }
        } else if (Object.values(westernStates).includes(String(stateId))) {
          // Western States
          theTargetZoom = 6;

          if(pct === 100) {
            theTargetZoom = 5;
          }
        } else if (Object.values(smallStates).includes(String(stateId))) {
          theTargetZoom = 8;

          // console.log('width:', width);
          // setRay('width: ' + width);

          if (
            (width < 1750 && (stateId == '25' || stateId == '24')) ||
            (width < 1110 && stateId == '09')
          ) { // Massachusetts
            theTargetZoom = 7;
          }

          if(pct === 100) {
            theTargetZoom = 8;
          }
        }

        // console.log('customPanFunction:', customPanFunction);

        // Pan and Zoom
        if (customPanFunction) {
          customPanFunction(theTargetZoom);
        } else {
          lMap.setMinZoom(theTargetZoom);
          lMap.setMaxZoom(theTargetZoom);
          panToStateById(lMap, stateId);
        }
      }

      function navigateToStateAndShowContent(stateId, targetZoom) {
        findAndHighlightState(stateId, targetZoom);
        getContent(stateId, 'map');
      }

      function panToStateById(lMap, stateId) {
        if (!usaGeoJsonLayer) {
          console.warn('GeoJSON layer not loaded yet');
          return;
        }

        let targetLayer = null;

        usaGeoJsonLayer.eachLayer(function (layer) {
          if (layer.feature && layer.feature.properties) {
            const props = layer.feature.properties;

            // Match against the STATE property from your GeoJSON
            if (props.STATE === stateId) {
              targetLayer = layer;
              return false; // Break out of eachLayer
            }
          }
        });

        if (targetLayer) {
          // Use the same approach as your existing click handler
          lMap.fitBounds(targetLayer.getBounds());
          lMap.panTo(targetLayer.getBounds().getCenter());

          // Optional: Highlight the state temporarily
          highlightState(targetLayer);
        } else {
          console.warn(`State with ID ${stateId} not found`);
        }
      }

      function highlightState(layer) {
        // Reset previously clicked layer
        if (previouslyClickedLayer && previouslyClickedLayer !== layer) {
          resetStateFill();
        }

        layer.setStyle({
          fillOpacity: 0.65,
        });

        // Store reference to current layer
        previouslyClickedLayer = layer;
      }

        // New standalone function to handle state layer finding and highlighting
      function findAndHighlightState(stateId, targetZoom) {
        // Check if GeoJSON layer is loaded
        if (!usaGeoJsonLayer) {
          console.warn('GeoJSON layer not loaded yet');
          return null;
        }

        let targetLayer = null;

        // Find the correct layer for this stateId
        usaGeoJsonLayer.eachLayer(function (layer) {
          if (layer.feature && layer.feature.properties) {
            const props = layer.feature.properties;
            // Match against the STATE property from your GeoJSON
            if (props.STATE === stateId) {
              targetLayer = layer;
            }
          }
        });

        if (targetLayer) {
          panStateToVisibleCenter(targetLayer, lMap, targetZoom, stateId);
          highlightState(targetLayer);
        } else {
          console.warn(`State with ID ${stateId} not found`);
        }

        return targetLayer;
      }

      /**
       * Pans the map to center a specific state layer within the visible left area.
       *
       * @param {L.Layer} layer - The Leaflet layer (state) that was clicked.
       * @param {L.Map} map - The Leaflet map instance.
       * @param {number} targetZoom - The target zoom level for the pan.
       * @param {string} stateId - The ID of the state being centered.
       */
      function panStateToVisibleCenter(layer, map, targetZoom, stateId) {
        if (!layer || !map || typeof map.project !== 'function') {
          console.error('panStateToVisibleCenter: Invalid map instance provided.', map);
          return;
        }

        const containerEl = document.querySelector('.leaflet__list-container');
        const parentEl = containerEl.parentElement;
        const containerW = containerEl.getBoundingClientRect().width;
        const parentW = parentEl.getBoundingClientRect().width;
        const pct = parentW ? (containerW / parentW) * 100 : 0;

        const stateCenter = layer.getBounds().getCenter();
        const viewportWidth = window.innerWidth;
        const zoom = targetZoom;

        let targetLatLng;

        // Case 1: Full-width panel (pct >= 100) - Apply vertical shift
        if (pct >= 100) {
          // State-specific vertical offset groups (from calculateAdjustedCenterForZoom)
          const offsets = {
            275: ['15', '60', '78', '72'], // Hawaii, Samoa, VI, PR
            250: ['48', '22'],             // Texas, Louisiana
            415: ['28', '01', '12', '13'], // MS, AL, FL, GA
            400: ['05', '37', '45', '47'], // AR, NC, SC, TN
            375: ['32', '35', '04', '40', '10'], // NV, NM, AZ, OK, DE
            360: ['08'],                   // CO
            350: ['42', '34', '24', '54', '51', '21', '39', '18', '29'], // PA, NJ, MD, WV, VA, KY, OH, IN, MO
            325: ['25', '33', '50', '56', '41', '31', '49'], // MA, NH, VT, WY, OR, NE, UT
            290: ['23', '27', '30'],       // ME, MN, MT
            270: ['53', '38'],             // WA, ND
          };

          let shiftLatPixels = 275; // Default vertical shift in pixels
          const width = window.innerWidth;

          // Convert state center to pixel point, adjust Y, and convert back to LatLng
          const statePoint = map.project(stateCenter, zoom);
          const offsetPoint = L.point(statePoint.x, statePoint.y + (shiftLatPixels / 2));
          targetLatLng = map.unproject(offsetPoint, zoom);
        }

        // Case 2: Partial panel (pct < 100) - Apply horizontal shift
        else {
          // Overlay is 60% of viewport width + 20px offset from right.
          const overlayWidth = viewportWidth * 0.6;
          const visibleWidth = viewportWidth - overlayWidth - 20;
          const targetCenterX = visibleWidth / 2;

          // Perform pixel-offset calculation for horizontal centering.
          const statePoint = map.project(stateCenter, zoom);

          // Calculate how far to shift. Map center is always viewportWidth / 2.
          const currentCenterX = viewportWidth / 2;
          const pixelOffset = currentCenterX - targetCenterX;

          // Apply the offset (shifting the map right moves the content left).
          const offsetPoint = L.point(statePoint.x + pixelOffset, statePoint.y);
          targetLatLng = map.unproject(offsetPoint, zoom);
        }

        // 3. Execute the pan/fly animation.
        map.flyTo([targetLatLng.lat, targetLatLng.lng], zoom, {
          duration: 1.0,
        });

        // 4. Trigger the overlay opening (standard in your current file).
        if (containerEl) {
          containerEl.classList.add('open');
        }
      }


      // Setup Reset View Button
      function setupResetViewButton() {
        const resetButton = document.querySelector('.leaflet-control-resetview a');
        if (!resetButton) {
          return;
        }

        resetButton.addEventListener('click', function (e) {
          e.preventDefault();
          resetMap(lMap, previouslyClickedLayer);
          closeContent();
        });
      }

      function resetMap(lMap, previouslyClickedLayer) {
        lMap.setMinZoom(1);
        lMap.setMaxZoom(18);

        // Unhighlight any currently highlighted state
        if (previouslyClickedLayer) {
          resetStateFill();
        }

        let mapWidthPx = document.querySelector('.leaflet').offsetWidth - 350;
        adjustMapView(mapWidthPx, theMap, lMap);
      }
    });

    /**
     * Initialize accordion functionality for dynamically inserted content
     */
    function initializeAccordion(container) {
      const accordionTitles = container.querySelectorAll('.accordion .accordion-item__title');

      accordionTitles.forEach(function (title) {
        // Remove any existing event listeners to prevent duplicates
        title.removeEventListener('click', handleAccordionClick);
        // Add the click event listener
        title.addEventListener('click', handleAccordionClick);
      });
    }

    /**
     * Handle accordion click events
     */
    function handleAccordionClick(e) {
      e.preventDefault();

      const title = e.currentTarget;
      const teaser = title.parentNode.querySelector('.accordion-item__teaser');

      if (teaser) {
        // Toggle visibility with slide animation
        if (teaser.style.display === 'none' || !teaser.style.display) {
          teaser.style.display = 'block';
          // Use jQuery for slide animation if available, otherwise just show
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

      // Toggle classes and aria attributes
      title.classList.toggle('is-open');

      if (title.getAttribute('aria-expanded') === 'true') {
        title.setAttribute('aria-expanded', 'false');
      } else {
        title.setAttribute('aria-expanded', 'true');
      }
    }

    // Setup Fullscreen Button
    function setupFullscreenButton() {
      const fullscreenButton = document.querySelector('.leaflet-control-fullscreen-button');
      const leafletContainer = document.querySelector('.leaflet');

      if (!fullscreenButton || !leafletContainer) {
        return;
      }

      fullscreenButton.addEventListener('click', function (e) {
        e.preventDefault();

        if (leafletContainer.classList.contains('fullscreen')) {
          // Exit fullscreen
          exitFullscreen(leafletContainer, fullscreenButton);
        } else {
          // Enter fullscreen
          enterFullscreen(leafletContainer, fullscreenButton);
        }
      });
    }

    // Enter fullscreen mode
    function enterFullscreen(container, button) {
      // Add CSS fullscreen class
      container.classList.add('fullscreen');
      button.classList.add('fullscreen-active');
      button.title = 'Exit Fullscreen';

      // Request browser fullscreen API
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

    // Exit fullscreen mode
    function exitFullscreen(container, button) {
      // Remove CSS fullscreen class
      container.classList.remove('fullscreen');
      button.classList.remove('fullscreen-active');
      button.title = 'View Fullscreen';

      // Exit browser fullscreen API
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

    // Handle browser fullscreen change events (e.g., when user presses ESC)
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
        // Browser exited fullscreen (e.g., ESC key), sync our state
        leafletContainer.classList.remove('fullscreen');
        button.classList.remove('fullscreen-active');
        button.title = 'View Fullscreen';
      }
    }

    // Initialize button
    setupFullscreenButton();

    // Listen for fullscreen change events
    document.addEventListener('fullscreenchange', handleFullscreenChange);
    document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
    document.addEventListener('mozfullscreenchange', handleFullscreenChange);
    document.addEventListener('MSFullscreenChange', handleFullscreenChange);

    // Optional: Handle F11 key for fullscreen toggle
    document.addEventListener('keydown', function (e) {
      if (e.key === 'F11' || (e.ctrlKey && e.shiftKey && e.key === 'F')) {
        e.preventDefault();
        const fullscreenButton = document.querySelector('.leaflet-control-fullscreen-button');
        if (fullscreenButton) {
          fullscreenButton.click();
        }
      }
    });
    function setRay(string) {
      ray(string);
    }
  });

  Drupal.behaviors.leafletSearch = {
    attach: function (context) {
      const searchInput = $('#search input[type="text"]', context);
      const listContainer = $('.leaflet__list', context);

      if (searchInput.length && listContainer.length) {
        searchInput.off('input.leafletSearch').on('input.leafletSearch', function() {
          const searchTerm = $(this).val().toLowerCase().trim();
          const listItems = listContainer.children('li');

          let visibleCount = 0;

          listItems.each(function() {
            const $item = $(this);

            // Skip no-results message
            if ($item.hasClass('no-results')) {
              return;
            }

            let shouldShow = false;

            if (searchTerm === '') {
              shouldShow = true;
            } else {
              const itemText = $item.text().toLowerCase();
              shouldShow = itemText.includes(searchTerm);
            }

            if (shouldShow) {
              $item.show().removeClass('filtered-out');
              visibleCount++;
            } else {
              $item.hide().addClass('filtered-out');
            }
          });

          // Handle no results message
          handleNoResultsMessage(listContainer, visibleCount, searchTerm);
        });

        // Clear search on Escape key
        searchInput.off('keydown.leafletSearch').on('keydown.leafletSearch', function(e) {
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
            const $noResults = $('<li class="no-results"><div style="padding: 20px; text-align: center; color: #666; font-style: italic;">No results found for "' + searchTerm + '"</div></li>');
            $container.append($noResults);
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