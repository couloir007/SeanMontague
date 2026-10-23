import { GoogleMapFeature } from "./GoogleMapFeature.js";

/**
 * @typedef {Object} ControlRotateSettings
 *
 * @augments {GeolocationMapFeatureSettings}

 * @prop {String} position
 */

export default class GoogleControlRotate extends GoogleMapFeature {
  constructor(settings, map) {
    super(settings, map);

    this.map.googleMap.setOptions({
      rotateControlOptions: {
        position: google.maps.ControlPosition[this.settings.position],
      },
      rotateControl: true,
    });
  }
}
