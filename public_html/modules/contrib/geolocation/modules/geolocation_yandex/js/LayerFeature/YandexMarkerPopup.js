import { YandexLayerFeature } from "./YandexLayerFeature.js";
import { importDefaultUiTheme } from "../YandexPackages.js";

/**
 * @typedef {Object} YandexMarkerPopupSettings
 *
 * @augments {GeolocationLayerFeatureSettings}
 *
 * @prop {Boolean} info_auto_display
 * @prop {String} position
 * @prop {Number} offset
 */

/**
 * @prop {YandexMarkerPopupSettings} settings
 */
export default class YandexMarkerPopup extends YandexLayerFeature {
  onMarkerAdded(marker) {
    super.onMarkerAdded(marker);

    const contentElement = marker.getContentElement();
    const content = contentElement ? () => contentElement : marker.getContent();

    if (!content) {
      return;
    }

    importDefaultUiTheme().then((ymaps3Theme) => {
      const { YMapPopupMarker } = ymaps3Theme;

      marker.yandexPopup = new YMapPopupMarker({
        coordinates: this.layer.map.denormalizeCoordinates(marker.coordinates),
        content,
        position: this.settings.position ?? "top",
        offset: Math.round(this.settings.offset) || 0,
        show: this.settings.info_auto_display ?? false,
      });

      this.layer.map.yandexMap.addChild(marker.yandexPopup);
    });
  }

  onMarkerClicked(marker) {
    super.onMarkerClicked(marker);

    if (!marker.yandexPopup) {
      return;
    }

    marker.yandexPopup.update({ show: !marker.yandexPopup.isOpen });
  }

  onMarkerUpdated(marker) {
    super.onMarkerUpdated(marker);

    if (!marker.yandexPopup) {
      return;
    }

    marker.yandexPopup.update({ coordinates: this.layer.map.denormalizeCoordinates(marker.coordinates) });
  }

  onMarkerRemove(marker) {
    super.onMarkerRemove(marker);

    if (!marker.yandexPopup) {
      return;
    }

    this.layer.map.yandexMap.removeChild(marker.yandexPopup);
    marker.yandexPopup = undefined;
  }
}
