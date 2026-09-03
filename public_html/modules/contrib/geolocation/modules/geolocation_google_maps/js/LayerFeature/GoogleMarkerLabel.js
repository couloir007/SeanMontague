import { GoogleLayerFeature } from "./GoogleLayerFeature.js";

/**
 * @typedef {Object} MarkerLabelSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {String} color
 * @prop {String} fontFamily
 * @prop {String} fontSize
 * @prop {String} fontWeight
 */

export default class GoogleMarkerLabel extends GoogleLayerFeature {
  onMarkerAdded(marker) {
    super.onMarkerAdded(marker);

    const currentLabel = marker.googleMarker.getLabel();
    if (!currentLabel) {
      return;
    }

    const text = typeof currentLabel === "string" ? currentLabel : currentLabel.text;
    if (!text) {
      return;
    }

    marker.googleMarker.setLabel({
      text,
      color: this.settings.color ?? undefined,
      fontFamily: this.settings.font_family ?? undefined,
      fontWeight: this.settings.font_weight ?? undefined,
      fontSize: this.settings.font_size ?? undefined,
    });
  }
}
