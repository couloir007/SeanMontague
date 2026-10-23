import { GoogleLayerFeature } from "./GoogleLayerFeature.js";

/**
 * @typedef {Object} MarkerIconSettings
 *
 * @augments {GeolocationMapFeatureSettings}
 *
 * @prop {String} marker_icon_path
 * @prop {Object} size
 * @prop {Number} size.width
 * @prop {Number} size.height
 * @prop {Object} anchor
 * @prop {String} anchor.left
 * @prop {String} anchor.top
 */

/**
 * @prop {MarkerIconSettings} settings
 */
export default class GoogleMarkerIcon extends GoogleLayerFeature {
  /**
   * @param {GoogleMapMarker} marker
   *   Google marker.
   */
  onMarkerAdded(marker) {
    super.onMarkerAdded(marker);

    this.applyIcon(marker);
  }

  /**
   * @param {GoogleMapMarker} marker
   *   Google marker.
   */
  onMarkerUpdated(marker) {
    super.onMarkerUpdated(marker);

    this.applyIcon(marker);
  }

  /**
   * Replace the marker content by a custom image.
   *
   * Icons set per location, like by the Views style icon field, take
   * precedence over the icon configured for the entire layer.
   *
   * @param {GoogleMapMarker} marker
   *   Google marker.
   */
  applyIcon(marker) {
    const iconUrl = marker.icon ?? this.settings.marker_icon_path;

    // No icon anywhere, leave the default pin alone.
    if (!iconUrl) {
      return;
    }

    const icon = document.createElement("img");
    icon.src = iconUrl;
    icon.alt = marker.title ?? "";
    icon.style.display = "block";

    if (this.settings.size?.width) {
      icon.style.width = `${this.settings.size.width}px`;
    }
    if (this.settings.size?.height) {
      icon.style.height = `${this.settings.size.height}px`;
    }

    // AdvancedMarkerElement anchors the bottom center of its content by
    // default. Only override that if actually configured.
    if (this.settings.anchor?.left) {
      marker.googleMarker.anchorLeft = this.settings.anchor.left;
    }
    if (this.settings.anchor?.top) {
      marker.googleMarker.anchorTop = this.settings.anchor.top;
    }

    // Replacing the marker content drops the label rendered as a pin glyph,
    // so bring it along.
    if (marker.label) {
      const wrapper = document.createElement("div");
      wrapper.style.position = "relative";
      wrapper.style.display = "inline-block";
      wrapper.appendChild(icon);

      const label = document.createElement("span");
      label.classList.add("geolocation-marker-icon-label");
      label.style.position = "absolute";
      label.style.left = "50%";
      label.style.top = "100%";
      label.style.transform = "translateX(-50%)";
      label.style.whiteSpace = "nowrap";
      label.style.fontWeight = "bold";
      label.style.fontSize = "14px";
      label.innerText = marker.label;
      wrapper.appendChild(label);

      marker.googleMarker.replaceChildren(wrapper);

      return;
    }

    marker.googleMarker.replaceChildren(icon);
  }
}
