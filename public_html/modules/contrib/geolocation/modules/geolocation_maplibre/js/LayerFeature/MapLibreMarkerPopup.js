import { MapLibreLayerFeature } from "./MapLibreLayerFeature.js";

/**
 * @typedef {Object} MapLibreMarkerPopupSettings
 *
 * @augments {GeolocationMapFeatureSettings}
 *
 * @prop {Boolean} info_auto_display
 * @prop {Number} max_width
 * @prop {Boolean} close_button
 * @prop {Boolean} close_on_click
 * @prop {Boolean} close_on_move
 * @prop {String} class_name
 */

/**
 * @prop {MapLibreMarkerPopupSettings} settings
 */
export default class MapLibreMarkerPopup extends MapLibreLayerFeature {
  onMarkerAdded(marker) {
    super.onMarkerAdded(marker);

    const contentElement = marker.getContentElement();
    const content = contentElement ?? marker.getContent();

    if (!content) {
      return;
    }

    const popup = new this.map.maplibregl.Popup({
      maxWidth: `${Math.round(this.settings.max_width) || 300}px`,
      closeButton: this.settings.close_button ?? true,
      closeOnClick: this.settings.close_on_click ?? true,
      closeOnMove: this.settings.close_on_move ?? false,
      className: this.settings.class_name || undefined,
    });

    if (contentElement) {
      popup.setDOMContent(contentElement);
    } else {
      popup.setHTML(content);
    }

    marker.maplibreMarker.setPopup(popup);

    if (this.settings.info_auto_display) {
      marker.maplibreMarker.togglePopup();
    }
  }
}
