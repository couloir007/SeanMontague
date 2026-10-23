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

    const centerOnPosition = (position) => {
      this.map.setCenterByCoordinates(new GeolocationCoordinates(position.coords.latitude, position.coords.longitude));
    };

    navigator.geolocation.getCurrentPosition(centerOnPosition);

    if (this.settings.follow_client) {
      // A reused map is centered again on every rebuild.
      clearInterval(this.followInterval);
      this.followInterval = setInterval(() => {
        navigator.geolocation.getCurrentPosition(centerOnPosition);
      }, 20000);
    }

    return true;
  }

  destroy() {
    super.destroy();

    clearInterval(this.followInterval);
  }
}
