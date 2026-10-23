import { GoogleMapFeature } from "./GoogleMapFeature.js";

/**
 * @typedef {Object} ControlStreetViewSettings
 *
 * @augments {GeolocationMapFeatureSettings}

 * @prop {String} position
 */

export default class GoogleControlStreetView extends GoogleMapFeature {
  constructor(settings, map) {
    super(settings, map);

    this.map.googleMap.setOptions({
      streetViewControlOptions: {
        position: google.maps.ControlPosition[this.settings.position],
      },
      streetViewControl: true,
    });
  }
}
