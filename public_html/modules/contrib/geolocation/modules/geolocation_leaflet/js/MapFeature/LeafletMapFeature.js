import { GeolocationMapFeature } from "../../../../js/MapFeature/GeolocationMapFeature.js";

/**
 * @prop {L.Map} map.leafletMap
 */
export class LeafletMapFeature extends GeolocationMapFeature {
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

  /**
   * @param {String?} position
   *   Requested control position. Falls back to "bottomright" if unknown.
   *
   * @return {String}
   *   Leaflet control position.
   */
  getLeafletPosition(position = null) {
    return this.map.getLeafletPosition(position, "bottomright");
  }
}
