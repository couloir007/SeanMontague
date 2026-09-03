// noinspection TypeScriptUMDGlobal

import { GeolocationCoordinates } from "../../../../js/Base/GeolocationCoordinates.js";
import { GeolocationMapBase } from "../../../../js/MapProvider/GeolocationMapBase.js";
import { GeolocationBoundaries } from "../../../../js/Base/GeolocationBoundaries.js";
import { LeafletMapMarker } from "../LeafletMapMarker.js";
import { LeafletShapeLine } from "../LeafletShapeLine.js";
import { LeafletShapeMultiPolygon } from "../LeafletShapeMultiPolygon.js";
import { LeafletShapePolygon } from "../LeafletShapePolygon.js";
import { LeafletShapeMultiLine } from "../LeafletShapeMultiLine.js";
import { LeafletCircle } from "../LeafletCircle.js";

/**
 * @typedef LeafletSettings
 *
 * @extends GeolocationMapSettings
 * @extends L.MapOptions
 */

/**
 * @prop {LeafletSettings} settings
 * @prop {L.Map} leafletMap
 * @prop {L.LayerGroup} markerLayer
 * @prop {L.Control[]} controls
 * @prop {L.Layer[]} tileLayers
 */
export default class Leaflet extends GeolocationMapBase {
  constructor(mapSettings) {
    super(mapSettings);

    // Set the container size.
    this.container.style.height = this.settings.height ?? "400px";
    this.container.style.width = this.settings.width ?? "100%";
  }

  initialize() {
    return super.initialize().then(() => {
      return new Promise((resolve) => {
        const mapOptions = Object.assign(this.settings, {
          attributionControl: this.settings.attributionControl ?? false,
          bounceAtZoomLimits: this.settings.bounceAtZoomLimits ?? true,
          center: [this.settings.lat, this.settings.lng],
          crs: L.CRS[this.settings.crs ?? "EPSG3857"],
          doubleClickZoom: this.settings.doubleClickZoom ?? true,
          zoom: this.settings.zoom ?? 10,
          minZoom: this.settings.minZoom ?? 0,
          maxZoom: this.settings.maxZoom ?? 20,
          zoomControl: this.settings.zoomControl ?? false,
          preferCanvas: this.settings.preferCanvas ?? false,
          zoomSnap: this.settings.zoomSnap ?? 1,
          zoomDelta: this.settings.zoomDelta ?? 1,
          trackResize: this.settings.trackResize ?? true,
          boxZoom: this.settings.boxZoom ?? true,
          dragging: this.settings.dragging ?? true,
          zoomAnimation: this.settings.zoomAnimation ?? true,
          zoomAnimationThreshold: this.settings.zoomAnimationThreshold ?? 4,
          fadeAnimation: this.settings.fadeAnimation ?? true,
          markerZoomAnimation: this.settings.markerZoomAnimation ?? true,
          inertia: this.settings.inertia ?? false,
          inertiaDeceleration: this.settings.inertiaDeceleration ?? 3000,
          easeLinearity: this.settings.easeLinearity ?? 0.2,
          worldCopyJump: this.settings.worldCopyJump ?? false,
          maxBoundsViscosity: this.settings.maxBoundsViscosity ?? 0.0,
          keyboard: this.settings.keyboard ?? true,
          keyboardPanDelta: this.settings.keyboardPanDelta ?? 80,
          scrollWheelZoom: this.settings.scrollWheelZoom ?? true,
          wheelDebounceTime: this.settings.wheelDebounceTime ?? 40,
          wheelPxPerZoomLevel: this.settings.wheelPxPerZoomLevel ?? 60,
          tap: this.settings.tap ?? true,
          tapTolerance: this.settings.tapTolerance ?? 15,
          touchZoom: this.settings.touchZoom ?? true,
        });

        this.leafletMap = L.map(this.container, mapOptions);

        this.markerLayer = L.layerGroup().addTo(this.leafletMap);
        this.tileLayer = L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png").addTo(this.leafletMap);

        resolve();
      }).then(() => {
        return new Promise((resolve) => {
          let singleClick;

          this.leafletMap.on(
            "click",
            /** @param {L.LeafletMouseEvent} e Event */ (e) => {
              singleClick = setTimeout(() => {
                this.features.forEach((feature) => {
                  feature.onClick(new GeolocationCoordinates(e.latlng.lat, e.latlng.lng));
                });
              }, 500);
            }
          );

          this.leafletMap.on(
            "dblclick",
            /** @param {L.LeafletMouseEvent} e Event */ (e) => {
              clearTimeout(singleClick);
              this.features.forEach((feature) => {
                feature.onDoubleClick(new GeolocationCoordinates(e.latlng.lat, e.latlng.lng));
              });
            }
          );

          this.leafletMap.on(
            "contextmenu",
            /** @param {L.LeafletMouseEvent} e Event */ (e) => {
              this.features.forEach((feature) => {
                feature.onContextClick(new GeolocationCoordinates(e.latlng.lat, e.latlng.lng));
              });
            }
          );

          this.leafletMap.on("moveend", () => {
            const bounds = this.getBoundaries();

            this.features.forEach((feature) => {
              feature.onMapIdle();
              if (!bounds) {
                return;
              }
              feature.onBoundsChanged(this.normalizeBoundaries(bounds));
            });
          });

          resolve(this);
        });
      });
    });
  }

