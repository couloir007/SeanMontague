/**
 * @prop {string} type
 * @prop { number[] | number[][] | number[][][] | number[][][][]} coordinates
 */
export class GeolocationGeometry {
  /**
   * @param {string} type GeoJSON geometry type, such as "Point" or "Polygon".
   * @param {array} coordinates Coordinates, nested as deeply as the type requires.
   */
  constructor(type, coordinates) {
    this.type = type;
    this.coordinates = coordinates;
  }

  /**
   * @param {GeolocationBoundaries} boundaries Boundaries
   *
   * @return {GeolocationGeometry} Geometry
   */
  static constructByBoundaries(boundaries) {
    return new GeolocationGeometry("Polygon", [
      [
        [boundaries.west, boundaries.south],
        [boundaries.east, boundaries.south],
        [boundaries.east, boundaries.north],
        [boundaries.west, boundaries.north],
        [boundaries.west, boundaries.south],
      ],
    ]);
  }
}
