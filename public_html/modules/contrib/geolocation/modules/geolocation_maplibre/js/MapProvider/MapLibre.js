import { GeolocationMapBase } from "../../../../js/MapProvider/GeolocationMapBase.js";
import { GeolocationBoundaries } from "../../../../js/Base/GeolocationBoundaries.js";
import { GeolocationCoordinates } from "../../../../js/Base/GeolocationCoordinates.js";
import { MapLibreMapMarker } from "../MapLibreMapMarker.js";
import { MapLibreShapeStore } from "../MapLibreShapeStore.js";
import { MapLibreShapeLine } from "../MapLibreShapeLine.js";
import { MapLibreShapePolygon } from "../MapLibreShapePolygon.js";
import { MapLibreShapeMultiLine } from "../MapLibreShapeMultiLine.js";
import { MapLibreShapeMultiPolygon } from "../MapLibreShapeMultiPolygon.js";
import { MapLibreCircle } from "../MapLibreCircle.js";

/**
 * @typedef MapLibreMapSettings
 *
 * @augments GeolocationMapSettings
 *
 * @prop {Object} maplibre_settings
 * @prop {Object} [maplibre_settings.base_map]
 * @prop {String} [maplibre_settings.base_map.import_path]
 * @prop {Object} [maplibre_settings.base_map.settings]
 * @prop {String} maplibre_settings.library_url
 * @prop {String} maplibre_settings.style_url
 * @prop {Number} maplibre_settings.zoom
 * @prop {Number} maplibre_settings.min_zoom
 * @prop {Number} maplibre_settings.max_zoom
 * @prop {String} maplibre_settings.height
 * @prop {String} maplibre_settings.width
 */

/**
 * @prop {typeof import("maplibre-gl")} maplibregl
 * @prop {import("maplibre-gl").Map} maplibreMap
 * @prop {?GeolocationBaseMap} baseMap
 * @prop {MapLibreShapeStore} shapeStore
 * @prop {MapLibreMapSettings} settings
 * @prop {Object[]} customControls
 */
export default class MapLibre extends GeolocationMapBase {
  constructor(mapSettings) {
    super(mapSettings);

    this.customControls = [];
    this.shapeStore = new MapLibreShapeStore(this);

    this.container.style.position = "relative";
    this.container.style.height = this.settings.maplibre_settings.height;
    this.container.style.width = this.settings.maplibre_settings.width;
  }

  onResize() {
    this.maplibreMap?.resize();
  }

  destroy() {
    super.destroy();

    this.maplibreMap?.remove();
  }

  initialize() {
    return super
      .initialize()
      .then(() => {
        // MapLibre GL JS 6 is an ES module with no UMD build, so the library
        // is imported here instead of being declared as a script for the map
        // loader to add. Kept on the map so features can reach it too.
        return import(this.settings.maplibre_settings.library_url);
      })
      .then((maplibregl) => {
        this.maplibregl = maplibregl;

        // A base map only ships a module where its service wants something
        // of the renderer; most contribute a style URL and nothing else.
        const importPath = this.settings.maplibre_settings.base_map?.import_path;

        return importPath ? import(importPath) : null;
      })
      .then((baseMapImport) => {
        this.baseMapImport = baseMapImport;

        return this.initializeMap();
      });
  }

  /**
   * Construct the map and wire its events up.
   *
   * @return {Promise<MapLibre>}
   *   Resolves once the style has loaded.
   */
  initializeMap() {
    const settings = this.settings.maplibre_settings;

    return new Promise((resolve, reject) => {
      this.maplibreMap = new this.maplibregl.Map({
        container: this.container,
        style: settings.style_url,
        center: [this.settings.lng, this.settings.lat],
        zoom: settings.zoom,
        minZoom: settings.min_zoom,
        maxZoom: settings.max_zoom,
      });

      if (this.baseMapImport) {
        try {
          this.baseMap = new this.baseMapImport.default(settings.base_map?.settings, this);
          // Runs before the style has been fetched, so whatever the service
          // needs is in place for the resources the style names.
          this.baseMap.attach();
        } catch (e) {
          console.error(e, `Geolocation - MapLibre: base map ${settings.base_map?.import_path} could not be attached.`);
        }
      }

      this.maplibreMap.on("error", (event) => {
        // A missing sprite or font is not worth failing the map over, so only
        // a style that never loads rejects.
        console.error("Geolocation - MapLibre:", event.error?.message ?? event);
      });

      // Sources and layers can only be added once the style is in place.
      this.maplibreMap.once("load", () => {
        this.shapeStore.attach();
        resolve();
      });

      setTimeout(() => {
        if (!this.maplibreMap.loaded()) {
          reject(new Error(`Geolocation - MapLibre: style ${settings.style_url} failed to load.`));
        }
      }, 30000);
    }).then(() => {
      this.maplibreMap.on("moveend", (event) => {
        this.features.forEach((feature) => {
          feature.onMapIdle();
        });

        const bounds = this.maplibreMap.getBounds();
        if (!bounds) {
          return;
        }

        this.features.forEach((feature) => {
          feature.onBoundsChanged(this.normalizeBoundaries(bounds));
        });
      });

      this.maplibreMap.on("click", (event) => {
        this.features.forEach((feature) => {
          feature.onClick(this.normalizeCoordinates(event.lngLat));
        });
      });

      this.maplibreMap.on("dblclick", (event) => {
        this.features.forEach((feature) => {
          feature.onDoubleClick(this.normalizeCoordinates(event.lngLat));
        });
      });

      this.maplibreMap.on("contextmenu", (event) => {
        this.features.forEach((feature) => {
          feature.onContextClick(this.normalizeCoordinates(event.lngLat));
        });
      });

      return this;
    });
  }

