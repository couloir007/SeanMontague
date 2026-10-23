import { GeolocationCoordinates } from "../../../../js/Base/GeolocationCoordinates.js";
import { GeolocationMapBase } from "../../../../js/MapProvider/GeolocationMapBase.js";
import { GeolocationBoundaries } from "../../../../js/Base/GeolocationBoundaries.js";
import { GoogleMapMarker } from "../GoogleMapMarker.js";
import { GoogleShapeLine } from "../GoogleShapeLine.js";
import { GoogleShapeMultiLine } from "../GoogleShapeMultiLine.js";
import { GoogleShapePolygon } from "../GoogleShapePolygon.js";
import { GoogleShapeMultiPolygon } from "../GoogleShapeMultiPolygon.js";
import { GoogleCircle } from "../GoogleCircle.js";

/**
 * @typedef GoogleMapSettings
 *
 * @augments GeolocationMapSettings
 *
 * @prop {String} google_url
 * @prop {MapOptions} google_map_settings
 */

/**
 * @prop {GoogleMapSettings} settings
 * @prop {google.maps.Map} googleMap
 */
export default class GoogleMaps extends GeolocationMapBase {
  constructor(mapSettings) {
    super(mapSettings);

    // Set the container size.
    this.container.style.height = this.settings.google_map_settings.height;
    this.container.style.width = this.settings.google_map_settings.width;
  }

  destroy() {
    super.destroy();

    // Google Maps offers no way to dispose of a map, only of its listeners.
    if (this.googleMap) {
      google.maps.event.clearInstanceListeners(this.googleMap);
    }
  }

  initialize() {
    return super
      .initialize()
      .then(() => {
        return new Promise((resolve) => {
          Drupal.geolocation.maps.addMapProviderCallback("Google", resolve);
        });
      })
      .then(() => {
        return new Promise((resolve) => {
          this.googleMap = new google.maps.Map(
            this.container,
            Object.assign(this.settings.google_map_settings, {
              // The setting defaults to an empty string.
              mapId: this.settings.google_map_settings.mapId || this.id,
              zoom: this.settings.zoom ?? this.settings.google_map_settings.zoom ?? 2,
              maxZoom: this.settings.google_map_settings.maxZoom ?? 20,
              minZoom: this.settings.google_map_settings.minZoom ?? 0,
              center: new google.maps.LatLng(this.settings.lat, this.settings.lng),
              mapTypeId: google.maps.MapTypeId[this.settings.google_map_settings.type] ?? "roadmap",
              mapTypeControl: false, // Handled by feature.
              zoomControl: false, // Handled by feature.
              streetViewControl: false, // Handled by feature.
              rotateControl: false, // Handled by feature.
              cameraControl: false, // Handled by feature.
              fullscreenControl: false, // Handled by feature.
              scaleControl: this.settings.google_map_settings.scaleControl ?? false,
              panControl: this.settings.google_map_settings.panControl ?? false,
              gestureHandling: this.settings.google_map_settings.gestureHandling ?? "auto",
            })
          );

          resolve();
        })
          .then(() => {
            return new Promise((resolve) => {
              google.maps.event.addListenerOnce(this.googleMap, "idle", () => {
                resolve();
              });
            });
          })
          .then(() => {
            return new Promise((resolve) => {
              let singleClick;

              this.googleMap.addListener("click", (event) => {
                singleClick = setTimeout(() => {
                  this.features.forEach((feature) => {
                    feature.onClick(new GeolocationCoordinates(event.latLng.lat(), event.latLng.lng()));
                  });
                }, 500);
              });

              this.googleMap.addListener("dblclick", (event) => {
                clearTimeout(singleClick);
                this.features.forEach((feature) => {
                  feature.onDoubleClick(new GeolocationCoordinates(event.latLng.lat(), event.latLng.lng()));
                });
              });

              this.googleMap.addListener("contextmenu", (event) => {
                this.features.forEach((feature) => {
                  feature.onContextClick(new GeolocationCoordinates(event.latLng.lat(), event.latLng.lng()));
                });
              });

              this.googleMap.addListener("idle", () => {
                this.features.forEach((feature) => {
                  feature.onMapIdle();
                });
              });

              this.googleMap.addListener("bounds_changed", () => {
                const bounds = this.googleMap.getBounds();
                if (!bounds) {
                  return;
                }

                this.features.forEach((feature) => {
                  feature.onBoundsChanged(this.normalizeBoundaries(bounds));
                });
              });

              resolve(this);
            });
          });
      });
  }

