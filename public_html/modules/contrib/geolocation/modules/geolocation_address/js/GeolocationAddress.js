/**
 * @prop {String} country
 * @prop {String} countryCode
 * @prop {String} organization
 * @prop {String} addressLine1
 * @prop {String} addressLine2
 * @prop {String} locality
 * @prop {String} dependentLocality
 * @prop {String} administrativeArea
 * @prop {String} postalCode
 * @prop {Map<String, String>} addressWidgetElementSelectors
 */
export class GeolocationAddress {
  constructor(data = null) {
    this.addressWidgetElementSelectors = new Map([
      ["organization", ".organization"],
      ["addressLine1", ".address-line1"],
      ["addressLine2", ".address-line2"],
      ["locality", ".locality"],
      ["administrativeArea", ".administrative-area"],
      ["postalCode", ".postal-code"],
      ["countryCode", ".country.form-select"],
    ]);

    if (data) {
      this.addressWidgetElementSelectors.forEach((selector, property) => {
        if (data[property]) {
          this[property] = data[property];
        }
      });
    }
  }

  /**
   * Empty?
   *
   * @return {boolean}
   *   Empty?
   */
  isEmpty() {
    return !this.countryCode;
  }
}
