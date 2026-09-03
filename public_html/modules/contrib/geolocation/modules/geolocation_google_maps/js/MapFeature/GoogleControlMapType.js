import { GoogleMapFeature } from "./GoogleMapFeature.js";

/**
 * @typedef {Object} ControlMapTypeSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {String} position
 * @prop {String} style
 * @prop {String} behavior
 */
export default class GoogleControlMapType extends GoogleMapFeature {
  constructor(settings, map) {
    super(settings, map);

    this.map.googleMap.setOptions({
      mapTypeControlOptions: {
        position: google.maps.ControlPosition[this.settings.position],
        style: google.maps.MapTypeControlStyle[this.settings.style],
      },
      mapTypeControl: this.settings.behavior === "always" ? true : undefined,
    });
  }
}
