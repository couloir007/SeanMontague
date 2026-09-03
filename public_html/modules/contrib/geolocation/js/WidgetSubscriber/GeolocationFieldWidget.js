/**
 * @name GeolocationWidgetSettings
 *
 * @prop {String} autoClientLocationMarker
 * @prop {String} id
 * @prop {String} type
 * @prop {String} fieldName
 * @prop {String} cardinality
 */

import { FieldWidgetBase } from "./FieldWidgetBase.js";
import { GeolocationCoordinates } from "../Base/GeolocationCoordinates.js";

export default class GeolocationFieldWidget extends FieldWidgetBase {
  setCoordinatesByElement(coordinates, element) {
    const latitudeInput = element.querySelector("input.geolocation-input-latitude");
    const longitudeInput = element.querySelector("input.geolocation-input-longitude");

    if (!longitudeInput || !latitudeInput) {
      return;
    }

    if (coordinates) {
      latitudeInput.value = coordinates.lat;
      latitudeInput.setAttribute("data-geolocation-current-value", coordinates.lat.toString());

      longitudeInput.value = coordinates.lng;
      longitudeInput.setAttribute("data-geolocation-current-value", coordinates.lng.toString());
    } else {
      latitudeInput.value = "";
      latitudeInput.setAttribute("data-geolocation-current-value", "");

      longitudeInput.value = "";
      longitudeInput.setAttribute("data-geolocation-current-value", "");
    }
  }

  getElementSelectorByIndex(index) {
    return `${this.getElementSelector()}[data-geolocation-widget-index='${index.toString()}']`;
  }

  getCoordinatesByElement(element) {
    const latitude = element.querySelector("input.geolocation-input-latitude");
    const longitude = element.querySelector("input.geolocation-input-longitude");

    if (!longitude || !latitude) {
      return Promise.reject(new Error("GeolocationFieldWidget: Cannot get coordinates by element as Latitude or longitude are not present."));
    }

    if (longitude.value === "" || latitude.value === "") {
      return Promise.reject(new Error("GeolocationFieldWidget: Cannot get coordinates by element as Latitude or longitude are not set."));
    }

    return Promise.resolve(new GeolocationCoordinates(latitude.value, longitude.value));
  }

  alterCoordinates(coordinates, index, source) {
    this.getElementByIndex(index).then((element) => {
      this.getCoordinatesByElement(element)
        .then((currentCoordinates) => {
          if (currentCoordinates === coordinates) {
            return;
          }

          super.alterCoordinates(coordinates, index, source);
        })
        .catch(() => {
          super.alterCoordinates(coordinates, index, source);
        });
    });
  }
}
