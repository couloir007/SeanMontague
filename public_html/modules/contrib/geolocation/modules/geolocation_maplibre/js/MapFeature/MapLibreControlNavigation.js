import { MapLibreMapFeature } from "./MapLibreMapFeature.js";

/**
 * @typedef {Object} MapLibreControlNavigationSettings
 *
 * @augments {GeolocationMapFeatureSettings}
 *
 * @prop {("top-left" | "top-right" | "bottom-left" | "bottom-right")} position
 * @prop {Boolean} show_zoom
 * @prop {Boolean} show_compass
 * @prop {Boolean} visualize_pitch
 */

/**
 * @prop {MapLibreControlNavigationSettings} settings
 */
export default class MapLibreControlNavigation extends MapLibreMapFeature {
  constructor(settings, map) {
    super(settings, map);

    map.maplibreMap.addControl(
      new map.maplibregl.NavigationControl({
        showZoom: this.settings.show_zoom ?? true,
        showCompass: this.settings.show_compass ?? true,
        visualizePitch: this.settings.visualize_pitch ?? false,
      }),
      this.getPosition()
    );
  }
}
