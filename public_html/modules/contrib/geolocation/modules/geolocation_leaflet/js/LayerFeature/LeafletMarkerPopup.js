import { LeafletLayerFeature } from "./LeafletLayerFeature.js";

/**
 * @typedef {Object} LeafletMarkerPopupSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {Boolean} info_auto_display
 * @prop {Number} max_width
 * @prop {Number} min_width
 * @prop {Number} max_height
 * @prop {Boolean} auto_pan
 * @prop {Boolean} keep_in_view
 * @prop {Boolean} close_button
 * @prop {Boolean} auto_close
 * @prop {Boolean} close_on_escape_key
 * @prop {String} class_name
 */

/**
 * @prop {LeafletMarkerPopupSettings} settings
 */
export default class LeafletMarkerPopup extends LeafletLayerFeature {
  onMarkerAdded(marker) {
    super.onMarkerAdded(marker);

    const content = marker.getContent();

    if (!content) {
      return;
    }

    marker.leafletMarker.bindPopup(content, {
      maxWidth: Math.round(this.settings.max_width) ?? 300,
      minWidth: Math.round(this.settings.min_width) ?? 50,
      maxHeight: Math.round(this.settings.max_height) ?? null,
      autoPan: this.settings.auto_pan ?? true,
      keepInView: this.settings.keep_in_view ?? false,
      closeButton: this.settings.close_button ?? true,
      autoClose: this.settings.auto_close ?? true,
      closeOnEscapeKey: this.settings.close_on_escape_key ?? true,
      className: this.settings.class_name ?? "",
    });

    if (this.settings.info_auto_display) {
      marker.leafletMarker.openPopup();
    }
  }

  /**
   * @param {GeolocationShape} shape
   * @param {L.geojson.GeometryObject[]} shape.leafletShapes
   */
  onShapeAdded(shape) {
    super.onShapeAdded(shape);

    const content = shape.getContent();

    if (!content) {
      return;
    }

    if (typeof shape.leafletShapes === "undefined") {
      return;
    }

    shape.leafletShapes.forEach((leafletShape) => {
      leafletShape.bindPopup(content, {
        maxWidth: Math.round(this.settings.max_width) ?? 300,
        minWidth: Math.round(this.settings.min_width) ?? 50,
        maxHeight: Math.round(this.settings.max_height) ?? null,
        autoPan: this.settings.auto_pan ?? true,
        keepInView: this.settings.keep_in_view ?? false,
        closeButton: this.settings.close_button ?? true,
        autoClose: this.settings.auto_close ?? true,
        closeOnEscapeKey: this.settings.close_on_escape_key ?? true,
        className: this.settings.class_name ?? "",
      });

      if (this.settings.info_auto_display) {
        leafletShape.openPopup();
      }
    });
  }
}
