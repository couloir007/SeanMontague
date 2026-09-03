import { GoogleMapFeature } from "./GoogleMapFeature.js";

/**
 * @typedef {Object} ControlFullscreenSettings
 *
 * @extends {GeolocationMapFeatureSettings}

 * @prop {String} position
 * @prop {String} behavior
 */
export default class GoogleControlFullscreen extends GoogleMapFeature {
  constructor(settings, map) {
    super(settings, map);

    this.map.googleMap.setOptions({
      fullscreenControlOptions: {
        position: google.maps.ControlPosition[this.settings.position],
      },
      fullscreenControl: this.settings.behavior === "always" ? true : undefined,
    });
  }
}
