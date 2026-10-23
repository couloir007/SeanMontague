/**
 * @typedef {Object} GeolocationShapeSettings
 *
 * @prop {String} [id]
 * @prop {String} [title]
 * @prop {Element} [wrapper]
 * @prop {String} [content]
 * @prop {String} [strokeColor]
 * @prop {Number} [strokeOpacity]
 * @prop {Number} [strokeWidth]
 * @prop {String} [fillColor]
 * @prop {Number} [fillOpacity]
 */

import { GeolocationCoordinates } from "./GeolocationCoordinates.js";
import { GeolocationBoundaries } from "./GeolocationBoundaries.js";

/**
 * @prop {String} [id]
 * @prop {String} title
 * @prop {Element} [wrapper]
 * @prop {GeolocationMapBase} map
 * @prop {String} content
 * @prop {GeolocationShapeSettings} settings
 * @prop {String} strokeColor
 * @prop {float} strokeOpacity
 * @prop {int} strokeWidth
 * @prop {String} fillColor
 * @prop {float} fillOpacity
 */
export class GeolocationShape {
  /**
   * @param {GeolocationGeometry} geometry
   *   Geometry.
   * @param {GeolocationShapeSettings} settings
   *   Settings.
   * @param {GeolocationMapBase} map
   *   Map.
   */
  constructor(geometry, settings = {}, map) {
    this.geometry = geometry;
    this.settings = settings;
    this.type = "";
    this.id = settings.id?.toString() ?? null;
    this.title = settings.title ?? undefined;
    this.wrapper = settings.wrapper ?? document.createElement("div");
    this.map = map;
    this.content = settings.content ?? this.getContent();

    // Shapes are not only created from rendered markup carrying every style -
    // a widget adding one from a geocoded result passes none of them - and an
    // undefined reaching a map provider draws nothing at all.
    this.strokeColor = settings.strokeColor ?? "#0000FF";
    this.strokeOpacity = settings.strokeOpacity ?? 1;
    this.strokeWidth = settings.strokeWidth ?? 2;
    this.fillColor = settings.fillColor ?? "#0000FF";
    this.fillOpacity = settings.fillOpacity ?? 0.2;
  }

  getContent() {
    if (!this.content) {
      this.content = this.wrapper?.querySelector(".location-content")?.innerHTML ?? "";
    }

    return this.content;
  }

  getContentElement() {
    if (typeof this.contentElement === "undefined") {
      this.contentElement = this.wrapper?.querySelector(".location-content") ?? null;
    }

    return this.contentElement;
  }

  /**
   * @param {GeolocationGeometry} [geometry]
   *   Geometry.
   * @param {GeolocationShapeSettings} [settings]
   *   Settings.
   */
  update(geometry, settings) {
    if (geometry) {
      this.geometry = geometry;
    }

    if (settings) {
      this.settings = {
        ...this.settings,
        ...settings,
      };

      if (settings.id) {
        this.id = settings.id.toString();
      }
      if (settings.title) {
        this.title = settings.title.toString();
      }
      if (settings.wrapper) {
        this.wrapper = settings.wrapper;
      }
      if (settings.content) {
        this.content = settings.content;
      }

      if (typeof settings.strokeColor !== "undefined") {
        this.strokeColor = settings.strokeColor;
      }
      if (typeof settings.strokeOpacity !== "undefined") {
        this.strokeOpacity = settings.strokeOpacity;
      }
      if (typeof settings.strokeWidth !== "undefined") {
        this.strokeWidth = settings.strokeWidth;
      }
      if (typeof settings.fillColor !== "undefined") {
        this.fillColor = settings.fillColor;
      }
      if (typeof settings.fillOpacity !== "undefined") {
        this.fillOpacity = settings.fillOpacity;
      }
    }
  }

  getBounds() {
    const bounds = {
      north: null,
      south: null,
      east: null,
      west: null,
    };

    /**
     * Extend the boundaries by a position, or by every position nested in it.
     *
     * Geometry types differ only in how deeply their coordinates nest
     * positions - a LineString holds them directly, a MultiPolygon three levels
     * down - so this descends until it reaches one instead of assuming a depth.
     *
     * @param {Array} coordinates
     *   A position, or an array of them at any nesting depth.
     */
    const extendBounds = (coordinates) => {
      if (!Array.isArray(coordinates)) {
        return;
      }

      if (typeof coordinates[0] === "number") {
        bounds.north = bounds.north === null || coordinates[1] > bounds.north ? coordinates[1] : bounds.north;
        bounds.south = bounds.south === null || coordinates[1] < bounds.south ? coordinates[1] : bounds.south;
        bounds.east = bounds.east === null || coordinates[0] > bounds.east ? coordinates[0] : bounds.east;
        bounds.west = bounds.west === null || coordinates[0] < bounds.west ? coordinates[0] : bounds.west;
        return;
      }

      coordinates.forEach(extendBounds);
    };

    extendBounds(this.geometry?.coordinates);

    if (bounds.east === null || bounds.west === null || bounds.north === null || bounds.south === null) {
      return null;
    }

    return new GeolocationBoundaries(bounds);
  }

  remove() {
    this.map.dataLayers.forEach((layer) => {
      layer.shapeRemoved(this);
    });
  }

  /**
   * Click handler delegation.
   *
   * @param {GeolocationCoordinates} coordinates Coordinates that were clicked.
   */
  click(coordinates) {
    this.map.dataLayers.forEach((layer) => {
      layer.shapeClicked(this, coordinates);
    });
  }
}
