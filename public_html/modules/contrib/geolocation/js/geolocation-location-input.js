/**
 * @file
 * Geolocation - Location Input Form & Plugin Management.
 */

(function (Drupal) {
  Drupal.behaviors.geolocationLocationInput = {
    /**
     * @param {Element} context
     * @param {Object} drupalSettings
     * @param {Object} drupalSettings.geolocation
     * @param {Object} drupalSettings.geolocation.locationInput
     */
    attach: (context, drupalSettings) => {
      for (const identifier of Object.keys(drupalSettings.geolocation.locationInput)) {
        const locationInputForm = context.querySelector(`.geolocation-location-input[data-identifier=${identifier}]`);

        if (!locationInputForm) {
          // Nothing left to do. Probably a different context. Not an error.
          continue;
        }

        if (locationInputForm.classList.contains("geolocation-location-input-processed")) {
          continue;
        }
        locationInputForm.classList.add("geolocation-location-input-processed");

        for (const pluginName of Object.keys(drupalSettings.geolocation.locationInput[identifier])) {
          const pluginSettings = drupalSettings.geolocation.locationInput[identifier][pluginName] ?? {};
          import(pluginSettings.import_path).then((plugin) => {
            /** @param {GeolocationLocationInputBase} locationInputPlugin */
            const locationInputPlugin = new plugin.default(locationInputForm, pluginSettings.settings);

            if (!locationInputPlugin) {
              console.error(pluginSettings, "Could not instantiate LocationInput Plugin.");
            }
          });
        }
      }
    },
    detach: () => {},
  };
})(Drupal);