  createMarker(coordinates, settings) {
    return new MapLibreMapMarker(coordinates, settings, this);
  }

  createShapeLine(geometry, settings) {
    return new MapLibreShapeLine(geometry, settings, this);
  }

  createShapePolygon(geometry, settings) {
    return new MapLibreShapePolygon(geometry, settings, this);
  }

  createShapeMultiLine(geometry, settings) {
    return new MapLibreShapeMultiLine(geometry, settings, this);
  }

  createShapeMultiPolygon(geometry, settings) {
    return new MapLibreShapeMultiPolygon(geometry, settings, this);
  }

  createCircle(center, radius, settings = {}) {
    return new MapLibreCircle(center, radius, this, settings);
  }

  getBoundaries() {
    super.getBoundaries();

    return this.normalizeBoundaries(this.maplibreMap.getBounds());
  }

  getMarkerBoundaries(markers) {
    super.getMarkerBoundaries(markers);

    markers = markers || this.dataLayers.get("default").markers;
    if (!markers?.length) {
      return null;
    }

    const bounds = new this.maplibregl.LngLatBounds();
    markers.forEach((marker) => {
      bounds.extend(this.denormalizeCoordinates(marker.coordinates));
    });

    return this.normalizeBoundaries(bounds);
  }

  setBoundaries(boundaries) {
    if (super.setBoundaries(boundaries) === false) {
      return false;
    }

    this.maplibreMap.fitBounds(this.denormalizeBoundaries(boundaries));

    return true;
  }

  async getZoom() {
    return this.maplibreMap.getZoom();
  }

  setZoom(zoom, defer) {
    if (!zoom) {
      zoom = this.settings.maplibre_settings.zoom;
    }

    this.maplibreMap.setZoom(parseInt(zoom));
  }

  getCenter() {
    return this.normalizeCoordinates(this.maplibreMap.getCenter());
  }

  setCenterByCoordinates(coordinates, accuracy) {
    if (super.setCenterByCoordinates(coordinates, accuracy) === false) {
      return false;
    }

    this.maplibreMap.panTo(this.denormalizeCoordinates(coordinates));
  }

  normalizeCoordinates(coordinates) {
    if (coordinates instanceof GeolocationCoordinates) {
      return coordinates;
    }

    return new GeolocationCoordinates(coordinates.lat, coordinates.lng);
  }

  denormalizeCoordinates(coordinates) {
    if (!(coordinates instanceof GeolocationCoordinates)) {
      return coordinates;
    }

    return [coordinates.lng, coordinates.lat];
  }

  normalizeBoundaries(boundaries) {
    if (boundaries instanceof GeolocationBoundaries) {
      return boundaries;
    }

    return new GeolocationBoundaries({
      north: boundaries.getNorth(),
      east: boundaries.getEast(),
      south: boundaries.getSouth(),
      west: boundaries.getWest(),
    });
  }

  denormalizeBoundaries(boundaries) {
    if (boundaries instanceof GeolocationBoundaries) {
      return [
        [boundaries.west, boundaries.south],
        [boundaries.east, boundaries.north],
      ];
    }

    return false;
  }

  addControl(element) {
    element.classList.remove("hidden");
    // The control corners are pointer-events: none, and MapLibre gives them
    // back only to what it recognizes as one of its own controls.
    element.classList.add("maplibregl-ctrl");

    const control = {
      onAdd: () => element,
      onRemove: () => element.remove(),
    };

    this.maplibreMap.addControl(control, element.dataset.mapControlPosition ?? "top-left");
    this.customControls.push(control);
  }

  removeControls() {
    this.customControls.forEach((control) => {
      this.maplibreMap.removeControl(control);
    });

    this.customControls = [];
  }

  loadTileLayer(layerId, layerSettings) {
    // One ID for both, so the layer and its source stay findable together.
    const sourceId = `geolocation-tile-${layerId}`;

    this.maplibreMap.addSource(sourceId, {
      type: "raster",
      // MapLibre has no {s} subdomain placeholder, Leaflet's tile URLs do.
      tiles: [layerSettings.url.replace("{s}", "a")],
      tileSize: 256,
      attribution: layerSettings.attribution ?? "",
      minzoom: layerSettings.min_zoom ?? 0,
      maxzoom: layerSettings.max_zoom ?? 22,
    });

    this.maplibreMap.addLayer({
      id: sourceId,
      type: "raster",
      source: sourceId,
      paint: {
        "raster-opacity": layerSettings.opacity ?? 1,
      },
    });

    this.tileLayers.set(layerId, sourceId);

    return sourceId;
  }

  unloadTileLayer(layerId) {
    if (!this.tileLayers.has(layerId)) {
      return;
    }

    const sourceId = this.tileLayers.get(layerId);

    if (this.maplibreMap.getLayer(sourceId)) {
      this.maplibreMap.removeLayer(sourceId);
    }
    if (this.maplibreMap.getSource(sourceId)) {
      this.maplibreMap.removeSource(sourceId);
    }

    this.tileLayers.delete(layerId);
  }
}
