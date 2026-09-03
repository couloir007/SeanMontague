import { GeolocationMapFeature } from "./GeolocationMapFeature.js";

/**
 * @prop {GeolocationGeocoder} geocoder
 * @prop {GeolocationGeocoderSettings} settings.geocoder_settings
 */
export default class ControlCustomGeocoder extends GeolocationMapFeature {
  onMapReady() {
    super.onMapReady();

    const geocoderInput = this.map.wrapper.querySelector(`.geolocation-geocoder-address[data-source-identifier="${this.map.wrapper.getAttribute("id")}"]`);

    if (!geocoderInput) {
      console.error(geocoderInput, "Geocoding input not found. No Geocoding feature support.");
    }

    if (this.geocoder) {
      this.geocoder.attachToElement(geocoderInput);
    } else {
      import(this.settings.geocoder_settings.import_path)
        /** @param {GeolocationGeocoder} geocoder */
        .then((geocoder) => {
          this.geocoder = new geocoder.default(this.settings.geocoder_settings);
          if (!this.geocoder) {
            console.error(this.geocoder, "Could not instantiate Geocoder. No Geocoding feature support.");
          }

          this.geocoder.addResultCallback((result) => {
            if (result.boundaries) {
              this.map.setBoundaries(result.boundaries);
            } else {
              let accuracy;
              if (typeof result.accuracy === "undefined") {
                accuracy = 10000;
              }
              this.map.setCenterByCoordinates(result.coordinates, accuracy);
            }
          });

          this.geocoder.attachToElement(geocoderInput);
        });
    }
  }
}
