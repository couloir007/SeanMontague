import { LeafletMapFeature } from "./LeafletMapFeature.js";

/**
 * @typedef {Object} CustomTileLayerSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {String} tile_layer_url
 * @prop {String} tile_layer_attribution
 * @prop {String} tile_layer_subdomains
 * @prop {Number} tile_layer_zoom
 */

/**
 * @prop {CustomTileLayerSettings} settings
 */
export default class LeafletCustomTileLayer extends LeafletMapFeature {
  constructor(settings, map) {
    super(settings, map);
    map.tileLayer.remove();
    map.tileLayer = L.tileLayer(this.settings.tile_layer_url, {
      attribution: this.settings.tile_layer_attribution,
      subdomains: this.settings.tile_layer_subdomains,
      maxZoom: this.settings.tile_layer_zoom,
    }).addTo(map.leafletMap);
  }
}
