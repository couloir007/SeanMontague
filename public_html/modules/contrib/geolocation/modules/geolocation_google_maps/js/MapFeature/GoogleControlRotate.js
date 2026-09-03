import { GoogleMapFeature } from "./GoogleMapFeature.js";

/**
 * @typedef {Object} ControlRotateSettings
 *
 * @extends {GeolocationMapFeatureSettings}

 * @prop {String} position
 * @prop {String} behavior
 */

export default class GoogleControlRotate extends GoogleMapFeature {
  constructor(settings, map) {
    super(settings, map);

    this.map.googleMap.setOptions({
      rotateControlOptions: {
        position: google.maps.ControlPosition[this.settings.position],
      },
      rotateControl: this.settings.behavior === "always" ? true : undefined,
    });
  }
}
