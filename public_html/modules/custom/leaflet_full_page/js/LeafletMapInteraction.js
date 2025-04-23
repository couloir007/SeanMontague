(function (Drupal, drupalSettings) {
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

      // Guard clause: Leaflet map instance must be initialized.
      if (!lMap) {
        console.error(`Leaflet map ${mapId} not initialized after window.load.`);
        return;
      }

      /**
       * Load GeoJSON file (e.g. boundaries or trails) asynchronously,
       * then add it as a styled layer to the Leaflet map.
       */
      fetch('/sites/default/files/KingdomTrails.geojson')
        .then(res => res.json())
        .then(geojsonData => {
          const geoJsonLayer = L.geoJSON(geojsonData, {
            style: () => ({
              color: 'green',
              weight: 2,
              fillOpacity: 0.2,
            }),
            onEachFeature: (feature, layer) => {
              if (feature.properties && feature.properties.name) {
                layer.bindPopup(feature.properties.name);
              }
            }
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
          if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
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
            if (!li) return; // Ignore clicks outside list items.

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

      /**
       * Placeholder function:
       * Intended to fetch and display detailed content for a given item.
       * User should implement AJAX or DOM manipulation here.
       * @param {string} contentID - The ID of the content to fetch.
       */
      function getContent(contentID) {
        console.log('Fetching content for:', contentID);
        // Implement fetching and displaying content details here.
        const currentDisplay = drupalSettings.leaflet_full_page?.currentDisplay || 'default_view_name';
        fetch(`/${currentDisplay}_mapitems`)
          .then(res => res.json())
          .then(data => {
            const item = data.find(obj => obj.id === contentID);
            if (item) {
              // do something with `item`
              ray(item);
              $('.leaflet__content-title').html(item.label);
              $('.leaflet__content-textarea').html(item.body);
            }
          })
          .catch(err => console.error(err));
      }

      /**
       * Placeholder function:
       * Intended to pan and zoom the Leaflet map to the location associated
       * with the given content ID.
       * @param {string} contentID - The ID to locate on the map.
       */
      function getNewLatLng(contentID) {
        console.log('Panning map to:', contentID);
        // Implement locating item coordinates and updating map view here.
      }

      /**
       * Dynamically adjusts the Leaflet map container height to fill available viewport space.
       * Ensures map resizes correctly on window resize.
       */
      function adjustMapHeight() {
        const mapElement = document.getElementById(mapId);
        if (!mapElement) return; // Guard: element must exist.

        const windowHeight = window.innerHeight;
        const offsetTop = mapElement.getBoundingClientRect().top;
        const newHeight = windowHeight - offsetTop;

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
