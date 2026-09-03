/**
 * @typedef {Object} GeolocationMapFeatureSettings
 *
 * @prop {String} import_path
 * @prop {Object} settings
 * @prop {String[]} scripts
 * @prop {String[]} async_scripts
 * @prop {String[]} stylesheets
 */

/**
 * Base class.
 *
 * @prop {GeolocationMapFeatureSettings} settings
 * @prop {GeolocationMapBase} map
 */
export class GeolocationMapFeature {
  /**
   * @constructor
   *
   * Called when map is initialized, but no map content is loaded yet.
   *
   * @param {GeolocationMapFeatureSettings} settings
   *   Settings.
   * @param {GeolocationMapBase} map
   *   Map.
   */
  constructor(settings, map) {
    this.settings = settings;
    this.map = map;
  }

  /**
   * Click somewhere on map.
   *
   * @param {GeolocationCoordinates} coordinates
   *   Coordinates.
   */
  onClick(coordinates) {}

  /**
   * Click somewhere on map.
   *
   * @param {GeolocationCoordinates} coordinates
   *   Coordinates.
   */
  onDoubleClick(coordinates) {}

  /**
   * Click somewhere on map.
   *
   * @param {GeolocationCoordinates} coordinates
   *   Coordinates.
   */
  onContextClick(coordinates) {}

  /**
   * Called when map content is fully loaded.
   */
  onMapReady() {}

  onMapIdle() {}

  /**
   * @param {GeolocationBoundaries} bounds
   *  Boundaries.
   */
  onBoundsChanged(bounds) {}

  /**
   * @param {GeolocationMapMarker} marker
   *  Marker.
   */
  onMarkerAdded(marker) {}

  /**
   * @param {GeolocationMapMarker} marker
   *  Marker.
   */
  onMarkerUpdated(marker) {}

  /**
   * @param {GeolocationMapMarker} marker
   *  Marker.
   */
  onMarkerRemove(marker) {}

  /**
   * @param {GeolocationMapMarker} marker
   *  Marker.
   */
  onMarkerClicked(marker) {}

  /**
   * @param {GeolocationShape} shape
   *  Marker.
   */
  onShapeAdded(shape) {}

  /**
   * @param {GeolocationShape} shape
   *  Shape.
   */
  onShapeUpdated(shape) {}

  /**
   * @param {GeolocationShape} shape
   *  Shape.
   */
  onShapeRemove(shape) {}

  /**
   * @param {GeolocationShape} shape
   *  Shape.
   * @param {GeolocationCoordinates} coordinates
   *   Coordinates.
   */
  onShapeClicked(shape, coordinates) {}
}
