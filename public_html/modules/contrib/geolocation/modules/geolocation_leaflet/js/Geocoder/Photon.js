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
 * @prop {float} location_priority.lon
 * @prop {Boolean} remove_duplicates
 */

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

      if (this.settings.location_priority.lat && this.settings.location_priority.lon) {
        url.searchParams.append("lat", this.settings.location_priority.lat.toString());
        url.searchParams.append("lon", this.settings.location_priority.lon.toString());
      }

      fetch(url)
        .then((response) => response.json())
        .then((data) => {
          if (typeof data.features === "undefined") {
            resolve(results);
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
        });
    });
  }
}
