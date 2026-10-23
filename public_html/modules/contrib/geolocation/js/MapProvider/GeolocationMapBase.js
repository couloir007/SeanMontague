/**
 * @typedef {Object} GeolocationTileLayerSettings
 *
 * @prop {String} label
 * @prop {String} url
 * @prop {String} attribution
 * @prop {Object} settings
 * @prop {Array} [bounds]
 * @prop {Number} [min_zoom]
 * @prop {Number} [max_zoom]
 * @prop {Number} [opacity]
 * @prop {Boolean} [overlay]
 */

/**
 * @typedef {Object} GeolocationMapSettings
 *
 * @prop {String} [type] Map type
 * @prop {String} id
 * @prop {Object} settings
 * @prop {Number} lat
 * @prop {Number} lng
 * @prop {Object.<string, object>} mapCenter
 * @prop {Object} wrapper
 * @prop {String} import_path
 * @prop {String[]} scripts
 * @prop {String[]} async_scripts
 * @prop {String[]} stylesheets
 * @prop {String} conditional_initialization
 * @prop {String} conditional_description
 * @prop {String} conditional_label
 * @prop {Number} conditional_viewport_threshold
 * @prop {Object.<string, GeolocationDataLayerSettings>} data_layers
 * @prop {Object.<string, GeolocationTileLayerSettings>} tile_layers
 * @prop {Object.<string, GeolocationMapFeatureSettings>} features
 */

import { GeolocationCoordinates } from "../Base/GeolocationCoordinates.js";
import { GeolocationBoundaries } from "../Base/GeolocationBoundaries.js";
import { GeolocationMapMarker } from "../Base/GeolocationMapMarker.js";
import { GeolocationShapePolygon } from "../Base/GeolocationShapePolygon.js";
import { GeolocationShapeLine } from "../Base/GeolocationShapeLine.js";
import { GeolocationShapeMultiLine } from "../Base/GeolocationShapeMultiLine.js";
import { GeolocationShapeMultiPolygon } from "../Base/GeolocationShapeMultiPolygon.js";
import { GeolocationCircle } from "../Base/GeolocationCircle.js";
import { GeolocationShape } from "../Base/GeolocationShape.js";

/**
 * @prop {String} id
 * @prop {GeolocationMapSettings} settings
 * @prop {HTMLElement} wrapper
 * @prop {HTMLElement} container
 * @prop {Map<String, GeolocationDataLayer>} dataLayers
 * @prop {Map<String, Object>} tileLayers
 * @prop {Map<String, GeolocationMapFeature>} features
 * @prop {GeolocationMapCenterBase[]} mapCenter
 * @prop {Boolean} keepViewport
 *   Skip centering on the next rebuild, as the content was loaded for the
 *   viewport the map already has.
 */
export class GeolocationMapBase {
  constructor(mapSettings) {
    this.keepViewport = false;
    this.settings = mapSettings || {};
    this.wrapper = mapSettings.wrapper;
    this.container = mapSettings.wrapper.querySelector(".geolocation-map-container");

    if (!this.container) {
      throw new Error("Geolocation - Map container not found");
    }

    this.features = new Map();
    this.mapCenter = [];
    this.dataLayers = new Map();
    this.tileLayers = new Map();

    this.id = mapSettings.id ?? `map${Math.floor(Math.random() * 10000)}`;

    if (typeof ResizeObserver !== "undefined") {
      this.resizeObserver = new ResizeObserver(() => {
        this.onResize();
      });
      this.resizeObserver.observe(this.container);
    }
  }

  onResize() {}

  /**
   * Release everything the map set up, once it left the page for good.
   *
   * Map providers extend this to tear down their library's map.
   */
  destroy() {
    // Features go first, so emptying the layers below reports the removed
    // markers and shapes to none of them, above all not to a widget.
    this.features.forEach((feature) => {
      try {
        feature.destroy();
      } catch (e) {
        console.error(e, `Feature ${feature.constructor.name} failed destroy: ${e.toString()}`);
      }
    });
    this.features.clear();

    this.dataLayers.forEach((layer, layerId) => {
      try {
        layer.destroy();
      } catch (e) {
        console.error(e, `Layer ${layerId} failed destroy: ${e.toString()}`);
      }
    });
    this.dataLayers.clear();

    this.mapCenter.forEach((center) => {
      center.destroy();
    });
    this.mapCenter = [];

    this.resizeObserver?.disconnect();
  }

  readyFeatures() {
    this.features.forEach((feature) => {
      feature.onMapReady();
    });
  }

