import { GoogleMapFeature } from "./GoogleMapFeature.js";

/**
 * @typedef {Object} ControlCameraSettings
 *
 * @augments {GeolocationMapFeatureSettings}
 *
 * @prop {String} position
 */
export default class GoogleControlCamera extends GoogleMapFeature {
  constructor(settings, map) {
    super(settings, map);

    this.map.googleMap.setOptions({
      cameraControlOptions: {
        position: google.maps.ControlPosition[this.settings.position],
      },
      cameraControl: true,
    });
  }
}