  createMarker(coordinates, settings) {
    return new GoogleMapMarker(coordinates, settings, this);
  }

  getBoundaries() {
    super.getBoundaries();

    return this.normalizeBoundaries(this.googleMap.getBounds());
  }

  getMarkerBoundaries(markers) {
    super.getMarkerBoundaries(markers);

    markers = markers || this.dataLayers.get("default").markers;
    if (!markers.length) {
      return null;
    }

    // A Google Maps API tool to re-center the map on its content.
    const bounds = new google.maps.LatLngBounds();

    markers.forEach((marker) => {
      bounds.extend(marker.googleMarker.position);
    });

    return this.normalizeBoundaries(bounds) || null;
  }

  setBoundaries(boundaries) {
    if (super.setBoundaries(boundaries) === false) {
      return false;
    }

    return this.googleMap.fitBounds(this.denormalizeBoundaries(boundaries) ?? null, this.settings.google_map_settings.fitBoundsPadding ?? 0);
  }

  getZoom() {
    return new Promise((resolve) => {
      google.maps.event.addListenerOnce(this.googleMap, "idle", () => {
        resolve(this.googleMap.getZoom());
      });
    });
  }

  setZoom(zoom, defer) {
    if (!zoom) {
      zoom = this.settings.google_map_settings.zoom;
    }
    zoom = parseInt(zoom);

    this.googleMap.setZoom(zoom);

    if (defer) {
      google.maps.event.addListenerOnce(this.googleMap, "idle", () => {
        this.googleMap.setZoom(zoom);
      });
    }
  }

  getCenter() {
    const center = this.googleMap.getCenter();

    return new GeolocationCoordinates(center.lat(), center.lng());
  }

  setCenterByCoordinates(coordinates, accuracy) {
    if (super.setCenterByCoordinates(coordinates, accuracy) === false) {
      return false;
    }

    this.googleMap.setCenter(coordinates);
  }

  normalizeBoundaries(boundaries) {
    if (boundaries instanceof GeolocationBoundaries) {
      return boundaries;
    }

    if (boundaries instanceof google.maps.LatLngBounds) {
      if (boundaries.isEmpty()) {
        return false;
      }

      const northEast = boundaries.getNorthEast();
      const southWest = boundaries.getSouthWest();

      return new GeolocationBoundaries({
        north: northEast.lat(),
        east: northEast.lng(),
        south: southWest.lat(),
        west: southWest.lng(),
      });
    }

    return false;
  }

  denormalizeBoundaries(boundaries) {
    if (boundaries instanceof google.maps.LatLngBounds) {
      return boundaries;
    }

    if (boundaries instanceof GeolocationBoundaries) {
      return new google.maps.LatLngBounds({ lat: boundaries.south, lng: boundaries.west }, { lat: boundaries.north, lng: boundaries.east });
    }

    return false;
  }

  addControl(element) {
    let position = google.maps.ControlPosition.TOP_LEFT;

    const customPosition = element.getAttribute("data-map-control-position") ?? null;
    if (google.maps.ControlPosition[customPosition]) {
      position = google.maps.ControlPosition[customPosition];
    }

    // Google keeps one list of controls per position, so the position says
    // which list this control belongs in - and an unknown one has none.
    const controls = this.googleMap.controls[position];
    if (!controls) {
      console.error(element, `Google Maps: Unknown control position ${position}.`);
      return element;
    }

    // The map is handed the element itself, so adding it twice would show it
    // twice.
    let existing = null;
    controls.forEach((control) => {
      if (control === element) {
        existing = control;
      }
    });

    if (existing) {
      return existing;
    }

    element.classList.remove("hidden");
    controls.push(element);

    return element;
  }

