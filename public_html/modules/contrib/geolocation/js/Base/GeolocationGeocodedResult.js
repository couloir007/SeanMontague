/**
 * @prop {GeolocationCoordinates} coordinates
 * @prop {GeolocationBoundaries} boundaries
 * @prop {Number} accuracy
 */
export class GeolocationGeocodedResult {
  /**
   * @param {GeolocationCoordinates} coordinates
   *   Coordinates.
   * @param {GeolocationBoundaries|null} boundaries
   *   Boundaries.
   * @param {Number} accuracy
   *   Accuracy.
   */
  constructor(coordinates, boundaries, accuracy) {
    this.coordinates = coordinates;
    this.boundaries = boundaries;
    this.accuracy = accuracy;
  }
}
