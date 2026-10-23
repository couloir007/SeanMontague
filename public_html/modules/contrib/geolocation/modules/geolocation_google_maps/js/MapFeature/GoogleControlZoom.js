import { GoogleMapFeature } from "./GoogleMapFeature.js";

/**
 * @typedef {Object} ControlZoomSettings
 *
 * @augments {GeolocationMapFeatureSettings}
 *
 * @prop {String} position
 */
export default class GoogleControlZoom extends GoogleMapFeature {
  constructor(settings, map) {
    super(settings, map);

    this.map.googleMap.setOptions({
      zoomControlOptions: {
        position: google.maps.ControlPosition[this.settings.position],
      },
      zoomControl: true,
    });
  }
}
