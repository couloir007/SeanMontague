/**
 * Terra Draw ships its own declarations, but as a module - unlike @types/leaflet
 * and @types/google.maps, it exports no ambient global for the UMD build this
 * feature loads, so its types have to be pulled in explicitly.
 *
 * @typedef {import("terra-draw").TerraDraw} TerraDraw
 */

/**
 * @typedef {Object} TerraDrawAdapterSettings
 *
 * @prop {String} map_provider
 * @prop {String} script
 * @prop {String} global
 * @prop {String} class
 * @prop {Object.<String, String>} globals
 */

/**
 * @typedef {Object} TerraDrawDrawingModeSettings
 *
 * @prop {String} mode
 * @prop {String} class
 * @prop {String} geojson_type
 */

/**
 * @typedef {Object} TerraDrawConnectorSettings
 *
 * @prop {String} plugin_id
 * @prop {int} cardinality
 * @prop {String} field_name
 * @prop {String} field_type
 * @prop {TerraDrawAdapterSettings|null} adapter
 * @prop {TerraDrawDrawingModeSettings|null} drawing_mode
 */

import GeolocationGeometryFieldWidgetMapConnector from "./GeolocationGeometryFieldWidgetMapConnector.js";
import { GeolocationGeometry } from "../../../../js/Base/GeolocationGeometry.js";

/**
 * Everything Terra Draw based connectors share: loading the library and the map
 * provider's adapter, starting Terra Draw with the mode the field type calls
 * for, and running the Draw / Edit / Delete control.
 *
 * What a drawn feature means to the widget is left to the implementing
 * connector - one geometry per field item, or several making up one.
 *
 * @prop {TerraDrawConnectorSettings} settings
 * @prop {String|null} mode Terra Draw mode this widget draws in.
 * @prop {TerraDraw|null} terraDraw
 * @prop {Map<Number, String>} featureIds Terra Draw feature IDs by widget index.
 * @prop {Promise<TerraDraw|null>} ready
 * @prop {String|null} selectedFeatureId
 */
export default class GeolocationGeometryTerraDrawConnectorBase extends GeolocationGeometryFieldWidgetMapConnector {
  constructor(settings, map) {
    super(settings, map);

    this.mode = this.settings.drawing_mode?.mode ?? null;
    this.terraDraw = null;
    this.featureIds = new Map();
    this.selectedFeatureId = null;
    this.deleteButton = null;
    this.modeButtons = [];
    this.control = this.map.wrapper.querySelector(`.geolocation-map-control.${this.settings.plugin_id}`);

    this.ready = this.initializeTerraDraw().catch((error) => {
      console.error(error, "Geolocation Geometry Terra Draw: Initialization failed.");
      return null;
    });
  }

  /**
   * Load Terra Draw, its adapter and set up the drawing modes.
   *
   * @return {Promise<TerraDraw|null>}
   *   Started Terra Draw instance, or NULL if this map cannot be drawn on.
   */
  async initializeTerraDraw() {
    // A map provider Terra Draw has no adapter for and a field type it cannot
    // draw are both pointed out server side, in the control this feature
    // renders in place of its buttons.
    if (!this.settings.adapter || !this.settings.drawing_mode) {
      return null;
    }

    Object.entries(this.settings.adapter.globals ?? {}).forEach(([alias, source]) => {
      if (typeof window[alias] === "undefined" && typeof window[source] !== "undefined") {
        window[alias] = window[source];
      }
    });

    await Drupal.geolocation.addScript(this.settings.adapter.script);

    const Adapter = window[this.settings.adapter.global]?.[this.settings.adapter.class];
    const DrawMode = window.terraDraw?.[this.settings.drawing_mode.class];

    if (!window.terraDraw || !Adapter || !DrawMode) {
      throw new Error(`Geolocation Geometry Terra Draw: Library, adapter ${this.settings.adapter.class} or mode ${this.settings.drawing_mode.class} unavailable.`);
    }

    const adapterOptions = this.getAdapterOptions();
    if (!adapterOptions) {
      throw new Error(`Geolocation Geometry Terra Draw: No map handle for map provider ${this.settings.adapter.map_provider}.`);
    }

    const { TerraDraw, TerraDrawSelectMode } = window.terraDraw;

    // A point has no vertices to edit, only a position to drag.
    const feature =
      this.mode === "point"
        ? { draggable: true }
        : {
            draggable: true,
            coordinates: {
              midpoints: true,
              draggable: true,
              deletable: true,
            },
          };

    this.terraDraw = new TerraDraw({
      adapter: new Adapter(adapterOptions),
      modes: [new DrawMode(), new TerraDrawSelectMode({ flags: { [this.mode]: { feature } } })],
    });

    const started = new Promise((resolve) => {
      this.terraDraw.on("ready", resolve);
    });

    this.terraDraw.start();
    await started;

    this.registerEventHandlers();
    this.initializeControl();
    this.setDrawingMode("select");

    return this.terraDraw;
  }

