import { MapLibreMapFeature } from "./MapLibreMapFeature.js";

/**
 * @typedef {Object} MapLibreControlScaleSettings
 *
 * @augments {GeolocationMapFeatureSettings}
 *
 * @prop {("top-left" | "top-right" | "bottom-left" | "bottom-right")} position
 * @prop {("metric" | "imperial" | "nautical")} unit
 * @prop {Number} max_width
 */

/**
 * @prop {MapLibreControlScaleSettings} settings
 */
export default class MapLibreControlScale extends MapLibreMapFeature {
  constructor(settings, map) {
    super(settings, map);

    map.maplibreMap.addControl(
      new map.maplibregl.ScaleControl({
        unit: this.settings.unit ?? "metric",
        maxWidth: Math.round(this.settings.max_width) || 100,
      }),
      this.getPosition()
    );
  }
}
