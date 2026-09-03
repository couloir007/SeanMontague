import { GeolocationLayerFeature } from "../../../../js/LayerFeature/GeolocationLayerFeature.js";

/**
 * @prop {Yandex} map
 */
export class YandexLayerFeature extends GeolocationLayerFeature {
  /**
   * @param {YandexMapMarker} marker
   *   Marker.
   */
  onMarkerAdded(marker) {
    super.onMarkerAdded(marker);
  }

  /**
   * @param {YandexMapMarker} marker
   *   Marker.
   */
  onMarkerClicked(marker) {
    super.onMarkerClicked(marker);
  }

  /**
   * @param {YandexMapMarker} marker
   *   Marker.
   */
  onMarkerRemove(marker) {
    super.onMarkerRemove(marker);
  }

  /**
   * @param {YandexMapMarker} marker
   *   Marker.
   */
  onMarkerUpdated(marker) {
    super.onMarkerUpdated(marker);
  }
}