  /**
   * Get the map library and map instance the adapter has to be built on.
   *
   * @return {Object|null}
   *   Adapter options, or NULL for a map provider without a known handle.
   */
  getAdapterOptions() {
    switch (this.settings.adapter.map_provider) {
      case "google_maps":
        return { lib: google.maps, map: this.map.googleMap, coordinatePrecision: 9 };

      case "leaflet":
        return { lib: L, map: this.map.leafletMap };

      // The MapLibre adapter reaches the library through the map itself.
      case "maplibre":
        return { map: this.map.maplibreMap };

      default:
        return null;
    }
  }

  registerEventHandlers() {
    this.terraDraw.on("finish", (id, context) => {
      if (context.action !== "draw") {
        return;
      }

      this.onFeatureDrawn(id);
    });

    this.terraDraw.on("change", (ids, type, context) => {
      // Changes this connector itself caused through addFeatures() and
      // removeFeatures() must not be reported back to the widget.
      if (context?.origin === "api") {
        return;
      }

      switch (type) {
        case "update":
          ids.forEach((id) => this.onFeatureUpdated(id));
          break;

        case "delete":
          ids.forEach((id) => {
            // The delete button must not point at a feature that is gone.
            if (this.selectedFeatureId === id) {
              this.setSelectedFeature(null);
            }

            this.onFeatureDeleted(id);
          });
          break;

        default:
          break;
      }
    });

    this.terraDraw.on("select", (id) => this.setSelectedFeature(id));
    this.terraDraw.on("deselect", () => this.setSelectedFeature(null));
  }

  initializeControl() {
    this.modeButtons = Array.from(this.control?.querySelectorAll("button.terra-draw-mode") ?? []);

    this.modeButtons.forEach((button) => {
      button.addEventListener("click", (event) => {
        event.preventDefault();
        this.setDrawingMode(button.dataset.terraDrawMode === "draw" ? "draw" : "select");
      });
    });

    this.deleteButton = this.control?.querySelector("button.terra-draw-delete");
    this.deleteButton?.addEventListener("click", (event) => {
      event.preventDefault();
      this.deleteSelectedFeature();
    });

    this.setSelectedFeature(null);
  }

  /**
   * Remember what the delete button would remove, and enable it accordingly.
   *
   * @param {String|null} featureId
   *   Terra Draw feature ID, or NULL when nothing is selected.
   */
  setSelectedFeature(featureId) {
    // Terra Draw also stores selection and midpoints, which are not the widget's.
    this.selectedFeatureId = featureId !== null && this.terraDraw?.getSnapshotFeature(featureId)?.properties?.mode === this.mode ? featureId : null;

    if (this.deleteButton) {
      this.deleteButton.disabled = this.selectedFeatureId === null;
    }
  }

  /**
   * Remove the selected geometry and report it, as the Delete key would.
   *
   * Terra Draw only reacts to that key while the map itself has focus, and its
   * Google Maps container is not focusable at all, so the button is the only
   * dependable way to delete. removeFeatures() is an API change, which the
   * change handler ignores on purpose, so the removal is reported here.
   */
  deleteSelectedFeature() {
    if (!this.terraDraw || this.selectedFeatureId === null) {
      return;
    }

    const featureId = this.selectedFeatureId;

    this.terraDraw.removeFeatures([featureId]);
    this.setSelectedFeature(null);
    this.onFeatureDeleted(featureId);
  }

