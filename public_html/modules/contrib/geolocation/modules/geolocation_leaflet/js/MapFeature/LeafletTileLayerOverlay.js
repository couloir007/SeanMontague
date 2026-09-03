import { LeafletMapFeature } from "./LeafletMapFeature.js";

/**
 * @typedef {Object} TileLayerOverlaySettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {String} tile_layer_overlay
 * @prop {String} tile_layer_options
 */

/**
 * @prop {TileLayerOverlaySettings} settings
 */
export default class LeafletTileLayerOverlay extends LeafletMapFeature {
  constructor(settings, map) {
    super(settings, map);
    L.tileLayer.provider(this.settings.tile_layer_overlay, this.settings.tile_layer_options).addTo(map.leafletMap).bringToFront();
  }
}
