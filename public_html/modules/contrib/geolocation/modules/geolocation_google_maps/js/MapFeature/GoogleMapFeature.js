import { GeolocationMapFeature } from "../../../../js/MapFeature/GeolocationMapFeature.js";

/**
 * @prop {GoogleMaps} map
 */
export class GoogleMapFeature extends GeolocationMapFeature {
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