  /**
   * @param {String} mode
   *   Either "draw" to add a new geometry or "select" to edit existing ones.
   */
  setDrawingMode(mode) {
    if (!this.terraDraw) {
      return;
    }

    // A connector assembling one field item out of several features tracks none
    // of them by index, so its empty map never reaches cardinality.
    if (mode === "draw" && this.settings.cardinality > 0 && this.featureIds.size >= this.settings.cardinality) {
      this.setMessage(Drupal.t("Maximum number of elements reached."));
      return;
    }

    this.terraDraw.setMode(mode === "draw" ? this.mode : "select");
    this.setSelectedFeature(null);
    this.setMessage("");

    this.modeButtons.forEach((button) => {
      button.classList.toggle("active", button.dataset.terraDrawMode === mode);
    });
  }

  /**
   * @param {String} message
   *   Message to show alongside the mode buttons, empty to clear it.
   */
  setMessage(message) {
    const element = this.control?.querySelector(".terra-draw-message");
    if (element) {
      element.textContent = message;
    }
  }

  /**
   * @param {String} featureId
   *   Terra Draw feature ID.
   *
   * @return {GeolocationGeometry|null}
   *   Geometry of that feature.
   */
  getGeometryByFeatureId(featureId) {
    const feature = this.terraDraw.getSnapshotFeature(featureId);

    if (!feature) {
      return null;
    }

    return new GeolocationGeometry(feature.geometry.type, feature.geometry.coordinates);
  }

  /**
   * Draw geometries of this connector's mode onto the map.
   *
   * @param {Array} coordinatesList
   *   Coordinates of each feature to add.
   *
   * @return {Object[]}
   *   Terra Draw validation results, one per feature.
   */
  addTerraDrawFeatures(coordinatesList) {
    const results = this.terraDraw.addFeatures(
      coordinatesList.map((coordinates) => ({
        type: "Feature",
        geometry: {
          type: this.settings.drawing_mode.geojson_type,
          coordinates,
        },
        properties: {
          mode: this.mode,
        },
      }))
    );

    (results ?? []).forEach((result) => {
      if (!result.valid) {
        console.error(`Geolocation Geometry Terra Draw: Geometry rejected. ${result.reason ?? ""}`);
      }
    });

    return results ?? [];
  }

  /**
   * The user finished drawing a new feature.
   *
   * @param {String} featureId
   *   Terra Draw feature ID.
   */
  onFeatureDrawn(featureId) {}

  /**
   * The user moved a feature or one of its vertices.
   *
   * @param {String} featureId
   *   Terra Draw feature ID.
   */
  onFeatureUpdated(featureId) {}

  /**
   * The user removed a feature.
   *
   * @param {String} featureId
   *   Terra Draw feature ID.
   */
  onFeatureDeleted(featureId) {}

  destroy() {
    super.destroy();

    // Before the map provider tears down the map Terra Draw draws on.
    if (this.terraDraw?.enabled) {
      this.terraDraw.stop();
    }
  }

  onMapReady() {
    super.onMapReady();

    this.ready.then((terraDraw) => {
      if (!terraDraw) {
        return;
      }

      const layer = this.map.dataLayers.get("default");
      if (!layer) {
        return;
      }

      this.takeOverRenderedGeometries(this.detachRenderedGeometries(layer));
    });
  }

  /**
   * Take the geometries the map rendered server side off the map.
   *
   * Terra Draw renders what it stores itself, so the map provider's own
   * elements have to go. Removing them is flagged, or the widget would take it
   * for a deletion.
   *
   * @param {GeolocationDataLayer} layer
   *   Default data layer.
   *
   * @return {{index: Number, geometry: GeolocationGeometry}[]}
   *   Geometry of every removed element, by widget index.
   */
  detachRenderedGeometries(layer) {
    const rendered = [];

    // Points are rendered as markers, every other geometry as a shape.
    layer.shapes.forEach((shape) => rendered.push({ element: shape, index: this.getIndexByShape(shape), geometry: shape.geometry }));
    layer.markers.forEach((marker) => rendered.push({ element: marker, index: this.getIndexByMarker(marker), geometry: this.getGeometryByMarker(marker) }));

    return rendered.map(({ element, index, geometry }) => {
      element.geolocationWidgetIgnore = true;
      element.remove();

      return { index, geometry };
    });
  }

  /**
   * Hand the geometries the map rendered server side over to Terra Draw, which
   * renders them itself from there on.
   *
   * @param {{index: Number, geometry: GeolocationGeometry}[]} geometries
   *   Detached geometries, by widget index.
   */
  takeOverRenderedGeometries(geometries) {}
}
