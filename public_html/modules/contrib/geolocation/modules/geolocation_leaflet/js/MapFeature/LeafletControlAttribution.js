import { LeafletMapFeature } from "./LeafletMapFeature.js";

/**
 * @typedef {Object} ControlAttributionSettings
 *
 * @augments {GeolocationMapFeatureSettings}
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
        prefix: this.settings.prefix,
        position: this.getLeafletPosition(this.settings.position),
      })
      .addTo(map.leafletMap);
  }
}
