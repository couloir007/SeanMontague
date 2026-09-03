import { GeolocationCoordinates } from "../Base/GeolocationCoordinates.js";
import { GeolocationShape } from "../Base/GeolocationShape.js";

/**
 * @typedef {Object} GeolocationDataLayerSettings
 *
 * @prop {String} import_path
 * @prop {Object} settings
 * @prop {Map<string, GeolocationLayerFeature>} features
 * @prop {String[]} scripts
 * @prop {String[]} async_scripts
 * @prop {String[]} stylesheets
 */

/**
 * @prop {GeolocationMapMarker[]} markers
 * @prop {GeolocationShape[]} shapes
 */
export default class GeolocationDataLayer {
  /**
   * @param {GeolocationMapBase} map
   *   Map.
   * @param {String} id
   *   ID.
   * @param {GeolocationDataLayerSettings} layerSettings
   *   Settings.
   */
  constructor(map, id, layerSettings) {
    this.map = map;
    this.settings = layerSettings.settings;
    this.features = new Map();
    this.markers = [];
    this.shapes = [];
    this.id = id;
  }

  /**
   * @param {GeolocationLayerFeatureSettings} layerFeatureSettings
   *   Layer feature settings.
   * @param {?string} id
   *   Layer feature ID.
   * @return {Promise<GeolocationLayerFeature>|null}
   *   Loading feature Promise.
   */
  loadFeature(layerFeatureSettings, id = null) {
    if (!layerFeatureSettings.import_path) {
      return null;
    }

    const scripts = layerFeatureSettings.scripts || [];
    const scriptLoads = [];
    scripts.forEach((script) => {
      scriptLoads.push(Drupal.geolocation.addScript(script));
    });

    const asyncScripts = layerFeatureSettings.async_scripts || [];
    const asyncScriptLoads = [];
    asyncScripts.forEach((script) => {
      asyncScriptLoads.push(Drupal.geolocation.addScript(script, true));
    });

    const stylesheets = layerFeatureSettings.stylesheets || [];
    const stylesheetLoads = [];
    stylesheets.forEach((stylesheet) => {
      stylesheetLoads.push(Drupal.geolocation.addStylesheet(stylesheet));
    });

    return Promise.all(scriptLoads)
      .then(() => {
        return Promise.all(asyncScriptLoads);
      })
      .then(() => {
        return Promise.all(stylesheetLoads);
      })
      .then(() => {
        return import(layerFeatureSettings.import_path);
      })
      .then((featureImport) => {
        try {
          const feature = new featureImport.default(layerFeatureSettings.settings, this);
          this.features.set(id, feature);

          return feature;
        } catch (e) {
          console.error(e, "Loading feature failed");
          return null;
        }
      })
      .catch((error) => {
        console.error(error, `Loading '${layerFeatureSettings.import_path}' failed.`);
      });
  }

  async loadFeatures() {
    const featureImports = [];

    Object.keys(this.settings.features ?? {}).forEach((featureName) => {
      const featurePromise = this.loadFeature(this.settings.features[featureName], featureName);

      if (featurePromise) {
        featureImports.push(featurePromise);
      }
    });

    return Promise.all(featureImports).then(() => {
      return this;
    });
  }

  /**
   * @param {String} selector
   *   CSS selector.
   */
  async loadMarkers(selector = "") {
    if (!selector) {
      selector = `#${this.id}.geolocation-map-layer .geolocation-location`;
    }

    this.map.wrapper.querySelectorAll(selector).forEach((location) => {
      const marker = this.map.createMarker(new GeolocationCoordinates(location.getAttribute("data-lat"), location.getAttribute("data-lng")), {
        id: location.getAttribute("id"),
        title: location.querySelector(".location-title")?.textContent.trim(),
        label: location.getAttribute("data-label") ?? undefined,
        icon: location.getAttribute("data-icon") ?? undefined,
        draggable: location.getAttribute("data-draggable") ?? undefined,
        wrapper: location,
      });

      this.markerAdded(marker);
    });

    return this;
  }

  markerAdded(marker) {
    if (!marker.id ?? false) {
      marker.id = this.markers.length.toString();
    }

    this.markers.push(marker);

    this.features.forEach((feature) => {
      try {
        feature.onMarkerAdded(marker);
      } catch (e) {
        console.error(e, `Feature  ${feature.constructor.name} failed onMarkerAdded: ${e.toString()}`);
      }
    });
  }

  markerUpdated(marker) {
    if (!this.getMarkerById(marker.id)) {
      return;
    }

    this.features.forEach((feature) => {
      try {
        feature.onMarkerUpdated(marker);
      } catch (e) {
        console.error(e, `Feature  ${feature.constructor.name} failed onMarkerUpdated: ${e.toString()}`);
      }
    });
  }

