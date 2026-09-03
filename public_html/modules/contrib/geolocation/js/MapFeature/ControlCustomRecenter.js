import { GeolocationMapFeature } from "./GeolocationMapFeature.js";

export default class ControlCustomRecenter extends GeolocationMapFeature {
  /**
   * @inheritDoc
   */
  constructor(settings, map) {
    super(settings, map);

    const recenterButton = this.map.wrapper.querySelector(".geolocation-map-control .recenter");

    recenterButton.addEventListener("click", (e) => {
      e.preventDefault();

      this.map.setCenterByOptions();
    });
  }
}
