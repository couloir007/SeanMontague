import { GeolocationMapFeature } from "./GeolocationMapFeature.js";
import { GeolocationBoundaries } from "../Base/GeolocationBoundaries.js";

/**
 * @typedef GeolocationAjaxUpdateSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {Boolean} boundary_filter
 * @prop {Boolean} hide_form
 * @prop {Number} views_refresh_delay
 * @prop {String} parameter_identifier
 * @prop {String} update_view_id
 * @prop {String} update_view_display_id
 */

/**
 * @prop {GeolocationAjaxUpdateSettings} settings
 * @prop {Number} mapIdleTimer
 * @prop {GeolocationBoundaries} currentBounds
 */
export default class GeolocationAjaxUpdate extends GeolocationMapFeature {
  constructor(settings, map) {
    super(settings, map);

    this.mapIdleTimer = null;
    this.currentBounds = null;
  }

  onMapIdle() {
    super.onMapIdle();

    const bounds = this.map.getBoundaries();

    clearTimeout(this.mapIdleTimer);
    this.mapIdleTimer = setTimeout(() => {
      if (this.map.updatingBounds) {
        return;
      }

      if (this.currentBounds instanceof GeolocationBoundaries) {
        if (this.currentBounds.equals(bounds)) {
          return;
        }
      }

      const ajaxSettings = this.viewsAjaxSettings();
      if (!ajaxSettings) {
        return;
      }

      this.currentBounds = bounds;

      ajaxSettings.submit[`${this.settings.parameter_identifier}[lat_north_east]`] = bounds.north;
      ajaxSettings.submit[`${this.settings.parameter_identifier}[lng_north_east]`] = bounds.east;
      ajaxSettings.submit[`${this.settings.parameter_identifier}[lat_south_west]`] = bounds.south;
      ajaxSettings.submit[`${this.settings.parameter_identifier}[lng_south_west]`] = bounds.west;

      this.map.wrapper.classList.add("ajax-loading");

      Drupal.ajax(ajaxSettings).execute();
    }, this.settings.views_refresh_delay);
  }

  viewsAjaxSettings() {
    // Make sure to load current form DOM element, which will change after every AJAX operation.
    const view = document.querySelector(`.view-id-${this.settings.update_view_id}.view-display-id-${this.settings.update_view_display_id}`);
    if (!view) {
      console.error("Geolocation - No common map container found.");
      return;
    }

    // Extract the view DOM ID from the view classes.
    const currentViewId = /(js-view-dom-id-\w+)/.exec(view.classList.toString())[1].replace("js-view-dom-id-", "views_dom_id:");

    if (typeof Drupal.views.instances[currentViewId] === "undefined") {
      return;
    }

    const ajaxSettings = { ...Drupal.views.instances[currentViewId].element_settings };
    ajaxSettings.progress.type = "none";

    const exposedForm = document.querySelector(`form#views-exposed-form-${this.settings.update_view_id.replace(/_/g, "-")}-${this.settings.update_view_display_id.replace(/_/g, "-")}`);
    if (exposedForm) {
      const formData = new FormData(exposedForm);
      formData.forEach((value, key) => {
        if (ajaxSettings.submit[key]) {
          value = formData.getAll(key);
        }

        ajaxSettings.submit = {
          ...ajaxSettings.submit,
          ...{
            [key]: value,
          },
        };
      });
    }

    return ajaxSettings;
  }
}
