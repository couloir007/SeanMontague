import { GeolocationMapFeature } from "../../../../js/MapFeature/GeolocationMapFeature.js";

/**
 * @prop {Baidu} map
 */
export class BaiduMapFeature extends GeolocationMapFeature {
  /**
   * @param {BaiduMapMarker} marker
   *   Baidu marker.
   */
  onMarkerAdded(marker) {
    super.onMarkerAdded(marker);
  }

  /**
   * @param {BaiduMapMarker} marker
   *   Baidu marker.
   */
  onMarkerUpdated(marker) {
    super.onMarkerUpdated(marker);
  }

  /**
   * @param {BaiduMapMarker} marker
   *   Baidu marker.
   */
  onMarkerRemove(marker) {
    super.onMarkerRemove(marker);
  }

  /**
   * @param {BaiduMapMarker} marker
   *   Baidu marker.
   */
  onMarkerClicked(marker) {
    super.onMarkerClicked(marker);
  }
}
