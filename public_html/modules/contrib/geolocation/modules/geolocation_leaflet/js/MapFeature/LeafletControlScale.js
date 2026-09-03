import { LeafletMapFeature } from "./LeafletMapFeature.js";

/**
 * @typedef {Object} ControlScaleSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {String} position
 * @prop {Boolean} metric
 * @prop {Boolean} imperial
 */

/**
 * @prop {ControlScaleSettings} settings
 */
export default class LeafletControlScale extends LeafletMapFeature {
  constructor(settings, map) {
    super(settings, map);
    L.control
      .scale({
        position: this.getLeafletPosition(this.settings.position),
        metric: this.settings.metric,
        imperial: this.settings.imperial,
      })
      .addTo(map.leafletMap);
  }
}
