/**
 * @file
 * Javascript for the Google geocoder function, specifically the views filter.
 */

/**
 * @typedef {Object} GeolocationViewsFilterGeocoderSettings
 *
 * @extends {GeolocationGeocoderSettings}
 *
 * @prop {String} import_path
 */

(function (Drupal) {
  /**
   * Attach common map style functionality.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches views geolocation filter geocoder to relevant elements.
   */
  Drupal.behaviors.geolocationViewsFilterGeocoder = {
    /**
     * @param {Object} context
     * @param {Object} drupalSettings
     * @param {Object.<String, GeolocationViewsFilterGeocoderSettings>} drupalSettings.geolocation.geocoder.viewsFilterGeocoder
     */
    attach: (context, drupalSettings) => {
      for (const [elementId, filterSettings] of Object.entries(drupalSettings.geolocation.geocoder.viewsFilterGeocoder)) {
        const form = document.querySelector(`.views-exposed-form .geolocation-geocoder-address[data-source-identifier="${elementId}"]`);

        if (form.classList.contains("processed")) {
          return;
        }
        form.classList.add("processed");

        if (!form) {
          console.warn("Could not find views exposed filter form.");
          return;
        }

        import(filterSettings.settings.import_path)
          /** @param {GeolocationGeocoder} geocoder */
          .then((geocoder) => {
            geocoder = new geocoder.default(filterSettings.settings);

            if (!geocoder) {
              console.error(geocoder, "Could not instantiate Geocoder. No Geocoding feature support.");
            }

            geocoder.addResultCallback((result) => {
              document.querySelector(`input[name='${elementId}[lat_north_east]']`).value = result.boundaries?.north ?? result.coordinates.lat;
              document.querySelector(`input[name='${elementId}[lng_north_east]']`).value = result.boundaries?.east ?? result.coordinates.lng;
              document.querySelector(`input[name='${elementId}[lat_south_west]']`).value = result.boundaries?.south ?? result.coordinates.lat;
              document.querySelector(`input[name='${elementId}[lng_south_west]']`).value = result.boundaries?.west ?? result.coordinates.lng;
            });

            geocoder.addClearCallback(() => {
              document.querySelector(`input[name='${elementId}[lat_north_east]']`).value = "";
              document.querySelector(`input[name='${elementId}[lng_north_east]']`).value = "";
              document.querySelector(`input[name='${elementId}[lat_south_west]']`).value = "";
              document.querySelector(`input[name='${elementId}[lng_south_west]']`).value = "";
            });

            geocoder.attachToElement(form);
          });
      }
    },
    detach: () => {},
  };
})(Drupal);
