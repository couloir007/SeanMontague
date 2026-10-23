import { MapLibreMapFeature } from "./MapLibreMapFeature.js";

/**
 * @typedef {Object} MapLibreControlFullscreenSettings
 *
 * @augments {GeolocationMapFeatureSettings}
 *
 * @prop {("top-left" | "top-right" | "bottom-left" | "bottom-right")} position
 */

/**
 * @prop {MapLibreControlFullscreenSettings} settings
 */
export default class MapLibreControlFullscreen extends MapLibreMapFeature {
  constructor(settings, map) {
    super(settings, map);

    // Fullscreen is requested on the wrapper, so the map's own controls and
    // any Drupal control rendered beside them come along.
    map.maplibreMap.addControl(new map.maplibregl.FullscreenControl({ container: map.wrapper }), this.getPosition());
  }
}