  markerRemoved(marker) {
    if (!this.getMarkerById(marker.id)) {
      return;
    }

    this.features.forEach((feature) => {
      try {
        feature.onMarkerRemove(marker);
      } catch (e) {
        console.error(e, `Feature  ${feature.constructor.name} failed onMarkerRemove: ${e.toString()}`);
      }
    });

    this.markers.forEach((element, index) => {
      if (element.id === marker.id) {
        this.markers.splice(Number(index), 1);
      }
    });
  }

  removeMarkers() {
    while (this.markers.length) {
      const removedMarker = this.markers.pop();
      this.markerRemoved(removedMarker);
      removedMarker.remove();
    }
  }

  markerClicked(marker) {
    if (!this.getMarkerById(marker.id)) {
      return;
    }

    this.features.forEach((feature) => {
      try {
        feature.onMarkerClicked(marker);
      } catch (e) {
        console.error(e, `Feature ${feature.constructor.name} failed onMarkerClicked: ${e.toString()}`);
      }
    });
  }

  getMarkerById(id) {
    for (let i = 0; i < this.markers.length; i++) {
      const marker = this.markers[i];
      if (!marker.id) continue;
      if (id === marker.id) {
        return marker;
      }
    }
    return null;
  }

  /**
   * @param {String} selector
   *   CSS selector.
   */
  async loadShapes(selector = "") {
    if (!selector) {
      selector = `#${this.id}.geolocation-map-layer .geolocation-geometry`;
    }

    this.map.wrapper.querySelectorAll(selector).forEach((shapeElement) => {
      const settings = {
        wrapper: shapeElement,
        title: shapeElement.querySelector("h2.title")?.textContent ?? "",
        content: shapeElement.querySelector("div.content")?.innerHTML ?? "",
        strokeColor: shapeElement.getAttribute("data-stroke-color") ?? "#0000FF",
        strokeWidth: shapeElement.getAttribute("data-stroke-width") ?? 2,
        strokeOpacity: shapeElement.getAttribute("data-stroke-opacity") ?? 1,
        fillColor: shapeElement.getAttribute("data-fill-color") ?? "#0000FF",
        fillOpacity: shapeElement.getAttribute("data-fill-opacity") ?? 0.2,
      };

      const geometry = JSON.parse(shapeElement.querySelector(".geometry")?.textContent);

      let shape;
      switch (shapeElement.getAttribute("data-geometry-type")) {
        case "line":
          shape = this.map.createShapeLine(geometry, settings);
          break;

        case "polygon":
          shape = this.map.createShapePolygon(geometry, settings);
          break;

        case "multiline":
          shape = this.map.createShapeMultiLine(geometry, settings);
          break;

        case "multipolygon":
          shape = this.map.createShapeMultiPolygon(geometry, settings);
          break;

        default:
          console.error("Unknown shape type cannot be added.");
      }

      this.shapeAdded(shape);
    });

    return this;
  }

  /**
   * @param {GeolocationShape} shape
   *   Shape.
   *
   * @return {GeolocationShape}
   *   Added shape.
   */
  shapeAdded(shape) {
    if (!shape.id ?? false) {
      shape.id = this.shapes.length.toString();
    }

    this.shapes.push(shape);

    this.features.forEach((feature) => {
      try {
        feature.onShapeAdded(shape);
      } catch (e) {
        console.error(e, `Feature  ${feature.constructor.name} failed onShapeAdded: ${e.toString()}`);
      }
    });
  }

  /**
   * @param {GeolocationShape} shape
   *   Shape.
   */
  shapeUpdated(shape) {
    this.features.forEach((feature) => {
      try {
        feature.onShapeUpdated(shape);
      } catch (e) {
        console.error(e, `Feature  ${feature.constructor.name} failed onShapeUpdated: ${e.toString()}`);
      }
    });
  }

  /**
   * @param {GeolocationShape} shape
   *   Shape.
   */
  shapeRemoved(shape) {
    this.features.forEach((feature) => {
      try {
        feature.onShapeRemove(shape);
      } catch (e) {
        console.error(e, `Feature  ${feature.constructor.name} failed onShapeRemove: ${e.toString()}`);
      }
    });

    this.shapes.forEach((element, index) => {
      if (element.id === shape.id) {
        this.shapes.splice(Number(index), 1);
      }
    });
  }

  removeShapes() {
    while (this.shapes.length) {
      const removedShape = this.shapes.pop();
      this.shapeRemoved(removedShape);
      removedShape.remove();
    }
  }

  getShapeById(id) {
    for (let i = 0; i < this.shapes.length; i++) {
      const shape = this.shapes[i];
      if (!shape.id) continue;
      if (id === shape.id) {
        return shape;
      }
    }
    return null;
  }

  shapeClicked(shape, coordinates) {
    if (!this.getShapeById(shape.id)) {
      return;
    }

    this.features.forEach((feature) => {
      try {
        feature.onShapeClicked(shape, coordinates);
      } catch (e) {
        console.error(e, `Feature ${feature.constructor.name} failed onShapeClicked: ${e.toString()}`);
      }
    });
  }
}
