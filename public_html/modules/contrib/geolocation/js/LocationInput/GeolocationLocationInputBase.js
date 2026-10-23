/**
 * @typedef {Object} GeolocationLocationInputSettings
 *
 * @prop {Boolean} hide_form
 */
export class GeolocationLocationInputBase {
  /**
   * @class
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
    const form = this.form.querySelector("input")?.form;

    if (!form) {
      return;
    }

    // Views binds its AJAX handling to the exposed form submit buttons, so a
    // native form submit would always reload the page.
    const submitButton = [...form.querySelectorAll('input[type="submit"], button[type="submit"], input[type="image"]')].find((button) => button.dataset.drupalSelector !== "edit-reset");

    if (submitButton) {
      submitButton.click();
      return;
    }

    form.submit();
  }
}
