export class GeolocationMapCenterBase {
  /**
   * @constructor
   *
   * @param {GeolocationMapBase} map
   *   Map.
   * @param {Object} settings
   *   Settings.
   */
  constructor(map, settings = {}) {
    this.map = map;
    this.settings = settings;
  }

  /**
   * @return {boolean}
   *   Success.
   */
  setCenter() {
    return true;
  }
}
