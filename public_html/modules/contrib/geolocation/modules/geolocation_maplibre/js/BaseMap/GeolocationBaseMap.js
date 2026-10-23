/**
 * @typedef {Object} GeolocationBaseMapSettings
 *
 * @prop {String} import_path
 * @prop {Object} settings
 */

/**
 * Base class for what a base map needs of the renderer.
 *
 * Most base maps need nothing and ship no module at all: a style URL is the
 * whole of what they contribute. This is for the ones whose service wants
 * something more - URLs in a scheme only it understands, labels that can be
 * switched to another language - so that the map provider does not carry
 * knowledge of any particular service.
 *
 * @prop {Object} settings
 * @prop {MapLibre} map
 */
export class GeolocationBaseMap {
  /**
   * @param {Object} settings
   *   Settings the plugin attached for itself.
   * @param {MapLibre} map
   *   Map drawing this base map.
   */
  constructor(settings, map) {
    this.settings = settings ?? {};
    this.map = map;
  }

  /**
   * Take hold of the map, just after it was constructed.
   *
   * Runs before the style document has been fetched, so anything that has to
   * be in place for the sources, sprites and fonts it names can be set up
   * here. Whatever has to wait for the style binds its own event.
   */
  attach() {}
}
