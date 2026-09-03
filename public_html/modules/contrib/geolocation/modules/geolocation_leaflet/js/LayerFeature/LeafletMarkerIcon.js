/**
 * @typedef {Object} LeafletMarkerIconSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {String} marker_icon_path
 * @prop {Array} icon_size
 * @prop {Number} icon_size.width
 * @prop {Number} icon_size.height
 * @prop {Array} icon_anchor
 * @prop {Number} icon_anchor.x
 * @prop {Number} icon_anchor.y
 * @prop {Array} popup_anchor
 * @prop {Number} popup_anchor.x
 * @prop {Number} popup_anchor.y
 * @prop {String} marker_shadow_path
 * @prop {Array} shadow_size
 * @prop {Number} shadow_size.width
 * @prop {Number} shadowSize.height
 * @prop {Array} shadow_anchor
 * @prop {Number} shadow_anchor.x
 * @prop {Number} shadow_anchor.y
 */

import { LeafletLayerFeature } from "./LeafletLayerFeature.js";

/**
 * @prop {LeafletMarkerIconSettings} settings
 */
export default class LeafletMarkerIcon extends LeafletLayerFeature {
  onMarkerAdded(marker) {
    super.onMarkerAdded(marker);

    let iconUrl;
    let currentIcon;
    if (typeof marker.wrapper !== "undefined") {
      currentIcon = marker.wrapper.dataset.icon ?? null;
    }

    if (!currentIcon) {
      if (typeof this.settings.marker_icon_path === "string") {
        iconUrl = this.settings.marker_icon_path;
      } else {
        return;
      }
    } else {
      iconUrl = currentIcon;
    }

    const iconOptions = {
      iconUrl,
    };

    if (this.settings.marker_shadow_path) {
      iconOptions.shadowUrl = this.settings.marker_shadow_path;
    }

    if (this.settings.icon_size?.width && this.settings.icon_size?.height) {
      iconOptions.iconSize = [this.settings.icon_size.width, this.settings.icon_size.height];
    }

    if (this.settings.shadow_size?.width && this.settings.shadow_size?.height) {
      iconOptions.shadowSize = [this.settings.shadow_size.width, this.settings.shadow_size.height];
    }

    if (this.settings.icon_anchor?.x || this.settings.icon_anchor?.y) {
      iconOptions.iconAnchor = [this.settings.icon_anchor.x, this.settings.icon_anchor.y];
    }

    if (this.settings.shadow_anchor?.x || this.settings.shadow_anchor?.y) {
      iconOptions.shadowAnchor = [this.settings.shadow_anchor.x, this.settings.shadow_anchor.y];
    }

    if (this.settings.popup_anchor?.x || this.settings.popup_anchor?.y) {
      iconOptions.popupAnchor = [this.settings.popup_anchor.x, this.settings.popup_anchor.y];
    }

    marker.leafletMarker.setIcon(L.icon(iconOptions));
  }
}
