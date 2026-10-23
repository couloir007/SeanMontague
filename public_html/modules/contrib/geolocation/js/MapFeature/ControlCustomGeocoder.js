import { GeolocationMapFeature } from "./GeolocationMapFeature.js";

/**
 * @prop {GeolocationGeocoder} geocoder
 * @prop {HTMLInputElement|null} geocoderInput
 * @prop {GeolocationMapMarker|null} resultMarker
 * @prop {GeolocationGeocoderSettings} settings.geocoder_settings
 * @prop {Boolean} settings.show_marker
 */
export default class ControlCustomGeocoder extends GeolocationMapFeature {
  constructor(settings, map) {
    super(settings, map);

    this.resultMarker = null;

    // Taken before the map provider moves the controls into its map.
    this.geocoderInput = this.map.wrapper.querySelector(":scope > .geolocation-map-controls .geolocation-geocoder-address");
  }

  onMapReady() {
    super.onMapReady();

    // A reused map is ready again after every rebuild, its input stays the same.
    if (this.geocoder) {
      return;
    }

    if (!this.geocoderInput) {
      console.error(this.geocoderInput, "Geocoding input not found. No Geocoding feature support.");
    }

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

          this.map.features.forEach((feature) => {
            feature.onGeocodedResult(result);
          });
        });

        this.geocoder.addClearCallback(() => {
          this.removeResultMarker();
        });

        this.geocoder.attachToElement(this.geocoderInput);
      });
  }

  /**
   * @param {GeolocationGeocodedResult} result
   *   Geocoded result.
   */
  onGeocodedResult(result) {
    super.onGeocodedResult(result);

    if (!this.settings.show_marker || !result.coordinates) {
      return;
    }

    // The marker is intentionally not handed to a data layer: it is no part of
    // the map content, so it must stay out of the layer bounds, out of marker
    // clustering and out of the layer's own removals.
    if (this.resultMarker) {
      this.resultMarker.update(result.coordinates);

      return;
    }

    this.resultMarker = this.map.createMarker(result.coordinates, {
      id: "control-geocoder-result",
      title: Drupal.t("Geocoded location"),
    });
  }

  /**
   * @param {GeolocationMapMarker} marker
   *   Marker.
   */
  onMarkerClicked(marker) {
    super.onMarkerClicked(marker);

    // Every marker of the default layer reports here, so only the own one may
    // be dismissed.
    if (marker.id !== "control-geocoder-result") {
      return;
    }

    this.removeResultMarker();
  }

  /**
   * Remove the marker set at the geocoded location, if any.
   */
  removeResultMarker() {
    if (!this.resultMarker) {
      return;
    }

    this.resultMarker.remove();
    this.resultMarker = null;
  }
}
