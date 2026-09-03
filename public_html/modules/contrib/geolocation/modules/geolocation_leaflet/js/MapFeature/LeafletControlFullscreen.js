import { LeafletMapFeature } from "./LeafletMapFeature.js";

/**
 * @typedef {Object} ControlFullscreenSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {String} position
 */

/**
 * @prop {ControlFullscreenSettings} settings
 */
export default class LeafletControlFullscreen extends LeafletMapFeature {
  constructor(settings, map) {
    super(settings, map);
    map.leafletMap.addControl(
      new L.Control.Fullscreen({
        position: this.getLeafletPosition(this.settings.position),
        title: {
          false: Drupal.t("View Fullscreen"),
          true: Drupal.t("Exit Fullscreen"),
        },
      })
    );
  }
}
