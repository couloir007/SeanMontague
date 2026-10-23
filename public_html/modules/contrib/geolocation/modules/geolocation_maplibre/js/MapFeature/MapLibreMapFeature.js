import { GeolocationMapFeature } from "../../../../js/MapFeature/GeolocationMapFeature.js";

/**
 * @prop {MapLibre} map
 */
export class MapLibreMapFeature extends GeolocationMapFeature {
  /**
   * @return {String}
   *   One of MapLibre's four corners.
   */
  getPosition() {
    return this.settings.position ?? "top-left";
  }
}
