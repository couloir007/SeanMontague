import { GeolocationLayerFeature } from "../../../../js/LayerFeature/GeolocationLayerFeature.js";

/**
 * @prop {Leaflet} map
 */
export class LeafletLayerFeature extends GeolocationLayerFeature {
  /**
   * @param {LeafletMapMarker} marker
   *   Leaflet marker.
   */
  onMarkerAdded(marker) {
    super.onMarkerAdded(marker);
  }

  /**
   * @param {LeafletMapMarker} marker
   *   Leaflet marker.
   */
  onMarkerClicked(marker) {
    super.onMarkerClicked(marker);
  }

  /**
   * @param {LeafletMapMarker} marker
   *   Leaflet marker.
   */
  onMarkerRemove(marker) {
    super.onMarkerRemove(marker);
  }

  /**
   * @param {LeafletMapMarker} marker
   *   Leaflet marker.
   */
  onMarkerUpdated(marker) {
    super.onMarkerUpdated(marker);
  }
}
