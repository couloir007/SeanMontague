import { GeolocationMapCenterBase } from "./GeolocationMapCenterBase.js";
import { GeolocationCoordinates } from "../Base/GeolocationCoordinates.js";

/**
 * @prop {float} settings.initial_longitude
 * @prop {float} settings.initial_latitude
 * @prop {boolean} settings.follow_client
 */
export default class ClientLocation extends GeolocationMapCenterBase {
  setCenter() {
    super.setCenter();

    this.map.setCenterByCoordinates(new GeolocationCoordinates(this.settings.initial_latitude, this.settings.initial_longitude));

    if (!navigator.geolocation) {
      return false;
    }

    navigator.geolocation.getCurrentPosition((position) => {
      this.map.setCenterByCoordinates(new GeolocationCoordinates(position.coords.latitude, position.coords.longitude));
    });

    if (this.settings.follow_client) {
      setInterval(() => {
        navigator.geolocation.getCurrentPosition((position) => {
          this.map.setCenterByCoordinates(new GeolocationCoordinates(position.coords.latitude, position.coords.longitude));
        });
      }, 20000);
    }

    return true;
  }
}
