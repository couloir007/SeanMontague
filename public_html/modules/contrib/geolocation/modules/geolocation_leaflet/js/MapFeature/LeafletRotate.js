import { LeafletMapFeature } from "./LeafletMapFeature.js";

/**
 * @typedef {Object} ControlRotateSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {Number} bearing
 * @prop {Boolean} display_control
 */

/**
 * @prop {ControlRotateSettings} settings
 * @prop {L.Map} map
 */
export default class LeafletRotate extends LeafletMapFeature {
  constructor(settings, map) {
    super(settings, map);
    L.Util.setOptions(map.leafletMap, {
      rotateControl: this.settings.display_control,
    });

    map.leafletMap.setBearing(this.settings.bearing);
    map.leafletMap.touchRotate.enable();
  }
}
