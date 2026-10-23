import { GoogleMapFeature } from "./GoogleMapFeature.js";

/**
 * @typedef {Object} ControlFullscreenSettings
 *
 * @augments {GeolocationMapFeatureSettings}

 * @prop {String} position
 */
export default class GoogleControlFullscreen extends GoogleMapFeature {
  constructor(settings, map) {
    super(settings, map);

    this.map.googleMap.setOptions({
      fullscreenControlOptions: {
        position: google.maps.ControlPosition[this.settings.position],
      },
      fullscreenControl: true,
    });
  }
}
