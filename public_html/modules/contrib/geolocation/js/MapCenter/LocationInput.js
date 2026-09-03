import { GeolocationMapCenterBase } from "./GeolocationMapCenterBase.js";

export default class Location extends GeolocationMapCenterBase {
  setCenter() {
    super.setCenter();

    return !!this.settings.success;
  }
}
