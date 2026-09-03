import { GoogleMapFeature } from "./GoogleMapFeature.js";

/**
 * @typedef {Object} ControlZoomSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {String} behavior
 * @prop {String} position
 * @prop {String} style
 */
export default class GoogleControlZoom extends GoogleMapFeature {
  constructor(settings, map) {
    super(settings, map);

    this.map.googleMap.setOptions({
      zoomControlOptions: {
        position: google.maps.ControlPosition[this.settings.position],
        style: google.maps.ZoomControlStyle[this.settings.style],
      },
      zoomControl: this.settings.behavior === "always" ? true : undefined,
    });
  }
}
