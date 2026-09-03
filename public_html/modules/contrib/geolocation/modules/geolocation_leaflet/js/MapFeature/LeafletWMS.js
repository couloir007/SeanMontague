import { LeafletMapFeature } from "./LeafletMapFeature.js";

/**
 * @typedef {Object} WMSSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {String} url
 * @prop {String} version
 * @prop {String} layers
 * @prop {String} styles
 * @prop {String} srs
 * @prop {String} format
 * @prop {Boolean} transparent
 * @prop {Boolean} identify
 */

/**
 * @prop {WMSSettings} settings
 */
export default class LeafletWMS extends LeafletMapFeature {
  constructor(settings, map) {
    super(settings, map);
    const source = L.WMS.source(this.settings.url, {
      version: this.settings.version,
      styles: this.settings.styles,
      srs: this.settings.srs,
      format: this.settings.format,
      transparent: !!this.settings.transparent,
      identify: !!this.settings.identify,
    });
    source.getLayer(this.settings.layers).addTo(map.leafletMap);
  }
}
