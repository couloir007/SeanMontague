import { GeolocationMapCenterBase } from "../MapCenter/GeolocationMapCenterBase.js";
import { GeolocationBoundaries } from "./GeolocationBoundaries.js";

export class GeolocationMapCenterBoundariesBase extends GeolocationMapCenterBase {
  /**
   * @return {boolean} boolean
   *   Success.
   */
  setCenter() {
    if (typeof this.settings.north !== "number" || typeof this.settings.east !== "number" || typeof this.settings.south !== "number" || typeof this.settings.west !== "number") {
      return false;
    }

    this.map.setBoundaries(
      new GeolocationBoundaries({
        north: this.settings.north,
        east: this.settings.east,
        south: this.settings.south,
        west: this.settings.west,
      })
    );

    return true;
  }
}
