import { GeolocationMapFeature } from "./GeolocationMapFeature.js";
import { GeolocationCoordinates } from "../Base/GeolocationCoordinates.js";

export default class ControlCustomLocate extends GeolocationMapFeature {
  constructor(settings, map) {
    super(settings, map);

    const locateButton = this.map.wrapper.querySelector(".geolocation-map-control .locate");

    if (navigator.geolocation && window.location.protocol === "https:") {
      locateButton.addEventListener(
        "click",
        (e) => {
          navigator.geolocation.getCurrentPosition((currentPosition) => {
            this.map.setCenterByCoordinates(new GeolocationCoordinates(currentPosition.coords.latitude, currentPosition.coords.longitude), currentPosition.coords.accuracy);
          });
          e.preventDefault();
        },
        false
      );
    } else {
      locateButton.parentNode.removeChild(locateButton);
    }
  }
}