  createMarker(coordinates, settings) {
    return new LeafletMapMarker(coordinates, settings, this);
  }

  createCircle(center, radius, settings = {}) {
    return new LeafletCircle(center, radius, this, settings);
  }

  createShapeLine(geometry, settings) {
    return new LeafletShapeLine(geometry, settings, this);
  }

  createShapePolygon(geometry, settings) {
    return new LeafletShapePolygon(geometry, settings, this);
  }

  createShapeMultiLine(geometry, settings) {
    return new LeafletShapeMultiLine(geometry, settings, this);
  }

  createShapeMultiPolygon(geometry, settings) {
    return new LeafletShapeMultiPolygon(geometry, settings, this);
  }

  getMarkerBoundaries(markers) {
    super.getMarkerBoundaries(markers);

    markers = markers || this.dataLayers.get("default").markers;
    if (!markers.length) {
      return null;
    }

    const leafletMarkers = [];
    markers.forEach((marker) => {
      leafletMarkers.push(marker.leafletMarker);
    });

    const group = new L.featureGroup(leafletMarkers);

    return this.normalizeBoundaries(group.getBounds());
  }

  getShapeBoundaries(shapes) {
    super.getShapeBoundaries(shapes);

    shapes = shapes || this.dataLayers.get("default").shapes;
    if (!shapes.length) {
      return null;
    }

    let leafletShapes = [];
    shapes.forEach((shape) => {
      leafletShapes = leafletShapes.concat(shape.leafletShapes);
    });

    const group = new L.featureGroup(leafletShapes);

    return this.normalizeBoundaries(group.getBounds());
  }

  getBoundaries() {
    return this.normalizeBoundaries(this.leafletMap.getBounds());
  }

  setBoundaries(boundaries) {
    if (super.setBoundaries(boundaries) === false) {
      return false;
    }

    this.leafletMap.fitBounds(this.denormalizeBoundaries(boundaries) ?? false);
  }

  getZoom() {
    return new Promise((resolve) => {
      resolve(this.leafletMap.getZoom());
    });
  }

  setZoom(zoom, defer) {
    if (!zoom) {
      zoom = this.settings.zoom;
    }
    this.leafletMap.setZoom(parseInt(zoom));
  }

  getCenter() {
    const center = this.leafletMap.getCenter();

    return new GeolocationCoordinates(center.lat, center.lng);
  }

  setCenterByCoordinates(coordinates, accuracy) {
    if (super.setCenterByCoordinates(coordinates, accuracy) === false) {
      return false;
    }

    this.leafletMap.panTo(coordinates);
  }

  normalizeBoundaries(boundaries) {
    if (boundaries instanceof GeolocationBoundaries) {
      return boundaries;
    }

    if (boundaries instanceof L.LatLngBounds) {
      return new GeolocationBoundaries({
        north: boundaries.getNorth(),
        east: boundaries.getEast(),
        south: boundaries.getSouth(),
        west: boundaries.getWest(),
      });
    }

    return false;
  }

  /**
   * @param {GeolocationBoundaries} boundaries
   *   Boundaries.
   *
   * @return {L.LatLngBounds|null}
   *   Boundaries.
   */
  denormalizeBoundaries(boundaries) {
    if (typeof boundaries === "undefined") {
      return null;
    }

    if (boundaries instanceof L.LatLngBounds) {
      return boundaries;
    }

    if (boundaries instanceof GeolocationBoundaries) {
      return L.latLngBounds([
        [boundaries.south, boundaries.west],
        [boundaries.north, boundaries.east],
      ]);
    }

    return null;
  }

  addControl(element) {
    this.controls = this.controls || [];
    const controlElement = new (L.Control.extend({
      options: {
        position: typeof element.dataset.mapControlPosition === "undefined" ? "topleft" : element.dataset.mapControlPosition,
      },
      onAdd: () => {
        element.style.display = "block";
        L.DomEvent.disableClickPropagation(element);
        return element;
      },
    }))();
    controlElement.addTo(this.leafletMap);
    this.controls.push(controlElement);
  }

  removeControls() {
    this.controls = this.controls || [];
    this.controls.forEach((control) => {
      this.leafletMap.removeControl(control);
    });
  }

  loadTileLayer(layerId, layerSettings) {
    const layer = L.tileLayer(layerSettings.url, {
      attribution: layerSettings.attribution,
    }).addTo(this.leafletMap);

    if (layer) {
      this.tileLayers.set(layerId, layer);
    }

    return layer;
  }

  unloadTileLayer(layerId) {
    if (!this.tileLayers.has(layerId)) {
      return;
    }

    const layer = this.tileLayers.get(layerId);
    this.leafletMap.removeLayer(layer);

    this.tileLayers.delete(layerId);
  }
}
