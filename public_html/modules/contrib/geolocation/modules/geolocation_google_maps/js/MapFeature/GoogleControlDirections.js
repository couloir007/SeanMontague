import { GoogleMapFeature } from "./GoogleMapFeature.js";

/**
 * @typedef {Object} MapFeatureDirectionsSettings
 *
 * @extends {GeolocationMapFeatureSettings}

 * @prop {string} directions_container_custom_id
 */

/**
 * @prop {MapFeatureDirectionsSettings} settings
 * @prop {GoogleMaps} map
 */
export default class GoogleControlDirections extends GoogleMapFeature {
  constructor(settings, map) {
    super(settings, map);

    const form = this.map.wrapper.querySelector(".geolocation-google-maps-directions-controls");
    if (!form) {
      return;
    }

    const directionsService = new google.maps.DirectionsService();
    const directionsRenderer = new google.maps.DirectionsRenderer();

    const directionsContainerSelector = this.settings.directions_container_custom_id ? this.settings.directions_container_custom_id : ".geolocation-google-maps-directions-container";

    directionsRenderer.setMap(this.map.googleMap);
    directionsRenderer.setPanel(this.map.wrapper.querySelector(directionsContainerSelector));

    form.addEventListener("submit", (event) => {
      event.preventDefault();

      const formData = new FormData(form);

      let travelMode;
      switch (formData.get("geolocation-google-maps-directions-controls-travel-mode")) {
        case "bicycling":
          travelMode = google.maps.TravelMode.BICYCLING;
          break;

        case "transit":
          travelMode = google.maps.TravelMode.TRANSIT;
          break;

        case "walking":
          travelMode = google.maps.TravelMode.WALKING;
          break;

        case "driving":
        default:
          travelMode = google.maps.TravelMode.DRIVING;
      }

      const origin = formData.get("geolocation-google-maps-directions-controls-origin");
      const destination = formData.get("geolocation-google-maps-directions-controls-destination");

      const directionsContainer = this.map.wrapper.querySelector(directionsContainerSelector);
      directionsContainer.innerHTML = "";

      if (!origin || !destination) {
        directionsContainer.innerHTML = "Origin or destination missing.";
        directionsContainer.style.background = "#F0022";
        return;
      }

      directionsService.route(
        {
          origin,
          destination,
          travelMode,
        },
        (result, status) => {
          switch (status) {
            case google.maps.DirectionsStatus.OK:
              directionsRenderer.setDirections(result);
              break;

            case google.maps.DirectionsStatus.NOT_FOUND:
              directionsContainer.innerHTML = "Could not identify the address entered.";
              break;

            case google.maps.DirectionsStatus.ZERO_RESULTS:
              directionsContainer.innerHTML = "No routes found.";
              break;

            case google.maps.DirectionsStatus.REQUEST_DENIED:
              directionsContainer.innerHTML = "Request denied. Directions API not enabled?";
              break;

            case google.maps.DirectionsStatus.UNKNOWN_ERROR:
              directionsContainer.innerHTML = "Unknown error.";
              break;

            case google.maps.DirectionsStatus.OVER_QUERY_LIMIT:
              directionsContainer.innerHTML = "Over query limit.";
              break;

            default:
              throw new Error("Google DirectionStatus: unknown status");
          }
        }
      );
    });
  }
}
