/**
 * @typedef {Object} AddressIntegrationSettings

 * @prop {String} geocoder
 * @prop {Object} settings
 * @prop {String} address_field
 * @prop {String} direction
 * @prop {String} sync_mode
 * @prop {String} geocoder_url
 * @prop {String} geocoder_reverse_url
 */

import { FieldWidgetBase } from "../../../../js/WidgetSubscriber/FieldWidgetBase.js";
import { GeolocationAddress } from "../GeolocationAddress.js";
import { GeolocationCoordinates } from "../../../../js/Base/GeolocationCoordinates.js";

export default class GeolocationAddressWidget extends FieldWidgetBase {
  initialize() {
    super.initialize();

    /**
     *
     * @type {Map<int, Function>}
     */
    this.ajaxAddressElementResolvesByIndex = new Map();

    jQuery(document).ajaxComplete(() => {
      if (!this.ajaxAddressElementResolvesByIndex.size) return;
      this.ajaxAddressElementResolvesByIndex.forEach((resolve, index) => {
        const element = this.getElementByIndex(index);
        if (element) {
          resolve(element);
          this.ajaxAddressElementResolvesByIndex.delete(index);
        }
      });
    });
  }

  onFormChange(element, index) {
    let requiredSet = true;
    element.querySelectorAll(".form-element.required").forEach((formElement) => {
      if (!formElement.value) requiredSet = false;
    });

    if (!requiredSet) return;

    super.onFormChange(element, index);
  }

  getAllInputElements(returnElements = false) {
    const hyphenatedFieldName = this.settings.field_name.replaceAll("_", "-");
    const pattern = new RegExp(`${hyphenatedFieldName}-\\d+$`);

    const elements = Array.from(this.form.querySelectorAll(`[data-drupal-selector*="${hyphenatedFieldName}-"]`)).filter((element) => {
      return pattern.test(element.getAttribute("data-drupal-selector"));
    });

    if (returnElements) {
      return elements;
    }

    const map = new Map();
    elements.forEach((element) => {
      map.set(this.getIndexByElement(element), element);
    });

    return map;
  }

  triggerAddMoreElement() {
    let error = false;
    this.getAllInputElements().forEach((element) => {
      if (element.querySelector(".form-element.error")) {
        console.error("Cannot add new element while errors present.");
        error = true;
      }
    });
    if (error) return;
    this.form.querySelector(`[name="${this.settings.field_name}_add_more"]`)?.dispatchEvent(new Event("mousedown"));
  }

