import { LeafletMapFeature } from "./LeafletMapFeature.js";

/**
 * @typedef {Object} ControlLayerSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {String} default_label
 * @prop {Array} tile_layer_providers
 * @prop {Array} tile_layer_options
 * @prop {String} position
 */

/**
 * @prop {ControlLayerSettings} settings
 */
export default class LeafletControlLayer extends LeafletMapFeature {
  constructor(settings, map) {
    super(settings, map);
    const baseMaps = {};
    baseMaps[this.settings.default_label] = map.tileLayer;
    this.settings.tile_layer_providers.forEach((variant, label) => {
      const parts = variant.split(".");
      const provider = parts[0];
      baseMaps[label] = L.tileLayer.provider(variant, this.settings.tile_layer_options[provider] || {});
    });

    const overlayMaps = {};
    L.control
      .layers(baseMaps, overlayMaps, {
        position: this.getLeafletPosition(this.settings.position),
      })
      .addTo(map.leafletMap);
  }
}
