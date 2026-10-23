import { GeolocationMapFeature } from "./GeolocationMapFeature.js";

/**
 * @typedef GeolocationAjaxUpdateSettings
 *
 * @augments {GeolocationMapFeatureSettings}
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
 *   The viewport the view shows the content of.
 * @prop {Boolean} requestPending
 *   A refresh for the viewport is on its way.
 * @prop {Boolean} centeringPending
 *   The map is being centered on its content, the viewport that results is
 *   the one the view already shows.
 * @prop {Number} centeringTimer
 */
export default class GeolocationAjaxUpdate extends GeolocationMapFeature {
  constructor(settings, map) {
    super(settings, map);

    this.mapIdleTimer = null;
    this.currentBounds = null;
    this.requestPending = false;
    this.centeringPending = false;
    this.centeringTimer = null;
  }

  onMapReady() {
    super.onMapReady();

    if (this.requestPending) {
      // The rebuild for the refresh sent below, which kept the viewport. The
      // map may have been moved again meanwhile.
      this.requestPending = false;
      this.scheduleUpdate();
      return;
    }

    // Initialized, or rebuilt for another reason like the exposed form or the
    // pager: the map has just been centered on its content, which is no reason
    // to refresh that content.
    this.centeringPending = true;
    clearTimeout(this.mapIdleTimer);
    clearTimeout(this.centeringTimer);

    // Centering may not have moved the map at all, then no idle follows.
    this.centeringTimer = setTimeout(() => {
      this.centeringPending = false;
      this.currentBounds = this.map.getBoundaries();
    }, this.settings.views_refresh_delay);
  }

  destroy() {
    super.destroy();

    clearTimeout(this.mapIdleTimer);
    clearTimeout(this.centeringTimer);
  }

  onMapIdle() {
    super.onMapIdle();

    // The viewport centering ended up with is the one the view shows.
    if (this.centeringPending) {
      this.centeringPending = false;
      clearTimeout(this.centeringTimer);
      this.currentBounds = this.map.getBoundaries();
      return;
    }

    this.scheduleUpdate();
  }

  /**
   * Refresh the view for the current viewport, once the map rested for a while.
   */
  scheduleUpdate() {
    const bounds = this.map.getBoundaries();

    clearTimeout(this.mapIdleTimer);
    this.mapIdleTimer = setTimeout(() => {
      // A pending refresh picks the move up once it arrived, see onMapReady().
      if (this.requestPending || this.currentBounds?.equals(bounds)) {
        return;
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

      // The viewport is what the content gets loaded for, so centering the map
      // on that content would move it away and request yet another refresh.
      this.requestPending = true;
      this.map.keepViewport = true;

      Drupal.ajax(ajaxSettings)
        .execute()
        .fail(() => {
          // No rebuild is coming.
          this.requestPending = false;
          this.map.keepViewport = false;
          this.map.wrapper.classList.remove("ajax-loading");
        });
    }, this.settings.views_refresh_delay);
  }

  viewsAjaxSettings() {
    const ajaxViews = drupalSettings.views?.ajaxViews ?? {};

    let currentViewId = null;
    for (const [viewId, view] of Object.entries(ajaxViews)) {
      const isUpdateTarget = view.view_name === this.settings.update_view_id && view.view_display_id === this.settings.update_view_display_id;

      // The settings can outlive the view on the page.
      const isOnPage = document.querySelector(`.js-view-dom-id-${view.view_dom_id}`) !== null;

      if (isUpdateTarget && isOnPage) {
        currentViewId = viewId;
        break;
      }
    }

    if (!currentViewId) {
      console.error("Geolocation - No common map container found.");
      return;
    }

    if (typeof Drupal.views.instances[currentViewId] === "undefined") {
      return;
    }

    const ajaxSettings = structuredClone(Drupal.views.instances[currentViewId].element_settings);
    // Simplify the CSS magic during replacement.
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
