import { GeolocationLayerFeature } from "../../../../js/LayerFeature/GeolocationLayerFeature.js";

/**
 * @prop {Baidu} layer.map
 */
export class BaiduLayerFeature extends GeolocationLayerFeature {
  /**
   * @param {BaiduMapMarker} marker
   *   Baidu marker.
   */
  onMarkerAdded(marker) {}

  /**
   * @param {BaiduMapMarker} marker
   *   Baidu marker.
   */
  onMarkerRemove(marker) {}

  /**
   * @param {BaiduMapMarker} marker
   *   Baidu marker.
   */
  onMarkerUpdated(marker) {}

  /**
   * @param {BaiduMapMarker} marker
   *   Baidu marker.
   */
  onMarkerClicked(marker) {}
}
