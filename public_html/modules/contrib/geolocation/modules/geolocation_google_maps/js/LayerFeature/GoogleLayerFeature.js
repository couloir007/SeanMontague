import { GeolocationLayerFeature } from "../../../../js/LayerFeature/GeolocationLayerFeature.js";

/**
 * @prop {GoogleMaps} layer.map
 */
export class GoogleLayerFeature extends GeolocationLayerFeature {
  /**
   * @param {GoogleMapMarker} marker
   *   Google marker.
   */
  onMarkerAdded(marker) {
    super.onMarkerAdded(marker);
  }

  /**
   * @param {GoogleMapMarker} marker
   *   Google marker.
   */
  onMarkerRemove(marker) {
    super.onMarkerRemove(marker);
  }

  /**
   * @param {GoogleMapMarker} marker
   *   Google marker.
   */
  onMarkerUpdated(marker) {
    super.onMarkerUpdated(marker);
  }

  /**
   * @param {GoogleMapMarker} marker
   *   Google marker.
   */
  onMarkerClicked(marker) {
    super.onMarkerClicked(marker);
  }
}
