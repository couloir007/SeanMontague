/**
 * @prop {string} type
 * @prop { number[] | number[][] | number[][][] | number[][][][]} coordinates
 */
export class GeolocationGeometry {
  /**
   * @param {string} type
   * @param {array} coordinates
   */
  constructor(type, coordinates) {
    this.type = type;
    this.coordinates = coordinates;
  }
}
