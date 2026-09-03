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

    const attributionBlock = document.querySelector("#geolocation-google-places-api-attribution");
    if (!attributionBlock) {
      console.error("Geolocation Google Places API attribution block missing.");
      return;
    }

    this.PlacesService = new google.maps.places.PlacesService(attributionBlock ?? null);
    this.AutocompleteSessionToken = new google.maps.places.AutocompleteSessionToken();
    this.AutocompleteService = new google.maps.places.AutocompleteService();
  }

  autoCompleteSource(request, response) {
    const autocompleteResults = [];

    const parameters = {
      input: request.term,
      sessionToken: this.AutocompleteSessionToken,
    };

    if (this.settings.component_restrictions) {
      parameters.componentRestrictions = {};
      if (this.settings.component_restrictions.administrative_area) {
        parameters.componentRestrictions.administrativeArea = this.settings.component_restrictions.administrative_area;
      }
      if (this.settings.component_restrictions.country) {
        if (Array.isArray(this.settings.component_restrictions.country)) {
          parameters.componentRestrictions.country = this.settings.component_restrictions.country;
        } else {
          parameters.componentRestrictions.country = this.settings.component_restrictions.country.split(",");
        }
      }
      if (this.settings.component_restrictions.locality) {
        parameters.componentRestrictions.locality = this.settings.component_restrictions.locality;
      }
      if (this.settings.component_restrictions.postal_code) {
        parameters.componentRestrictions.postalCode = this.settings.component_restrictions.postal_code;
      }
      if (this.settings.component_restrictions.route) {
        parameters.componentRestrictions.route = this.settings.component_restrictions.route;
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

    this.AutocompleteService.getPlacePredictions(
      parameters,

      (results, status) => {
        if (status === google.maps.places.PlacesServiceStatus.OK) {
          results.forEach((result) => {
            autocompleteResults.push({
              value: result.description,
              place_id: result.place_id,
              classes: result.types.reverse(),
            });
          });
        }
        response(autocompleteResults);
      }
    );
  }

  autoCompleteSelectHandler(event, ui) {
    this.PlacesService.getDetails(
      {
        placeId: ui.item.place_id,
        fields: ["name", "geometry"],
      },

      (place, status) => {
        if (status === google.maps.places.PlacesServiceStatus.OK) {
          if (typeof place.geometry.location === "undefined") {
            return;
          }

          this.resultCallback(this.googlePlaceToGeocodedResult(place));
        }
      }
    );
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
   * @param {google.maps.places.PlaceResult} place
   *   PlaceResult.
   *
   * @return {GeolocationGeocodedResult}
   *   Result.
   */
  googlePlaceToGeocodedResult(place) {
    let bounds = null;
    if (place.geometry.viewport) {
      bounds = new GeolocationBoundaries({
        north: place.geometry.viewport.getNorthEast().lat(),
        east: place.geometry.viewport.getNorthEast().lng(),
        south: place.geometry.viewport.getSouthWest().lat(),
        west: place.geometry.viewport.getSouthWest().lng(),
      });
    }

    const coordinates = new GeolocationCoordinates(place.geometry.location.lat(), place.geometry.location.lng());

    return new GeolocationGeocodedResult(coordinates, bounds, 0);
  }

  geocode(address) {
    return new Promise((resolve) => {
      const parameters = {
        input: address,
        sessionToken: this.sessionToken,
      };

      if (typeof this.settings.componentRestrictions !== "undefined") {
        if (this.settings.componentRestrictions) {
          parameters.componentRestrictions = this.settings.componentRestrictions;
        }
      }

      this.PlacesService.findPlaceFromQuery(
        {
          query: address,
          fields: ["name", "geometry"],
        },
        (results, status) => {
          if (status === google.maps.places.PlacesServiceStatus.OK) {
            const place = results.pop();
            if (typeof place.geometry.location === "undefined") {
              return;
            }

            resolve(this.googlePlaceToGeocodedResult(place));
          }
        }
      );
    });
  }
}