  removeControls() {
    this.googleMap.controls.forEach((item) => {
      if (typeof item === "undefined") {
        return;
      }

      if (typeof item.clear === "function") {
        item.clear();
      }
    });
  }

  createCircle(center, radius, settings = {}) {
    return new GoogleCircle(center, radius, this, settings);
  }

  /**
   * @param {GeolocationGeometry} geometry
   *   Geometry.
   * @param {GeolocationShapeSettings} settings
   *   Settings.
   *
   * @return {GoogleShapeLine}
   *   Shape.
   */
  createShapeLine(geometry, settings) {
    return new GoogleShapeLine(geometry, settings, this);
  }

  /**
   * @param {GeolocationGeometry} geometry
   *   Geometry.
   * @param {GeolocationShapeSettings} settings
   *   Settings.
   *
   * @return {GoogleShapePolygon}
   *   Shape.
   */
  createShapePolygon(geometry, settings) {
    return new GoogleShapePolygon(geometry, settings, this);
  }

  /**
   * @param {GeolocationGeometry} geometry
   *   Geometry.
   * @param {GeolocationShapeSettings} settings
   *   Settings.
   *
   * @return {GoogleShapeMultiLine}
   *   Shape.
   */
  createShapeMultiLine(geometry, settings) {
    return new GoogleShapeMultiLine(geometry, settings, this);
  }

  /**
   * @param {GeolocationGeometry} geometry
   *   Geometry.
   * @param {GeolocationShapeSettings} settings
   *   Settings.
   *
   * @return {GoogleShapeMultiPolygon}
   *   Shape.
   */
  createShapeMultiPolygon(geometry, settings) {
    return new GoogleShapeMultiPolygon(geometry, settings, this);
  }

  getShapeBoundaries(shapes) {
    super.getShapeBoundaries(shapes);

    shapes = shapes || this.dataLayers.get("default").shapes;
    if (!shapes.length) {
      return null;
    }

    // A Google Maps API tool to re-center the map on its content.
    const bounds = new google.maps.LatLngBounds();

    shapes.forEach((shape) => {
      shape.googleShapes.forEach((googleShape) => {
        googleShape.getPath().forEach((element) => {
          bounds.extend(element);
        });
      });
    });

    return this.normalizeBoundaries(bounds);
  }

  loadTileLayer(layerId, layerSettings) {
    const layer = new google.maps.ImageMapType({
      name: layerSettings.label || layerId,
      getTileUrl(coord, zoom) {
        return layerSettings.url.replace("{x}", coord.x.toString()).replace("{y}", coord.y.toString()).replace("{z}", zoom.toString()).replace("{s}", "a");
      },
      tileSize: new google.maps.Size(256, 256),
      minZoom: layerSettings.min_zoom || 1,
      maxZoom: layerSettings.max_zoom || 20,
      opacity: layerSettings.opacity || 1,
    });

    if (layerSettings.overlay) {
      this.googleMap.overlayMapTypes.push(layer);
    } else {
      this.googleMap.mapTypes.unbind(layerId);
      this.googleMap.mapTypes.set(layerId, layer);
      this.googleMap.setMapTypeId(layerId);
    }

    if (layer) {
      this.tileLayers.set(layerId, layer);
    }

    return layer;
  }

  unloadTileLayer(layerId) {
    if (!this.tileLayers.has(layerId)) {
      return;
    }

    const overlayIndex = this.googleMap.overlayMapTypes.getArray().indexOf(this.tileLayers.get(layerId));
    if (overlayIndex > -1) {
      this.googleMap.overlayMapTypes.removeAt(overlayIndex);
    } else {
      this.googleMap.setMapTypeId(google.maps.MapTypeId[this.settings.google_map_settings.type] ?? "roadmap");
      this.googleMap.mapTypes.unbind(layerId);
    }

    this.tileLayers.delete(layerId);
  }
}
