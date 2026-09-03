import { LeafletMapFeature } from "./LeafletMapFeature.js";

/**
 * @typedef {Object} TileLayerSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {String} tile_layer_provider
 * @prop {String} tile_layer_options
 */

/**
 * @prop {TileLayerSettings} settings
 */
export default class LeafletTileLayer extends LeafletMapFeature {
  constructor(settings, map) {
    super(settings, map);
    map.tileLayer.remove();
    map.tileLayer = L.tileLayer.provider(this.settings.tile_layer_provider, this.settings.tile_layer_options).addTo(map.leafletMap);
  }
}
