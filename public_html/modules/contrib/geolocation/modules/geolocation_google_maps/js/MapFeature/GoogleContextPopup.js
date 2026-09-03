import { GoogleMapFeature } from "./GoogleMapFeature.js";
import { GeolocationCoordinates } from "../../../../js/Base/GeolocationCoordinates.js";

/**
 * @typedef {Object} ContextPopupSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {String} content
 */

/**
 * @prop {ContextPopupSettings} settings
 * @prop {GoogleMaps} map
 */
export default class GoogleContextPopup extends GoogleMapFeature {
  constructor(settings, map) {
    super(settings, map);

    const contextContainer = document.createElement("div");
    contextContainer.classList.add("geolocation-context-popup", "hidden");
    this.contextContainer = this.map.container.appendChild(contextContainer);
  }

  /**
   * Context popup handling.
   *
   * @param {GeolocationCoordinates} location - Coordinates.
   *
   * @return {google.maps.Point} - Pixel offset against top left corner of
   *     map container.
   */
  fromLocationToPixel(location) {
    const numTiles = 2 ** this.map.googleMap.getZoom();
    const projection = this.map.googleMap.getProjection();
    const worldCoordinate = projection.fromLatLngToPoint(new google.maps.LatLng(location.lat, location.lng));
    const pixelCoordinate = new google.maps.Point(worldCoordinate.x * numTiles, worldCoordinate.y * numTiles);

    const topLeft = new google.maps.LatLng(this.map.googleMap.getBounds().getNorthEast().lat(), this.map.googleMap.getBounds().getSouthWest().lng());

    const topLeftWorldCoordinate = projection.fromLatLngToPoint(topLeft);
    const topLeftPixelCoordinate = new google.maps.Point(topLeftWorldCoordinate.x * numTiles, topLeftWorldCoordinate.y * numTiles);

    return new google.maps.Point(pixelCoordinate.x - topLeftPixelCoordinate.x, pixelCoordinate.y - topLeftPixelCoordinate.y);
  }

  onClick(location) {
    super.onClick(location);

    if (typeof this.contextContainer !== "undefined") {
      this.contextContainer.classList.add("hidden");
    }
  }

  onContextClick(location) {
    super.onContextClick(location);

    const content = Drupal.formatString(this.settings.content, {
      "@lat": location.lat,
      "@lng": location.lng,
    });

    this.contextContainer.innerHTML = content;

    if (content.length > 0) {
      const pos = this.fromLocationToPixel(location);

      this.contextContainer.classList.remove("hidden");

      this.contextContainer.style.left = `${pos.x}px`;
      this.contextContainer.style.top = `${pos.y}px`;
    }
  }
}
