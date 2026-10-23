import { GeolocationGeocoder } from "../../../../../../js/Geocoder/GeolocationGeocoder.js";
import { GeolocationGeocodedResult } from "../../../../../../js/Base/GeolocationGeocodedResult.js";
import { GeolocationCoordinates } from "../../../../../../js/Base/GeolocationCoordinates.js";
import { GeolocationBoundaries } from "../../../../../../js/Base/GeolocationBoundaries.js";

/**
 * @prop {String} settings.google_api_url
 */
export default class GooglePlacesAPI extends GeolocationGeocoder {
  constructor(settings) {
    super(settings);

    this.AutocompleteSessionToken = new google.maps.places.AutocompleteSessionToken();
  }

  autoCompleteSource(request, response) {
    const autocompleteResults = [];

    const parameters = {
      input: request.term,
      sessionToken: this.AutocompleteSessionToken,
    };

    if (this.settings.component_restrictions) {
      if (this.settings.component_restrictions.country) {
        parameters.includedRegionCodes = Array.isArray(this.settings.component_restrictions.country) ? this.settings.component_restrictions.country : this.settings.component_restrictions.country.split(",");
      }
    }

    if (this.settings.boundary_restriction) {
      parameters.locationRestriction = new google.maps.LatLngBounds(
        { lat: parseFloat(this.settings.boundary_restriction.south), lng: parseFloat(this.settings.boundary_restriction.west) },
        { lat: parseFloat(this.settings.boundary_restriction.north), lng: parseFloat(this.settings.boundary_restriction.east) }
      );
    }

    if (this.settings.region) {
      parameters.region = this.settings.region;
    }

    google.maps.places.AutocompleteSuggestion.fetchAutocompleteSuggestions(parameters)
      .then(({ suggestions }) => {
        suggestions.forEach(({ placePrediction }) => {
          autocompleteResults.push({
            value: placePrediction.text.toString(),
            place_id: placePrediction.placeId,
            classes: placePrediction.types ? placePrediction.types.slice().reverse() : [],
          });
        });
        response(autocompleteResults);
      })
      .catch(() => {
        response(autocompleteResults);
      });
  }

  autoCompleteSelectHandler(event, ui) {
    const place = new google.maps.places.Place({ id: ui.item.place_id });
    place.fetchFields({ fields: ["displayName", "location", "viewport"] }).then(() => {
      if (!place.location) {
        return;
      }
      this.resultCallback(this.googlePlaceToGeocodedResult(place));
    });
  }

  autoCompleteRenderItem(ul, item) {
    const iconElement = document.createElement("div");
    iconElement.classList.add("geolocation-geocoder-item");
    iconElement.classList.add(...item.classes);
    iconElement.textContent = item.label;

    const iconContainer = document.createElement("div");
    iconContainer.append(iconElement);

    const listElement = document.createElement("li");
    listElement.setAttribute("data-value", item.value);
    listElement.append(iconContainer);

    ul.append(listElement);

    return jQuery(listElement);
  }

  /**
   * @param {google.maps.places.Place} place
   *   Place.
   *
   * @return {GeolocationGeocodedResult}
   *   Result.
   */
  googlePlaceToGeocodedResult(place) {
    let bounds = null;
    if (place.viewport) {
      bounds = new GeolocationBoundaries({
        north: place.viewport.getNorthEast().lat(),
        east: place.viewport.getNorthEast().lng(),
        south: place.viewport.getSouthWest().lat(),
        west: place.viewport.getSouthWest().lng(),
      });
    }

    const coordinates = new GeolocationCoordinates(place.location.lat(), place.location.lng());

    return new GeolocationGeocodedResult(coordinates, bounds, 0);
  }

  geocode(address) {
    return new Promise((resolve) => {
      google.maps.places.Place.searchByText({
        textQuery: address,
        fields: ["displayName", "location", "viewport"],
      }).then(({ places }) => {
        if (!places || places.length === 0) {
          return;
        }
        const place = places[0];
        if (!place.location) {
          return;
        }
        resolve(this.googlePlaceToGeocodedResult(place));
      });
    });
  }
}
