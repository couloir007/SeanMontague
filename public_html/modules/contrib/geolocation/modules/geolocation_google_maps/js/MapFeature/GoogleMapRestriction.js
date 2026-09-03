import { GoogleMapFeature } from "./GoogleMapFeature.js";

/**
 * @typedef {Object} MapRestrictionSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {String} north
 * @prop {String} south
 * @prop {String} east
 * @prop {String} west
 * @prop {Boolean} strict
 */

/**
 * @prop {MapRestrictionSettings} settings
 * @prop {GoogleMaps} map
 */
export default class GoogleMapRestriction extends GoogleMapFeature {
  constructor(settings, map) {
    super(settings, map);

    this.map.googleMap.setOptions({
      restriction: {
        latLngBounds: {
          north: parseFloat(this.settings.north),
          south: parseFloat(this.settings.south),
          east: parseFloat(this.settings.east),
          west: parseFloat(this.settings.west),
        },
        strictBounds: Boolean(this.settings.strict),
      },
    });
  }
}
