import { GoogleMapFeature } from "./GoogleMapFeature.js";

/**
 * @typedef {Object} ControlStreetViewSettings
 *
 * @extends {GeolocationMapFeatureSettings}

 * @prop {String} position
 * @prop {String} behavior
 */

export default class GoogleControlStreetView extends GoogleMapFeature {
  constructor(settings, map) {
    super(settings, map);

    this.map.googleMap.setOptions({
      streetViewControlOptions: {
        position: google.maps.ControlPosition[this.settings.position],
      },
      streetViewControl: this.settings.behavior === "always" ? true : undefined,
    });
  }
}
