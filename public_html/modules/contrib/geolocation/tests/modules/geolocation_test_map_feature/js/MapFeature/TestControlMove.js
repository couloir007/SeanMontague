import { GeolocationMapFeature } from "../../../../../js/MapFeature/GeolocationMapFeature.js";
import { GeolocationBoundaries } from "../../../../../js/Base/GeolocationBoundaries.js";

/**
 * @prop {Number} settings.north
 * @prop {Number} settings.east
 * @prop {Number} settings.south
 * @prop {Number} settings.west
 */
export default class TestControlMove extends GeolocationMapFeature {
  constructor(settings, map) {
    super(settings, map);

    this.map.wrapper.querySelector(".geolocation-map-control .test-move").addEventListener("click", (e) => {
      e.preventDefault();

      // As ControlCustomGeocoder does with a result.
      this.map.setBoundaries(new GeolocationBoundaries(this.settings));
    });
  }
}
