import { GeolocationLocationInputBase } from "./GeolocationLocationInputBase.js";

/**
 * @typedef {Object} GeocoderLocationInputSettings
 *
 * @extends GeolocationLocationInputSettings
 *
 * @prop {Boolean} auto_submit
 * @prop {Boolean} hide_form
 * @prop {Object} geocoder_settings
 * @prop {String} geocoder_settings.import_path
 * @prop {GeolocationGeocoderSettings} geocoder_settings.settings
 */

/**
 * @prop {GeocoderLocationInputSettings} settings
 */
export default class Geocoder extends GeolocationLocationInputBase {
  constructor(form, settings = {}) {
    super(form, settings);

    import(this.settings.geocoder_settings.import_path)
      /** @param {GeolocationGeocoder} geocoder */
      .then((geocoder) => {
        this.geocoder = new geocoder.default(this.settings.geocoder_settings);

        if (!this.geocoder) {
          console.error(this.geocoder, "Could not instantiate Geocoder. No Geocoding feature support.");
        }

        this.geocoder.addResultCallback((result) => {
          if (result.coordinates) {
            this.setCoordinates(result.coordinates);
          }

          if (this.settings.auto_submit) {
            this.submit();
          }
        });

        const geocoderInput = this.form.querySelector(".geolocation-geocoder-address");

        if (!geocoderInput) {
          console.error("No Geocoder input found");
          return false;
        }

        this.geocoder.attachToElement(geocoderInput);
      });
  }
}
