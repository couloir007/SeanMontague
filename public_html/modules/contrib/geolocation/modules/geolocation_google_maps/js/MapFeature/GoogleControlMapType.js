import { GoogleMapFeature } from "./GoogleMapFeature.js";

/**
 * @typedef {Object} ControlMapTypeSettings
 *
 * @augments {GeolocationMapFeatureSettings}
 *
 * @prop {String} position
 * @prop {String} style
 */
export default class GoogleControlMapType extends GoogleMapFeature {
  constructor(settings, map) {
    super(settings, map);

    this.map.googleMap.setOptions({
      mapTypeControlOptions: {
        position: google.maps.ControlPosition[this.settings.position],
        style: google.maps.MapTypeControlStyle[this.settings.style],
      },
      mapTypeControl: true,
    });
  }
}
