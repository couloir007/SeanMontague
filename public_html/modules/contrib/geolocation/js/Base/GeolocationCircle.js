/**
 * @typedef {Object} GeolocationCircleSettings
 *
 * @prop {String} [strokeColor]
 * @prop {Number} [strokeOpacity]
 * @prop {Number} [strokeWidth]
 * @prop {String} [fillColor]
 * @prop {Number} [fillOpacity]
 */

import { GeolocationCoordinates } from "./GeolocationCoordinates.js";

/**
 * @prop {GeolocationCoordinates} center
 * @prop {int} radius
 * @prop {GeolocationMapBase} map
 * @prop {GeolocationCircleSettings} settings
 */
export class GeolocationCircle {
  /**
   * @param {GeolocationCoordinates} center
   * @param {int} radius
   * @param {GeolocationMapBase} map
   * @param {GeolocationCircleSettings} settings
   */
  constructor(center, radius, map, settings = {}) {
    this.center = center;
    this.radius = radius;

    this.map = map;

    this.strokeColor = settings.strokeColor ?? "#4285F4";
    this.strokeOpacity = settings.strokeOpacity ?? 0.3;
    this.strokeWidth = settings.strokeWidth ?? 1;
    this.fillColor = settings.fillColor ?? "#4285F4";
    this.fillOpacity = settings.fillOpacity ?? 0.15;
  }

  /**
   * @param {GeolocationCoordinates} center
   * @param {int} radius
   * @param {GeolocationCircleSettings} [settings]
   */
  update(center, radius, settings = {}) {
    if (center) {
      this.center = center;
    }

    if (radius) {
      this.radius = radius;
    }

    if (settings) {
      this.strokeColor = settings.strokeColor ?? this.strokeColor ?? "#4285F4";
      this.strokeOpacity = settings.strokeOpacity ?? this.strokeOpacity ?? 0.3;
      this.strokeWidth = settings.strokeWidth ?? this.strokeWidth ?? 1;
      this.fillColor = settings.fillColor ?? this.fillColor ?? "#4285F4";
      this.fillOpacity = settings.fillOpacity ?? this.fillOpacity ?? 0.15;
    }
  }

  remove() {}
}
