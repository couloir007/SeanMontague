import { GeolocationLocationInputBase } from "./GeolocationLocationInputBase.js";
import { GeolocationCoordinates } from "../Base/GeolocationCoordinates.js";

/**
 * @prop {Boolean} settings.auto_submit
 */
export default class ClientLocation extends GeolocationLocationInputBase {
  constructor(form, settings = {}) {
    super(form, settings);

    if (!navigator.geolocation) {
      return;
    }

    navigator.geolocation.getCurrentPosition((position) => {
      this.setCoordinates(new GeolocationCoordinates(position.coords.latitude, position.coords.longitude));
    });

    const locateButton = form.querySelector(".geolocation-location-input-client-location");
    if (!locateButton) {
      return;
    }
    locateButton.classList.remove("js-hide");
    locateButton.addEventListener("click", (event) => {
      event.preventDefault();
      navigator.geolocation.getCurrentPosition((position) => {
        this.setCoordinates(new GeolocationCoordinates(position.coords.latitude, position.coords.longitude));
      });
    });
  }

  setCoordinates(coordinates) {
    super.setCoordinates(coordinates);

    if (this.settings.auto_submit ?? false) {
      this.form.querySelector("input").form.submit();
    }
  }
}