  /**
   *
   * @param {GeolocationAddress} address
   *   Address.
   * @return {Promise<GeolocationCoordinates|null>}
   *   Coordinates.
   */
  addressToCoordinates(address) {
    return new Promise((resolve, reject) => {
      if (!address.countryCode) {
        resolve(null);
        return;
      }
      fetch(this.settings.geocoder_url, {
        method: "POST",
        mode: "cors",
        cache: "no-cache",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/json",
        },
        redirect: "follow",
        referrerPolicy: "no-referrer",
        body: JSON.stringify({
          geocoder: this.settings.geocoder,
          geocoder_settings: this.settings.settings,
          field_name: this.settings.field_name,
          address,
        }),
      }).then((response) => {
        if (response.ok) {
          response.json().then((coordinates) => {
            if (coordinates) {
              resolve(new GeolocationCoordinates(coordinates.lat, coordinates.lng));
            } else {
              resolve(null);
            }
          });
          return;
        }
        reject(new Error(`GeolocationAddressWidget: ${response.status} ${response.statusText}`));
      });
    });
  }

  /**
   * @param {float} latitude
   *   Latitude.
   * @param {float} longitude
   *   Longitude.
   *
   * @return {Promise<GeolocationAddress>}
   *   Address.
   */
  coordinatesToAddress(latitude, longitude) {
    return new Promise((resolve, reject) => {
      fetch(this.settings.geocoder_reverse_url, {
        method: "POST",
        mode: "cors",
        cache: "no-cache",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/json",
        },
        redirect: "follow",
        referrerPolicy: "no-referrer",
        body: JSON.stringify({
          geocoder: this.settings.geocoder,
          geocoder_settings: this.settings.settings,
          field_name: this.settings.field_name,
          latitude,
          longitude,
        }),
      }).then((response) => {
        if (response.ok) {
          response.json().then((address) => {
            resolve(new GeolocationAddress(address));
          });

          return;
        }
        reject(new Error(`GeolocationAddressWidget: ${response.status} ${response.statusText}`));
      });
    });
  }

  /**
   * @param {GeolocationAddress} address
   *   Address.
   * @param {Element} element
   *   Element.
   */
  setAddressByElement(address, element) {
    const countrySelect = element.querySelector("select.country");

    if (!address || (!address.countryCode ?? false)) {
      countrySelect.value = "";
      countrySelect.dispatchEvent(new Event("change"));
      return;
    }

    if (countrySelect.value === address.countryCode) {
      address.addressWidgetElementSelectors.forEach((selector, property) => {
        if (property === "countryCode") return;
        if (element.querySelector(selector)) element.querySelector(selector).value = address[property] ?? "";
      });

      return;
    }

    new Promise((resolve) => {
      const index = this.getIndexByElement(element);
      this.ajaxAddressElementResolvesByIndex.set(index, resolve);
    }).then((addressElement) => {
      this.setAddressByElement(address, addressElement);
    });

    countrySelect.value = address.countryCode;
    countrySelect.dispatchEvent(new Event("change"));
  }

  setCoordinatesByElement(coordinates, element) {
    if (!element) {
      console.error("Cannot set address by coordinates on non-existing element.");
    }

    if (!coordinates) {
      const countrySelect = element.querySelector("select.country");

      countrySelect.value = "";
      countrySelect.dispatchEvent(new Event("change"));

      return;
    }

    this.coordinatesToAddress(coordinates.lat, coordinates.lng).then((address) => {
      if (address.isEmpty()) {
        const warning = document.createElement("div");
        warning.innerHTML = `<p>${Drupal.t("No address found for given coordinates. Address field will stay empty for given field.")}</p>`;
        Drupal.dialog(warning, {
          title: Drupal.t("Address synchronization"),
        }).showModal();
      }
      this.setAddressByElement(address, element);
    });
  }

  getCoordinatesByElement(element) {
    if (!element) {
      return Promise.reject(new Error("GeolocationAddressWidget: Cannot get coordinates of non-existing element."));
    }

    const countryCode = element.querySelector("select.country")?.value ?? null;

    if (!countryCode) {
      return new Promise((resolve) => {
        resolve(new GeolocationCoordinates());
      });
    }

    const address = this.getAddressByElement(element);

    if (address) {
      return this.addressToCoordinates(address);
    }

    return Promise.reject(new Error("GeolocationAddressWidget: Cannot get coordinates of element without an address."));
  }

  /**
   * @param {Element} element
   *   Element.
   *
   * @return {GeolocationAddress}
   *   Address.
   */
  getAddressByElement(element) {
    const address = new GeolocationAddress();

    address.addressWidgetElementSelectors.forEach((selector, property) => {
      address[property] = element.querySelector(selector)?.value.trim() ?? null;
    });

    return address;
  }

  reorder(newOrder, source) {
    if (this.settings.direction === "one_way") return;
    super.reorder(newOrder, source);
  }

  addCoordinates(coordinates, index, source) {
    if (this.settings.direction === "one_way") return;
    super.addCoordinates(coordinates, index, source);
  }

  removeCoordinates(index, source) {
    if (this.settings.direction === "one_way") return;
    super.removeCoordinates(index, source);
  }

  alterCoordinates(coordinates, index, source) {
    if (this.settings.direction === "one_way") return;
    super.alterCoordinates(coordinates, index, source);
  }
}
