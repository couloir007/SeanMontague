(function (Drupal, drupalSettings) {
  'use strict';

  // Utility to decode HTML entities
  function decodeHtmlEntities(text) {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = text;
    return textarea.value;
  }

  function setupMarkers(features, lMap) {
    features.forEach(feature => {
      if (feature.type === 'point') {
        const marker = L.marker([feature.lat, feature.lon]);
        marker.entity_id = feature.entity_id;

        marker.on('click', () => {
          getContent(marker.entity_id, 'map');
          getNewLatLng(marker.entity_id);
        });

        marker.addTo(lMap);
      }
    });
  }

  function addGeoJsonLayer(lMap) {
    fetch('/sites/default/files/KingdomTrails.geojson')
      .then(res => res.json())
      .then(data => {
        const layer = L.geoJSON(data, {
          style: () => ({
            color: 'green',
            weight: 2,
            fillOpacity: 0.2,
          }),
          onEachFeature: (feature, layer) => {
            if (feature.properties?.name) {
              layer.bindPopup(feature.properties.name);
              layer.on('click', () => {
                lMap.panTo(layer.getBounds().getCenter());
              });
            }
          },
        });
        layer.addTo(lMap);
      })
      .catch(console.error);
  }

  function populateList(items) {
    const listContainer = document.querySelector('.leaflet__list');
    if (!listContainer) {
      return;
    }

    listContainer.innerHTML = '';
    items.forEach(item => {
      const li = document.createElement('li');
      li.tabIndex = -1;
      li.id = item.id;
      li.className = 'map-item-list';

      const thumb = document.createElement('div');
      thumb.className = 'thumb';
      thumb.style.backgroundImage = `url(${item.field_media_image})`;

      const info = document.createElement('div');
      info.className = 'info';

      const h1 = document.createElement('h1');
      h1.style.fontSize = '16px';
      h1.style.fontWeight = 'bold';
      h1.textContent = decodeHtmlEntities(item.label || '');

      const teaser = document.createElement('div');
      teaser.className = 'teaser';
      teaser.textContent = decodeHtmlEntities(item.field_subtitle || '');

      const pubDate = document.createElement('date');
      pubDate.className = 'pub-date';
      pubDate.textContent = item.field_publication_date || '';

      info.append(h1, teaser, pubDate);
      li.append(thumb, info);
      listContainer.appendChild(li);
    });
  }

  function attachListClickHandler() {
    const listContainer = document.querySelector('.leaflet__list');
    if (!listContainer) {
      return;
    }

    listContainer.addEventListener('click', e => {
      const li = e.target.closest('li.map-item-list');
      if (!li) {
        return;
      }

      const contentID = li.id;
      getContent(contentID);
      getNewLatLng(contentID);
    });
  }

  function setupCloseButton(theMap, lMap) {
    document.querySelectorAll('.leaflet__content a.x-button').forEach(button => {
      button.addEventListener('click', e => {
        e.preventDefault();
        lMap.setView(theMap.settings.center, theMap.settings.zoom);
        lMap.invalidateSize();

        const listContainer = document.querySelector('.leaflet__list-container');
        listContainer?.classList.remove('open', 'container-up');

        const leafletContent = document.querySelector('.leaflet__content');
        if (leafletContent) {
          leafletContent.style.display = 'none';
        }

        document.querySelectorAll('li.map-item-list').forEach(item => item.style.display = 'block');
        document.querySelector('.leaflet__top h1').style.display = 'block';
        document.querySelector('.leaflet__top h3').style.display = 'none';
      });
    });
  }

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

    // Iterate through each Leaflet map config found in drupalSettings.
    Object.keys(drupalSettings.leaflet).forEach(function (mapId) {
      const cfg = drupalSettings.leaflet[mapId];
      const lMap = cfg && cfg.lMap;
      const features = drupalSettings.leaflet[mapId]['features'];
      const theMap = drupalSettings.leaflet[mapId]['map'];
      let mapWidthPx = document.querySelector('.leaflet').offsetWidth - 350;
      const US_CENTER_LON = -95.867;

      if (mapWidthPx < 1320) {
        adjustMapView(mapWidthPx, theMap, lMap);
      }

      // Guard clause: Leaflet map instance must be initialized.
      if (!lMap) {
        console.error(`Leaflet map ${mapId} not initialized after window.load.`);
        return;
      }

      features.forEach(feature => {
        // console.log(feature);
        if (feature.type === 'point') {
          // Create marker for feature
          const marker = L.marker([feature.lat, feature.lon]);

          // Store entity_id on marker directly as custom property
          marker.entity_id = feature.entity_id;

          // Add click listener
          marker.on('click', () => {
            // console.log('Clicked entity_id:', marker.entity_id);
            getContent(marker.entity_id, 'map');
            // Your logic here, e.g., getContent(marker.entity_id);
            getNewLatLng(marker.entity_id);
          });

          // Add marker to the map
          marker.addTo(lMap);
        }
      });

      /**
       * Load GeoJSON file (e.g. boundaries or trails) asynchronously,
       * then add it as a styled layer to the Leaflet map.
       */
      fetch('/sites/default/files/KingdomTrails.geojson')
        .then(res => res.json())
        .then(geojsonData => {
          const geoJsonLayer = L.geoJSON(geojsonData, {
            style: () => {
              return ({
                color: 'green',
                weight: 2,
                fillOpacity: 0.2,
              });
            },
            onEachFeature: (feature, layer) => {
              if (feature.properties && feature.properties.name) {
                layer.bindPopup(feature.properties.name);

                layer.on('click', function () {
                  console.log('Feature clicked:', feature.properties.name);

                  // Open popup when feature is clicked
                  // layer.openPopup();

                  // Pan map to center on the clicked feature
                  lMap.panTo(layer.getBounds().getCenter());
                });
              }
            },
          });
          geoJsonLayer.addTo(lMap);
        })
        .catch(err => {
          console.error('Error loading GeoJSON:', err);
        });

      /**
       * Fetches the map items JSON endpoint which provides
       * data entries to populate the interactive list.
       */
      const currentDisplay = drupalSettings.leaflet_full_page?.currentDisplay || 'default_view_name';
      fetch(`/${currentDisplay}_mapitems`)
        .then(res => {
          if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
          }
          return res.json();
        })
        .then(items => {
          const listContainer = document.querySelector('.leaflet__list');
          if (!listContainer) {
            console.warn('.leaflet__list container not found');
            return; // Exit if list container is missing.
          }

          listContainer.innerHTML = ''; // Clear existing list items.

          // Create and append each item in the fetched list data.
          items.forEach(item => {
            const li = document.createElement('li');
            li.tabIndex = -1;                      // Accessibility: allow focus.
            li.id = item.id;                      // Set ID for reference.
            li.classList.add('map-item-list');   // Styling hook.

            // Thumbnail with background image from the item.
            const thumb = document.createElement('div');
            thumb.classList.add('thumb');
            thumb.style.backgroundImage = `url(${item.field_media_image})`;

            // Container for textual info.
            const info = document.createElement('div');
            info.classList.add('info');

            // Title styled with bold font.
            const h1 = document.createElement('h1');
            h1.style.fontSize = '16px';
            h1.style.fontWeight = 'bold';
            h1.textContent = decodeHtmlEntities(item.label || '');

            // Subtitle or teaser text.
            const teaser = document.createElement('div');
            teaser.classList.add('teaser');
            teaser.textContent = decodeHtmlEntities(item.field_subtitle || '');

            // Publication date shown semantically.
            const pubDate = document.createElement('date');
            pubDate.classList.add('pub-date');
            pubDate.textContent = item.field_publication_date || '';

            // Assemble info container.
            info.appendChild(h1);
            info.appendChild(teaser);
            info.appendChild(pubDate);

            // Assemble complete list item.
            li.appendChild(thumb);
            li.appendChild(info);

            // Add the list item to the container.
            listContainer.appendChild(li);
          });

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

            const contentID = li.id;

            // Placeholder: load/display content related to this item.
            getContent(contentID);

            // Placeholder: pan or zoom map to this item location.
            getNewLatLng(contentID);
          });

        })
        .catch(err => {
          console.error('Failed to load map items:', err);
        });

      /* CloseButton */
      document.querySelectorAll('.leaflet__content a.x-button').forEach(button => {
        button.addEventListener('click', function (e) {
          e.preventDefault();

          mapWidthPx = document.querySelector('.leaflet').offsetWidth - 350;
          adjustMapView(mapWidthPx, theMap, lMap);

          document.querySelector('.leaflet__list-container').classList.remove('open');
          document.querySelector('.leaflet__list-container').classList.remove('container-up');
          document.querySelector('.leaflet__content').style.display = 'none';
          document.querySelectorAll('li.map-item-list').forEach(item => item.style.display = 'block');
          document.querySelector('.leaflet__top h1').style.display = 'block';
          document.querySelector('.leaflet__top h3').style.display = 'none';

          // filterItems();

          return null;
        });
      });

      /**
       * Placeholder function:
       * Intended to fetch and display detailed content for a given item.
       * User should implement AJAX or DOM manipulation here.
       * @param {string} contentID - The ID of the content to fetch.
       * @param which - The source of click (e.g., 'map' or 'list').
       */
      function getContent(contentID, which) {
        const currentDisplay = drupalSettings.leaflet_full_page?.currentDisplay || 'default_view_name';
        // console.log('Fetching content for:', contentID);

        fetch(`/${currentDisplay}_mapitems?contentID=${encodeURIComponent(contentID)}&which=${encodeURIComponent(which)}`)  // Add query parameter here
          .then(res => {
            // Get contentID from the request URL
            const url = new URL(res.url);
            const contentID = url.searchParams.get('contentID');
            const which = url.searchParams.get('which');

            // console.log('contentID from URL:', contentID);

            return res.json().then(data => {
              // Return both the data and the contentID
              return {
                data: data,
                contentID: contentID,
                which: which,
              };
            });
          })
          .then(data => {
            let theData = data.data;
            let contentID = data.contentID;  // Use contentID from the response
            let which = data.which;  // Use which from the response

            const item = theData.find(obj => Number(obj.id) === Number(contentID));

            if (item) {
              const itemsList = [];
              let itemTitle = '';
              if (which === 'map') {
                // do something with `item`
                itemsList.push(contentID);
                itemTitle = item.label;

                if (itemsList.length < 2) {
                  [contentID] = itemsList;
                } else {
                  contentID = null;

                  document.querySelectorAll('li.map-item-list').forEach(el => el.style.display = 'none');

                  itemsList.forEach(item => {
                    document.querySelector('.leaflet__list-container').classList.remove('open');
                    document.querySelector('.leaflet__list-container').classList.remove('container-up');
                    document.querySelector('.leaflet__content').style.display = 'none';
                    document.getElementById(item).style.display = 'block';
                    document.querySelector('.leaflet__top h1').style.display = 'none';
                    document.querySelector('.leaflet__top h3').innerHTML = itemTitle;
                    document.querySelector('.leaflet__top h3').style.display = 'block';
                  });
                }
              }

              if (contentID != null) {
                document.querySelector('.leaflet__content-title').textContent = item.label;
                document.querySelector('.leaflet__content-textarea').innerHTML = item.field_body;
                // document.querySelector('.leaflet__content-textarea').append(mapItems[contentID].content);

                document.querySelectorAll('.leaflet__content-textarea a').forEach(link => {
                  link.setAttribute('target', '_blank');
                });

                const theWidth = getTheWidth();

                if (theWidth === '100%') {
                  document.querySelector('.leaflet__list-container').classList.add('container-up');
                  if (item.image_url_mobile) {
                    document.querySelector('.leaflet__content-main-image').src = item.image_url_mobile;
                  }
                } else {
                  document.querySelector('.leaflet__list-container').classList.remove('container-up');
                  if (item.image_url_big) {
                    document.querySelector('.leaflet__content-main-image').src = item.image_url_big;
                  }
                }

                if (item.credit) {
                  const figcaption = document.querySelector('.leaflet__content-main-figure figcaption');
                  figcaption.innerHTML = `<strong>Credit: </strong>${item.credit}`;
                  figcaption.style.display = 'block';
                }

                document.querySelector('.leaflet__content').setAttribute('maps-nid', contentID);
                document.querySelector('.leaflet__content').style.display = 'block';

                requestAnimationFrame(() => document.querySelector('.leaflet__list-container').classList.add('open'));

                document.querySelector('.leaflet__content-scrollable').style.cssText = 'overflow-y: scroll; overflow-x: hidden;';
                document.querySelector('.leaflet__content-scrollable').scrollTo({ top: 0, behavior: 'smooth' });

                const scrollableElement = document.querySelector('.leaflet__content-scrollable');
                scrollableElement.style.cssText = 'overflow-y: scroll; overflow-x: hidden; max-height: 100%;';
                scrollableElement.scrollTo({ top: 0, behavior: 'smooth' });

                const headerHeight = document.querySelector('.leaflet__content-header').offsetHeight + 7 + 30;
                document.querySelector('.leaflet__content-scrollable').style.height = `${document.querySelector('.leaflet__content').offsetHeight - headerHeight - 20}px`;

                setTimeout(() => {
                  if (document.querySelector('.leaflet__list-container').classList.contains('container-up')) {
                    const headerHeight = document.querySelector('.leaflet__content-header').offsetHeight + 7 + 30;
                    document.querySelector('.leaflet__content-scrollable').style.height =
                      `${document.querySelector('.leaflet__content').offsetHeight - headerHeight - 20}px`;
                  }
                }, 400);
              }
            }
          })
          .catch(err => console.error(err));
      }

      function adjustMapView(mapWidthPx, theMap, lMap) {
        const zoom = zoomFromWidth(mapWidthPx);
        theMap.settings.center.lon = lonWestOfCenter(zoom);
        lMap.setView(theMap.settings.center, zoom);
        lMap.invalidateSize();

        console.log(theMap.settings.center);
        console.log('Zoom: ' + zoom);
        console.log('Zoom4: ' + lonWestOfCenter(zoom));
      }

      /**
       * Given a zoom level, returns the longitude
       * you get by moving 50 px west of the U.S. center.
       *
       * @param {number} zoom  – the Leaflet/OSM zoom (e.g. 0,1,2…)
       * @param {number} px    – pixel offset (default 50)
       * @returns {number}     – target longitude
       */
      function lonWestOfCenter(zoom, px = -175) {
        // Web-Mercator uses 256×2^zoom px world width → 360° across.
        const degPerPixel = 360 / (256 * Math.pow(2, zoom));
        return US_CENTER_LON - (degPerPixel * px);
      }

      /**
       * Placeholder function:
       * Intended to pan and zoom the Leaflet map to the location associated
       * with the given content ID.
       * @param {string} contentID - The ID to locate on the map.
       */
      function getNewLatLng(contentID) {
        // console.log('Panning map to:', contentID);
        // Implement locating item coordinates and updating map view here.
        const feature = features.find(item => item.entity_id === contentID);

        const newLatLng = {};

        newLatLng.lng = parseFloat(feature.lon) + 1;
        newLatLng.lat = feature.lat;
        // console.log(newLatLng);
        // console.log(feature);

        lMap.setView(newLatLng, 9);
        lMap.invalidateSize();
      }

      function zoomFromWidth(widthPx) {
        // Thresholds: [minWidth, zoom]
        const thresholds = [
          [1319, 5],
          [659.5, 4],
          [329.7, 3],
          [164.9, 2],
          [82.4, 1],
          [41.2, 0],
          [0, 0],  // catch anything below 41.2px as zoom 0
        ];

        for (const [minW, zoom] of thresholds) {
          if (widthPx >= minW) {
            return zoom;
          }
        }
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

        const windowHeight = window.innerHeight;
        const offsetTop = mapElement.getBoundingClientRect().top;
        const newHeight = windowHeight - offsetTop;

        const headerHeight = document.querySelector('.leaflet__content-header').offsetHeight + 7 + 30;
        document.querySelector('.leaflet__content-scrollable').style.height = `${document.querySelector('.leaflet__content').offsetHeight - headerHeight - 20}px`;

        mapElement.style.height = `${newHeight}px`;
        lMap.invalidateSize(); // Notify Leaflet to recalc map size.
      }

      function decodeHtmlEntities(text) {
        const txt = document.createElement('textarea');
        txt.innerHTML = text;
        return txt.value;
      }

      // Initial map height adjustment.
      adjustMapHeight();

      // Adjust map height dynamically as window resizes.
      window.addEventListener('resize', () => {
        adjustMapHeight();
      });

    });
  });
})(Drupal, drupalSettings);
