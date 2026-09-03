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
 * @prop {String} [fillColor]
 * @prop {float} [fillOpacity]
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

  getContent() {
    if (!this.content) {
      this.content = this.wrapper?.querySelector(".location-content")?.innerHTML ?? "";
    }

    return this.content;
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
    switch (this.type) {
      case "line":
      case "polygon":
        this.geometry.coordinates.forEach((value) => {
          bounds.north = bounds.north === null || value[1] > bounds.north ? value[1] : bounds.north;
          bounds.south = bounds.south === null || value[1] < bounds.south ? value[1] : bounds.south;
          bounds.east = bounds.east === null || value[0] > bounds.east ? value[0] : bounds.east;
          bounds.west = bounds.west === null || value[0] < bounds.west ? value[0] : bounds.west;
        });
        break;
      case "multiline":
        this.geometry.coordinates.forEach((line) => {
          line.coordinates.forEach((value) => {
            bounds.north = bounds.north === null || value[1] > bounds.north ? value[1] : bounds.north;
            bounds.south = bounds.south === null || value[1] < bounds.south ? value[1] : bounds.south;
            bounds.east = bounds.east === null || value[0] > bounds.east ? value[0] : bounds.east;
            bounds.west = bounds.west === null || value[0] < bounds.west ? value[0] : bounds.west;
          });
        });
        break;
      case "multipolygon":
        this.geometry.coordinates.forEach((polygon) => {
          polygon.coordinates.forEach((value) => {
            bounds.north = bounds.north === null || value[1] > bounds.north ? value[1] : bounds.north;
            bounds.south = bounds.south === null || value[1] < bounds.south ? value[1] : bounds.south;
            bounds.east = bounds.east === null || value[0] > bounds.east ? value[0] : bounds.east;
            bounds.west = bounds.west === null || value[0] < bounds.west ? value[0] : bounds.west;
          });
        });
        break;
    }

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
   * @param {GeolocationCoordinates} coordinates
   */
  click(coordinates) {
    this.map.dataLayers.forEach((layer) => {
      layer.shapeClicked(this, coordinates);
    });
  }
}
