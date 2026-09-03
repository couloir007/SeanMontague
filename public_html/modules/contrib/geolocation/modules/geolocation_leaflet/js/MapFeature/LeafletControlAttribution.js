import { LeafletMapFeature } from "./LeafletMapFeature.js";

/**
 * @typedef {Object} ControlAttributionSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {String} prefix
 * @prop {String} position
 */

/**
 * @prop {ControlAttributionSettings} settings
 */
export default class LeafletControlAttribution extends LeafletMapFeature {
  constructor(settings, map) {
    super(settings, map);

    if (!navigator.geolocation) {
      return;
    }

    L.control
      .attribution({
        prefix: `${this.settings.prefix} | &copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors`,
        position: this.getLeafletPosition(this.settings.position),
      })
      .addTo(map.leafletMap);
  }
}
