import { GeolocationGeocoder } from "../../../../js/Geocoder/GeolocationGeocoder.js";
import { GeolocationGeocodedResult } from "../../../../js/Base/GeolocationGeocodedResult.js";
import { GeolocationCoordinates } from "../../../../js/Base/GeolocationCoordinates.js";
import { GeolocationBoundaries } from "../../../../js/Base/GeolocationBoundaries.js";

/**
 * @typedef {Object} PhotonResult
 *
 * @prop {String} type
 *
 * @prop {Object} geometry
 * @prop {String} geometry.type
 *
 * @prop {float[]} geometry.coordinates
 *
 * @prop {Object} properties
 * @prop {float[]} properties.extent
 * @prop {String} properties.street
 * @prop {String} properties.city
 * @prop {String} properties.state
 * @prop {String} properties.postcode
 * @prop {String} properties.country
 * @prop {String} properties.housenumber
 */

/**
 * @typedef {Object} PhotonSettings
 *
 * @prop {Object} location_priority
 * @prop {float} location_priority.lat
 * @prop {float} location_priority.lng
 *
 * @prop {Object} bbox
 * @prop {float} bbox.lat_north_east
 * @prop {float} bbox.lng_north_east
 * @prop {float} bbox.lat_south_west
 * @prop {float} bbox.lng_south_west
 *
 * @prop {Boolean} remove_duplicates
 */

/**
 * Photon request timeout in milliseconds.
 *
 * @type {int}
 */
const requestTimeout = 5000;

/**
 * @prop {PhotonSettings} settings
 */
export default class Photon extends GeolocationGeocoder {
  geocode(address) {
    return new Promise((resolve) => {
      const results = [];

      const url = new URL("https://photon.komoot.io/api/");
      url.searchParams.append("q", address);
      url.searchParams.append("limit", "3");

      if (["de", "en", "fr"].includes(document.documentElement.lang)) {
        url.searchParams.append("lang", document.documentElement.lang);
      }

      if (this.settings.location_priority.lat && this.settings.location_priority.lng) {
        url.searchParams.append("lat", this.settings.location_priority.lat.toString());
        url.searchParams.append("lon", this.settings.location_priority.lng.toString());
      }

      const bbox = this.settings.bbox ?? {};
      if (bbox.lng_south_west && bbox.lat_south_west && bbox.lng_north_east && bbox.lat_north_east) {
        url.searchParams.append("bbox", [bbox.lng_south_west, bbox.lat_south_west, bbox.lng_north_east, bbox.lat_north_east].join(","));
      }

      fetch(url, { signal: AbortSignal.timeout(requestTimeout) })
        .then((response) => response.json())
        .then((data) => {
          if (typeof data.features === "undefined") {
            resolve(results);
            return;
          }

          /**
           * @param {int} index
           * @param {PhotonResult} feature
           */
          data.features.forEach((feature) => {
            if (!feature.geometry.coordinates) {
              return;
            }

            const addressParts = [];
            if (feature.properties.street) {
              addressParts.push(feature.properties.street + (feature.properties.housenumber ?? " "));
            }
            if (feature.properties.city) {
              addressParts.push(feature.properties.city);
            }
            if (feature.properties.state) {
              addressParts.push(feature.properties.state);
            }
            if (feature.properties.postcode) {
              addressParts.push(feature.properties.postcode);
            }
            if (feature.properties.country) {
              addressParts.push(feature.properties.country);
            }
            const formattedAddress = (feature.properties.name ? `${feature.properties.name} - ` : "") + addressParts.join(", ");

            const coordinates = new GeolocationCoordinates(feature.geometry.coordinates[1], feature.geometry.coordinates[0]);

            const bounds = !feature.properties.extent
              ? null
              : new GeolocationBoundaries({
                  north: feature.properties.extent[1],
                  east: feature.properties.extent[2],
                  south: feature.properties.extent[3],
                  west: feature.properties.extent[0],
                });

            if (this.settings.remove_duplicates) {
              if (
                !results.find((item) => {
                  return item.label === formattedAddress;
                })
              ) {
                results.push({
                  label: formattedAddress,
                  geocodedResult: new GeolocationGeocodedResult(coordinates, bounds, 0),
                });
              }
            } else {
              results.push({
                label: formattedAddress,
                geocodedResult: new GeolocationGeocodedResult(coordinates, bounds, 0),
              });
            }
          });
          resolve(results);
        })
        .catch((error) => {
          const message = new Drupal.Message();
          const messageId = "geolocation-photon-error";

          if (!message.select(messageId)) {
            message.add(error.name === "TimeoutError" ? Drupal.t("Address lookup timed out. Please try again or enter coordinates manually.") : Drupal.t("Address lookup failed. Please try again or enter coordinates manually."), {
              type: "warning",
              id: messageId,
            });
          }

          console.error(`Geolocation - Photon: ${error}`);

          // Resolve empty, so the autocomplete gets its response and stops loading.
          resolve([]);
        });
    });
  }
}
