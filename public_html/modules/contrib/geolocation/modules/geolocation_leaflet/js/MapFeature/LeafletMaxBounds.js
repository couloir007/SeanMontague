import { LeafletMapFeature } from "./LeafletMapFeature.js";

/**
 * @typedef {Object} LeafletMaxBoundsSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {String} north
 * @prop {String} south
 * @prop {String} east
 * @prop {String} west
 */

/**
 * @prop {LeafletMaxBoundsSettings} settings
 */
export default class LeafletMaxBounds extends LeafletMapFeature {
  constructor(settings, map) {
    super(settings, map);

    let east = parseFloat(this.settings.east);
    const west = parseFloat(this.settings.west);
    const south = parseFloat(this.settings.south);
    const north = parseFloat(this.settings.north);
    if (west > east) {
      east += 360;
    }
    const bounds = new L.LatLngBounds([
      [south, west],
      [north, east],
    ]);
    map.leafletMap.setMaxBounds(bounds);
    map.leafletMap.setMinZoom(map.leafletMap.getBoundsZoom(bounds));
  }
}
