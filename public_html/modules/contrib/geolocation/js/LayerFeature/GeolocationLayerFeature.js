/**
 * @typedef {Object} GeolocationLayerFeatureSettings
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
 * @prop {GeolocationLayerFeatureSettings} settings
 * @prop {GeolocationMapBase} map
 */
export class GeolocationLayerFeature {
  /**
   * @constructor
   *
   * @param {GeolocationLayerFeatureSettings} settings
   *   Settings.
   * @param {GeolocationDataLayer} layer
   *   Layer.
   */
  constructor(settings, layer) {
    this.settings = settings;
    this.layer = layer;
  }

  /**
   * @param {GeolocationMapMarker} marker
   *   Marker.
   */
  onMarkerAdded(marker) {}

  /**
   * @param {GeolocationMapMarker} marker
   *   Marker.
   */
  onMarkerUpdated(marker) {}

  /**
   * @param {GeolocationMapMarker} marker
   *   Marker.
   */
  onMarkerRemove(marker) {}

  /**
   * @param {GeolocationMapMarker} marker
   *   Marker.
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
   *  Coordinates.
   */
  onShapeClicked(shape, coordinates) {}
}
