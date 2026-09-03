/**
 * @typedef {Object} GoogleGeocoderComponentRestrictions
 *
 * @prop {String} administrative_area
 * @prop {String} country
 * @prop {String} locality
 * @prop {String} postal_code
 * @prop {String} route
 */

/**
 * @typedef {Object} GoogleGeocoderBoundaryRestrictions
 *
 * @prop {String} east
 * @prop {String} south
 * @prop {String} west
 * @prop {String} north
 */

import { GeolocationGeocoder } from "../../../../js/Geocoder/GeolocationGeocoder.js";
import { GeolocationGeocodedResult } from "../../../../js/Base/GeolocationGeocodedResult.js";
import { GeolocationCoordinates } from "../../../../js/Base/GeolocationCoordinates.js";
import { GeolocationBoundaries } from "../../../../js/Base/GeolocationBoundaries.js";

/**
 * @prop {String} settings.google_api_url
 * @prop {GoogleGeocoderComponentRestrictions} settings.component_restrictions
 * @prop {GoogleGeocoderBoundaryRestrictions} settings.boundary_restriction
 * @prop {String} settings.region
 * @prop {String} settings.label
 */
export default class GoogleGeocodingAPI extends GeolocationGeocoder {
  constructor(settings) {
    super(settings);

    if (typeof google !== "undefined" && typeof google.maps !== "undefined" && typeof google.maps.Geocoder !== "undefined") {
      this.geocoder = new google.maps.Geocoder();
    } else {
      // Use the same callback mechanism as the map provider to avoid loading
      // Google Maps API twice with different parameters.
      Drupal.geolocation.maps.addMapProviderCallback("Google", () => {
        this.geocoder = new google.maps.Geocoder();
      });
      Drupal.geolocation.addScript(this.settings.google_api_url, true);
    }
  }

  geocode(address) {
    return new Promise((resolve) => {
      const results = [];

      const parameters = {
        address,
      };

      if (this.settings.component_restrictions) {
        parameters.componentRestrictions = {};
        if (this.settings.component_restrictions.administrative_area) {
          parameters.componentRestrictions.administrativeArea = this.settings.component_restrictions.administrative_area;
        }
        if (this.settings.component_restrictions.country) {
          parameters.componentRestrictions.country = this.settings.component_restrictions.country;
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
        parameters.bounds = new google.maps.LatLngBounds(
          { lat: parseFloat(this.settings.boundary_restriction.south), lng: parseFloat(this.settings.boundary_restriction.west) },
          { lat: parseFloat(this.settings.boundary_restriction.north), lng: parseFloat(this.settings.boundary_restriction.east) }
        );
      }

      if (this.settings.region) {
        parameters.region = this.settings.region;
      }

      this.geocoder
        .geocode(parameters)
        .then((googleGeocoderResponse) => {
          googleGeocoderResponse.results.forEach((result) => {
            let bounds = null;
            if (result.geometry.bounds) {
              bounds = new GeolocationBoundaries({
                north: result.geometry.bounds.getNorthEast().lat(),
                east: result.geometry.bounds.getNorthEast().lng(),
                south: result.geometry.bounds.getSouthWest().lat(),
                west: result.geometry.bounds.getSouthWest().lng(),
              });
            } else if (result.geometry.viewport) {
              bounds = new GeolocationBoundaries({
                north: result.geometry.viewport.getNorthEast().lat(),
                east: result.geometry.viewport.getNorthEast().lng(),
                south: result.geometry.viewport.getSouthWest().lat(),
                west: result.geometry.viewport.getSouthWest().lng(),
              });
            }

            const coordinates = new GeolocationCoordinates(result.geometry.location.lat(), result.geometry.location.lng());
            const accuracy = result.geometry.accuracy ?? null;

            results.push({
              label: result.formatted_address,
              geocodedResult: new GeolocationGeocodedResult(coordinates, bounds, accuracy),
            });
          });
          resolve(results);
        })
        .catch((reason) => {
          console.error(`Geolocation - GoogleGeocodingAPI: ${reason}`);
        });
    });
  }
}
