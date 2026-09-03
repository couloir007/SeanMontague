/**
 * @typedef {Object} GeolocationLocationInputSettings
 *
 * @prop {Boolean} hide_form
 */
export class GeolocationLocationInputBase {
  /**
   * @constructor
   *
   * @param {Element} form
   *   Form.
   * @param {GeolocationLocationInputSettings} settings
   *   Settings.
   */
  constructor(form, settings = {}) {
    this.form = form;
    this.settings = settings;

    if (this.settings.hide_form ?? false) {
      this.form.querySelector(".geolocation-location-input-coordinates")?.classList.add("hidden");
    }
  }

  /**
   * @param {GeolocationCoordinates|null} coordinates
   *   Coordinates.
   */
  setCoordinates(coordinates) {
    if (coordinates) {
      this.form.querySelector('input[name$="[lat]"], input[name$="lat"]').value = coordinates.lat;
      this.form.querySelector('input[name$="[lng]"], input[name$="lng"]').value = coordinates.lng;
    } else {
      this.form.querySelector('input[name$="[lat]"], input[name$="lat"]').value = "";
      this.form.querySelector('input[name$="[lng]"], input[name$="lng"]').value = "";
    }
  }

  submit() {
    this.form.querySelector("input")?.form?.submit();
  }
}