  /**
   * @return {Promise<GeolocationMapBase>}
   *   Initialized map.
   */
  initialize() {
    const scripts = this.settings.scripts || [];
    const scriptLoads = [];
    scripts.forEach((script) => {
      scriptLoads.push(Drupal.geolocation.addScript(script));
    });

    const asyncScripts = this.settings.async_scripts || [];
    const asyncScriptLoads = [];
    asyncScripts.forEach((script) => {
      asyncScriptLoads.push(Drupal.geolocation.addScript(script, true));
    });

    const stylesheets = this.settings.stylesheets || [];
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
        // Some features depend on libraries loaded AFTER main script but BEFORE instantiating.
        const featureScriptLoads = [];
        Object.keys(this.settings.features ?? {}).forEach((featureName) => {
          const featureScripts = this.settings.features[featureName]?.scripts || [];
          featureScripts.forEach((featureScript) => {
            featureScriptLoads.push(Drupal.geolocation.addScript(featureScript));
          });
        });
        return Promise.all(featureScriptLoads);
      })
      .then(() => {
        return this;
      });
  }

  /**
   * @param {GeolocationMapFeatureSettings} featureSettings
   *   Feature settings.
   * @param {?string} id
   *   Feature ID.
   * @return {Promise<GeolocationMapFeature>|null}
   *   Loaded feature.
   */
  loadFeature(featureSettings, id = null) {
    if (!featureSettings.import_path) {
      return null;
    }

    const asyncScripts = featureSettings.async_scripts || [];
    const asyncScriptLoads = [];
    asyncScripts.forEach((script) => {
      asyncScriptLoads.push(Drupal.geolocation.addScript(script, true));
    });

    const stylesheets = featureSettings.stylesheets || [];
    const stylesheetLoads = [];
    stylesheets.forEach((stylesheet) => {
      stylesheetLoads.push(Drupal.geolocation.addStylesheet(stylesheet));
    });

    return Promise.all(asyncScriptLoads)
      .then(() => {
        return Promise.all(stylesheetLoads);
      })
      .then(() => {
        return import(featureSettings.import_path);
      })
      .then((featureImport) => {
        try {
          const feature = new featureImport.default(featureSettings.settings, this);
          this.features.set(id, feature);

          return feature;
        } catch (e) {
          console.error(e, `Loading ${featureSettings.import_path} failed: ${e.toString()}`);
        }
      })
      .catch((error) => {
        console.error(error, `Loading ' ${featureSettings.import_path}' failed`);
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

  async loadCenterOptions() {
    const mapCenterImports = [];

    Object.keys(this.settings.mapCenter ?? {}).forEach((mapCenterName) => {
      const mapCenterSettings = this.settings.mapCenter[mapCenterName];
      if (mapCenterSettings.import_path) {
        const promise = import(mapCenterSettings.import_path);
        promise
          .then((mapCenter) => {
            const plugin = new mapCenter.default(this, mapCenterSettings.settings);
            plugin.weight = this.settings.mapCenter[mapCenterName].weight;
            this.mapCenter.push(plugin);
          })
          .catch((error) => {
            console.error(error, `Loading '${mapCenterSettings.import_path}' failed`);
          });
        mapCenterImports.push(promise);
      }
    });

    return Promise.all(mapCenterImports).then(() => {
      this.mapCenter.sort(
        /**
         * @param {GeolocationMapCenterBase} a
         *   Center one.
         * @param {Number} a.weight
         *   Weight one.
         * @param {GeolocationMapCenterBase} b
         *   Center two.
         * @param {Number} b.weight
         *   Weight two.
         *
         * @return {int}
         *   Compare value
         */
        (a, b) => {
          if (a.weight > b.weight) {
            return 1;
          }
          if (a.weight < b.weight) {
            return -1;
          }
          return 0;
        }
      );

      return this;
    });
  }

  addControl(element) {
    // Stub.
  }

  removeControls() {
    // Stub.
  }

  async getZoom() {
    // Stub.
  }

  setZoom(zoom, defer) {
    // Stub.
  }

  /**
   * @return {GeolocationBoundaries}
   *   Boundaries.
   */
  getBoundaries() {
    return null;
  }

  /**
   * @param {GeolocationBoundaries} boundaries
   *   Boundaries.
   *
   * @return {boolean}
   *   Change.
   */
  setBoundaries(boundaries) {
    if (!boundaries) {
      return false;
    }

    if (this.getBoundaries()?.equals(boundaries)) {
      return false;
    }

    return true;
  }

  /**
   * @param {Array.<GeolocationMapMarker|GeolocationShape>} elements
   *   Elements to measure. Defaults to the markers and shapes of the default
   *   data layer.
   *
   * @return {GeolocationBoundaries}
   *   Boundaries.
   */
  getElementBoundaries(elements) {
    elements = elements || (this.dataLayers.get("default").markers || []).concat(this.dataLayers.get("default").shapes || []);
    if (!elements.length) {
      return null;
    }

    const markers = [];
    const shapes = [];

    elements.forEach((element) => {
      if (element instanceof GeolocationMapMarker) {
        markers.push(element);
      } else if (element instanceof GeolocationShape) {
        shapes.push(element);
      }
    });

    let bounds = this.getMarkerBoundaries(markers);

    if (bounds === null) {
      bounds = this.getShapeBoundaries(shapes);
    } else {
      bounds.extend(this.getShapeBoundaries(shapes));
    }

    return bounds;
  }

  /**
   * @param {GeolocationMapMarker[]} markers
   *   Markers.
   *
   * @return {GeolocationBoundaries}
   *   Boundaries.
   */
  getMarkerBoundaries(markers) {
    return null;
  }

  /**
   * @param {GeolocationShape[]} shapes
   *   Shapes.
   *
   * @return {GeolocationBoundaries}
   *   Boundaries.
   */
  getShapeBoundaries(shapes) {
    shapes = shapes || this.dataLayers.get("default").shapes;
    if (!shapes.length) {
      return null;
    }

    let bounds;

    shapes.forEach((shape) => {
      const currentBounds = shape.getBounds();
      if (currentBounds === null) {
        return;
      }

      if (bounds) {
        bounds.extend(currentBounds);
      } else {
        bounds = currentBounds;
      }
    });

    if (bounds) {
      return bounds;
    }

    return null;
  }

  /**
   * @return {GeolocationCoordinates}
   *   Coordinates.
   */
  getCenter() {
    return null;
  }

  setCenterByOptions() {
    this.setZoom();

    Object.values(this.mapCenter).every((center) => {
      return center.setCenter() !== true;
    });

    return this;
  }

  /**
   * @param {GeolocationCoordinates} coordinates
   *   Coordinates.
   * @param {Number} accuracy
   *   Accuracy.
   */
  setCenterByCoordinates(coordinates, accuracy = undefined) {
    if (typeof accuracy === "undefined") {
      return;
    }

    const earth = 6378.137;

    const m = 1 / (((2 * Math.PI) / 360) * earth) / 1000;

    this.setBoundaries(
      new GeolocationBoundaries(
        coordinates.lat + accuracy * m,
        coordinates.lng + (accuracy * m) / Math.cos(coordinates.lat * (Math.PI / 180)),
        coordinates.lat + -1 * accuracy * m,
        coordinates.lng + (-1 * accuracy * m) / Math.cos(coordinates.lat * (Math.PI / 180))
      )
    );

    const circle = this.createCircle(coordinates, accuracy, {
      fillColor: "#4285F4",
      fillOpacity: 0.15,
      strokeColor: "#4285F4",
      strokeOpacity: 0.3,
      strokeWidth: 1,
    });

    // Fade circle away.
    const intervalId = setInterval(() => {
      let { fillOpacity } = circle;
      fillOpacity -= 0.03;

      let { strokeOpacity } = circle;
      strokeOpacity -= 0.06;

      if (strokeOpacity > 0 && fillOpacity > 0) {
        circle.update(null, 0, {
          fillOpacity,
          strokeOpacity,
        });
      } else {
        circle.remove();
        clearInterval(intervalId);
      }
    }, 500);

    return false;
  }

  /**
   * @param {GeolocationCoordinates} coordinates
   *   Coordinates.
   * @param {GeolocationMarkerSettings} settings
   *   Settings.
   *
   * @return {GeolocationMapMarker}
   *   Marker.
   */
  createMarker(coordinates, settings) {
    return new GeolocationMapMarker(coordinates, settings, this);
  }

  getMarkerById(id, layerId = null) {
    if (layerId) {
      this.dataLayers.get(layerId).markers.forEach((marker) => {
        if (id === marker.id ?? null) {
          return marker;
        }
      });

      return null;
    }

    // Check default first, then the rest.
    this.dataLayers.get("default").markers.forEach((marker) => {
      if (id === marker.id ?? null) {
        return marker;
      }
    });

    this.dataLayers.forEach((layer) => {
      if (id === "default") {
        return;
      }

      layer.markers.forEach((marker) => {
        if (id === marker.id ?? null) {
          return marker;
        }
      });
    });

    return null;
  }

  removeMapMarkers() {
    this.dataLayers.forEach((layer) => {
      layer.removeMarkers();
    });
  }

  /**
   * @param {GeolocationCoordinates} center
   *   Center.
   * @param {int} radius
   *   Radius.
   * @param {GeolocationCircleSettings} [settings]
   *   Settings.
   *
   * @return {GeolocationCircle}
   *   Shape.
   */
  createCircle(center, radius, settings = {}) {
    return new GeolocationCircle(center, radius, this, settings);
  }

  /**
   * @param {GeolocationGeometry} geometry
   *   Geometry.
   * @param {GeolocationShapeSettings} settings
   *   Settings.
   *
   * @return {GeolocationShapeLine}
   *   Shape.
   */
  createShapeLine(geometry, settings) {
    return new GeolocationShapeLine(geometry, settings, this);
  }

  /**
   * @param {GeolocationGeometry} geometry
   *   Geometry.
   * @param {GeolocationShapeSettings} settings
   *   Settings.
   *
   * @return {GeolocationShapePolygon}
   *   Shape.
   */
  createShapePolygon(geometry, settings) {
    return new GeolocationShapePolygon(geometry, settings, this);
  }

  /**
   * @param {GeolocationGeometry} geometry
   *   Geometry.
   * @param {GeolocationShapeSettings} settings
   *   Settings.
   *
   * @return {GeolocationShapeMultiLine}
   *   Shape.
   */
  createShapeMultiLine(geometry, settings) {
    return new GeolocationShapeMultiLine(geometry, settings, this);
  }

  /**
   * @param {GeolocationGeometry} geometry
   *   Geometry.
   * @param {GeolocationShapeSettings} settings
   *   Settings.
   *
   * @return {GeolocationShapeMultiPolygon}
   *   Shape.
   */
  createShapeMultiPolygon(geometry, settings) {
    return new GeolocationShapeMultiPolygon(geometry, settings, this);
  }

  removeMapShapes() {
    this.dataLayers.forEach((layer) => {
      layer.removeShapes();
    });
  }

  /**
   * @param {String} layerId
   *   Layer ID.
   * @param {GeolocationDataLayerSettings} layerSettings
   *   Layer settings.
   *
   * @return {Promise<GeolocationDataLayer>|null}
   *   Layer.
   */
  loadDataLayer(layerId, layerSettings) {
    if (!layerSettings.import_path) {
      return null;
    }

    const scripts = layerSettings.scripts || [];
    const scriptLoads = [];
    scripts.forEach((script) => {
      scriptLoads.push(Drupal.geolocation.addScript(script));
    });

    const asyncScripts = layerSettings.async_scripts || [];
    const asyncScriptLoads = [];
    asyncScripts.forEach((script) => {
      asyncScriptLoads.push(Drupal.geolocation.addScript(script, true));
    });

    const stylesheets = layerSettings.stylesheets || [];
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
        return import(layerSettings.import_path);
      })
      .then((layerImport) => {
        try {
          const layerKey = layerId === "geolocation_default_layer_default" ? "default" : layerId;

          // The map outlives its layers, so a replaced layer has to release
          // whatever it attached.
          const previousLayer = this.dataLayers.get(layerKey);
          if (previousLayer) {
            previousLayer.destroy();
          }

          /** @type {GeolocationDataLayer} */
          const layer = new layerImport.default(this, layerId, layerSettings);
          this.dataLayers.set(layerKey, layer);

          return layer.loadFeatures();
        } catch (e) {
          console.error(`Layer ${layerId} failed: ${e.toString()}`);
        }
      })
      .then((layer) => {
        return layer.loadMarkers();
      })
      .then((layer) => {
        return layer.loadShapes();
      })
      .catch((error) => {
        console.error(error.toString(), error, `Loading '${layerSettings.import_path}' failed`);
      });
  }

  /**
   * Load data layers.
   *
   * @return {Promise<GeolocationMapBase>}
   *   Resolves with this map once every data layer is loaded.
   */
  async loadDataLayers() {
    const dataLayerImports = [];

    Object.keys(this.settings.data_layers ?? {}).forEach((dataLayerName) => {
      const dataLayerPromise = this.loadDataLayer(dataLayerName, this.settings.data_layers[dataLayerName] ?? {});

      if (dataLayerPromise) {
        dataLayerImports.push(dataLayerPromise);
      }
    });

    return Promise.all(dataLayerImports).then(() => {
      return this;
    });
  }

  /**
   * @param {string} layerId
   *   Machine name of the tile layer.
   * @param {GeolocationTileLayerSettings} layerSettings
   *   Settings for the tile layer.
   */
  loadTileLayer(layerId, layerSettings) {
    // Example: this.tileLayers.set(layerId, layer);
  }

  async loadTileLayers() {
    Object.keys(this.settings.tile_layers ?? {}).forEach((layerId) => {
      // An Ajax refresh re-runs the init chain on the very same map, so
      // loading again would stack a second, untracked copy of every layer.
      if (this.tileLayers.has(layerId)) {
        return;
      }

      this.loadTileLayer(layerId, this.settings.tile_layers[layerId] ?? {});
    });

    return Promise.resolve(this);
  }

  /**
   * @param {string} layerId
   *   Machine name of the tile layer to remove.
   */
  unloadTileLayer(layerId) {
    // this.tileLayers.delete(layerId);
  }

  fitMapToElements(elements) {
    const boundaries = this.getElementBoundaries(elements);
    if (!boundaries) {
      return false;
    }

    this.setBoundaries(boundaries);
  }
}
