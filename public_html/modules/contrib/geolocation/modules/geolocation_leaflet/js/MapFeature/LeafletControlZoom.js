import { LeafletMapFeature } from "./LeafletMapFeature.js";

/**
 * @typedef {Object} ControlZoomSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {("topleft" | "topright" | "bottomleft" | "bottomright")} position
 */

/**
 * @prop {ControlZoomSettings} settings
 */
export default class LeafletControlZoom extends LeafletMapFeature {
  constructor(settings, map) {
    super(settings, map);
    L.control
      .zoom({
        position: this.getLeafletPosition(this.settings.position),
      })
      .addTo(map.leafletMap);
  }
}
