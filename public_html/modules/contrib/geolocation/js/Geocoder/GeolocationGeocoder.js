import { GeolocationGeocodedResult } from "../Base/GeolocationGeocodedResult.js";

/**
 * @typedef {Object} GeolocationGeocoderSettings
 *
 * @prop {int} autocomplete_min_length
 */

/**
 * @prop {GeolocationGeocoderSettings} settings
 * @prop {array} resultCallbacks
 * @prop {array} clearCallbacks
 * @prop {Function?} autoCompleteRenderItem
 */
export class GeolocationGeocoder {
  constructor(settings) {
    this.settings = settings;

    this.resultCallbacks = [];
    this.clearCallbacks = [];
  }

  /**
   * @param {function(GeolocationGeocodedResult)} callback
   */
  addResultCallback(callback) {
    this.resultCallbacks.push(callback);
  }

  /**
   * @param {function()} callback
   */
  addClearCallback(callback) {
    this.resultCallbacks.push(callback);
  }

  /**
   * @param {GeolocationGeocodedResult} result
   *   Geocoded result.
   */
  resultCallback(result) {
    this.resultCallbacks.forEach((callback) => {
      callback(result);
    });
  }

  clearCallback() {
    this.clearCallbacks.forEach((callback) => {
      callback();
    });
  }

  /**
   * @interface
   *
   * @param {String} address
   *   Address to geocode.
   *
   * @return {Promise<GeolocationGeocodedResult[]>}
   *   Geocoded Result.
   */
  geocode(address) {
    return new Promise(() => {});
  }

  /**
   * @param {HTMLElement} geocoderInput
   *   Geocoder Input element.
   */
  attachToElement(geocoderInput) {
    if (!geocoderInput) {
      console.error("No geocoding input element. No Geocoding.");
      return;
    }

    if (!jQuery) {
      console.error("No jQuery present. Cannot autocomplete. No Geocoding selection.");
      return;
    }

    geocoderInput.addEventListener("input", () => {
      this.clearCallback();
    });

    const autocomplete = jQuery(geocoderInput);
    autocomplete.autocomplete({
      autoFocus: true,
      minLength: this.settings.autocomplete_min_length ?? 1,
      source: (request, response) => {
        this.autoCompleteSource(request, response);
      },

      /**
       * Option form autocomplete selected.
       *
       * @param {Object} event - See jquery doc
       * @param {Object} ui - See jquery doc
       * @param {Object} ui.item - See jquery doc
       */
      select: (event, ui) => {
        this.autoCompleteSelectHandler(event, ui);
      },
    });

    if (typeof this.autoCompleteRenderItem !== "undefined") {
      autocomplete.autocomplete("instance")._renderItem = this.autoCompleteRenderItem;
    }
  }

  /**
   * Option form autocomplete selected.
   *
   * @param {Object} request - See jquery doc
   * @param {function} response - See jquery doc
   */
  autoCompleteSource(request, response) {
    const autocompleteResults = [];

    this.geocode(request.term).then((results) => {
      results.forEach((result) => {
        autocompleteResults.push({
          value: result.label,
          geocodedResult: result.geocodedResult,
        });
      });
      response(autocompleteResults);
    });
  }

  /**
   * Option form autocomplete selected.
   *
   * @param {Object} event - See jquery doc
   * @param {Object} ui - See jquery doc
   * @param {Object} ui.item - See jquery doc
   */
  autoCompleteSelectHandler(event, ui) {
    this.resultCallback(ui.item.geocodedResult);
  }
}
