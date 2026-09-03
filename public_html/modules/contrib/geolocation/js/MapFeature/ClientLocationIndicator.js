import { GeolocationMapFeature } from "./GeolocationMapFeature.js";
import { GeolocationCoordinates } from "../Base/GeolocationCoordinates.js";

/**
 * @prop {String} settings.icon_path
 */
export default class ClientLocationIndicator extends GeolocationMapFeature {
  constructor(settings, map) {
    super(settings, map);

    if (!navigator.geolocation) {
      return;
    }

    /** @type {GeolocationMapMarker} */
    let clientLocationMarker;

    /** @type {GeolocationCircle} */
    let indicatorCircle;

    /** @type {GeolocationCoordinates} */
    let currentCoordinates;

    setInterval(() => {
      navigator.geolocation.getCurrentPosition((currentPosition) => {
        currentCoordinates = new GeolocationCoordinates(currentPosition.coords.latitude, currentPosition.coords.longitude);

        if (!clientLocationMarker) {
          clientLocationMarker = this.map.createMarker(currentCoordinates, {
            id: "current-location",
            title: Drupal.t("Current location"),
            icon: drupalSettings.path.baseUrl + settings.icon_path,
          });
        } else {
          clientLocationMarker.update(currentCoordinates);
        }

        if (indicatorCircle) {
          indicatorCircle.update(currentCoordinates, parseInt(currentPosition.coords.accuracy.toString()));
        } else {
          indicatorCircle = this.map.createCircle(currentCoordinates, parseInt(currentPosition.coords.accuracy.toString()));
        }
      });
    }, 20000);
  }
}
