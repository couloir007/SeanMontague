/**
 * @prop {Number} lat
 * @prop {Number} lng
 */
export class GeolocationCoordinates {
  constructor(lat, lng) {
    this.lat = Number(lat);
    this.lng = Number(lng);
  }

  equals(lat, lng) {
    return Number(lat) === this.lat && Number(lng) === this.lng;
  }
}
