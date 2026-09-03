import { GeolocationCoordinates } from "../../../../js/Base/GeolocationCoordinates.js";
import { GeolocationMapBase } from "../../../../js/MapProvider/GeolocationMapBase.js";
import { GeolocationBoundaries } from "../../../../js/Base/GeolocationBoundaries.js";
import { BaiduMapMarker } from "../BaiduMapMarker.js";
import { BaiduShapeLine } from "../BaiduShapeLine.js";
import { BaiduShapePolygon } from "../BaiduShapePolygon.js";
import { BaiduShapeMultiLine } from "../BaiduShapeMultiLine.js";
import { BaiduShapeMultiPolygon } from "../BaiduShapeMultiPolygon.js";
import { BaiduCircle } from "../BaiduCircle.js";

/* global BMAP_ANCHOR_TOP_LEFT */

/**
 * @typedef BaiduMapSettings
 *
 * @extends GeolocationMapSettings
 *
 * @prop {String} baidu_api_url
 * @prop {MapOptions} baidu_settings
 */

/**
 * @prop {BMapGL.Map} baiduMap
 * @prop {BMapGL.Control[]} customControls
 * @prop {BaiduMapSettings} settings
 * @prop {BMapGL.TileLayer[]} tileLayers
 */
export default class Baidu extends GeolocationMapBase {
  /**
   * @constructor
   *
   * @param {BaiduMapSettings} mapSettings
   *   Settings.
   */
  constructor(mapSettings) {
    super(mapSettings);

    this.customControls = [];

    // Set the container size.
    this.container.style.height = this.settings.baidu_settings.height;
    this.container.style.width = this.settings.baidu_settings.width;
  }

  initialize() {
    return super
      .initialize()
      .then(() => {
        return new Promise((resolve) => {
          Drupal.geolocation.maps.addMapProviderCallback("Baidu", resolve);
        });
      })
      .then(() => {
        return new Promise((resolve) => {
          this.baiduMap = new BMapGL.Map(this.container, this.settings.baidu_settings);
          this.baiduMap.centerAndZoom(new BMapGL.Point(this.settings.lng, this.settings.lat), this.settings.zoom ?? 2);
          resolve();
        }).then(() => {
          return new Promise((resolve) => {
            let singleClick;

            this.baiduMap.addEventListener("click", (event) => {
              singleClick = setTimeout(() => {
                this.features.forEach((feature) => {
                  feature.onClick(new GeolocationCoordinates(event.point.lat, event.point.lng));
                });
              }, 500);
            });

            this.baiduMap.addEventListener("dblclick", (event) => {
              clearTimeout(singleClick);
              this.features.forEach((feature) => {
                feature.onDoubleClick(new GeolocationCoordinates(event.point.lat, event.point.lng));
              });
            });

            this.baiduMap.addEventListener("rightclick", (event) => {
              this.features.forEach((feature) => {
                feature.onContextClick(new GeolocationCoordinates(event.point.lat, event.point.lng));
              });
            });

            this.baiduMap.addEventListener("moveend", () => {
              this.updatingBounds = false;

              this.features.forEach((feature) => {
                feature.onMapIdle();
              });
            });

            this.baiduMap.addEventListener("moveend", () => {
              const bounds = this.getBoundaries();
              if (!bounds) {
                return;
              }

              this.features.forEach((feature) => {
                feature.onBoundsChanged(bounds);
              });
            });

            resolve(this);
          });
        });
      });
  }

  createCircle(center, radius, settings = {}) {
    return new BaiduCircle(center, radius, this, settings);
  }

  createMarker(coordinates, settings) {
    const marker = new BaiduMapMarker(coordinates, settings, this);
    this.baiduMap.addOverlay(marker.baiduMarker);

    return marker;
  }

  createShapeLine(geometry, settings) {
    return new BaiduShapeLine(geometry, settings, this);
  }

  createShapePolygon(geometry, settings) {
    return new BaiduShapePolygon(geometry, settings, this);
  }

  createShapeMultiLine(geometry, settings) {
    return new BaiduShapeMultiLine(geometry, settings, this);
  }

  createShapeMultiPolygon(geometry, settings) {
    return new BaiduShapeMultiPolygon(geometry, settings, this);
  }

  getBoundaries() {
    super.getBoundaries();

    return this.normalizeBoundaries(this.baiduMap.getBounds());
  }

  getShapeBoundaries(shapes) {
    super.getShapeBoundaries(shapes);

    shapes = shapes || this.dataLayers.get("default").shapes;
    if (!shapes.length) {
      return null;
    }

    let bounds;

    shapes.forEach((shape) => {
      shape.baiduShapes.forEach((baiduShape) => {
        baiduShape.getPath().forEach((point) => {
          if (!bounds) {
            bounds = new BMapGL.Bounds(point, point);
          } else {
            bounds.extend(point);
          }
        });
      });
    });

    return this.normalizeBoundaries(bounds);
  }

  getMarkerBoundaries(markers) {
    super.getMarkerBoundaries(markers);

    markers = markers || this.dataLayers.get("default").markers;
    if (!markers) {
      return null;
    }

    let bounds;

    markers.forEach((marker) => {
      if (!bounds) {
        bounds = new BMapGL.Bounds(marker.baiduMarker.getPosition(), marker.baiduMarker.getPosition());
      } else {
        bounds.extend(marker.baiduMarker.getPosition());
      }
    });

    return this.normalizeBoundaries(bounds);
  }

  setBoundaries(boundaries) {
    if (super.setBoundaries(boundaries) === false) {
      return false;
    }

    /** @type {BMapGL.Bounds} */
    boundaries = this.denormalizeBoundaries(boundaries);

    this.baiduMap.setViewport([boundaries.getNorthEast(), boundaries.getSouthWest()]);

    return this;
  }

  getZoom() {
    this.baiduMap.getZoom();
  }

  setZoom(zoom, defer) {
    if (!zoom) {
      zoom = this.settings.baidu_settings.zoom;
    }
    zoom = parseInt(zoom);

    this.baiduMap.setZoom(zoom);
  }

  getCenter() {
    const center = this.baiduMap.getCenter();

    return new GeolocationCoordinates(center.lat, center.lng);
  }

  setCenterByCoordinates(coordinates, accuracy) {
    if (super.setCenterByCoordinates(coordinates, accuracy) === false) {
      return false;
    }

    this.baiduMap.panTo(new BMapGL.Point(coordinates.lng, coordinates.lat));
  }

  normalizeBoundaries(boundaries) {
    if (boundaries instanceof GeolocationBoundaries) {
      return boundaries;
    }

    if (boundaries instanceof BMapGL.Bounds) {
      if (boundaries.isEmpty()) {
        return null;
      }
      return new GeolocationBoundaries({
        north: boundaries.getNorthEast().lat,
        east: boundaries.getNorthEast().lng,
        south: boundaries.getSouthWest().lat,
        west: boundaries.getSouthWest().lng,
      });
    }

    return null;
  }

  denormalizeBoundaries(boundaries) {
    if (boundaries instanceof BMapGL.Bounds) {
      return boundaries;
    }

    if (boundaries instanceof GeolocationBoundaries) {
      return new BMapGL.Bounds(new BMapGL.Point(boundaries.east, boundaries.north), new BMapGL.Point(boundaries.west, boundaries.south));
    }

    return false;
  }

  addControl(element) {
    element.classList.remove("hidden");
    element.style.position = "absolute";
    element.style.zIndex = "400";
    const control = new BMapGL.Control({
      anchor: window[element.getAttribute("data-map-control-position")] ?? BMAP_ANCHOR_TOP_LEFT,
      offset: new BMapGL.Size(50, 50),
    });

    control.initialize = (map) => {
      map.getContainer().appendChild(element);
    };

    this.baiduMap.addControl(control);
  }

  removeControls() {
    this.customControls.forEach((control) => {
      this.baiduMap.removeControl(control);
    });
  }

  loadTileLayer(layerId, layerSettings) {
    const layer = new BMapGL.TileLayer();

    layer.getTilesUrl = (tileCoord, zoom) => {
      const offset = 2 ** (zoom - 1);
      const tileX = tileCoord.x + offset;
      const tileY = offset - tileCoord.y - 1;

      return layerSettings.url.replace("{s}", "a").replace("{x}", tileX.toString()).replace("{y}", tileY.toString()).replace("{z}", zoom.toString());
    };

    this.baiduMap.addTileLayer(layer);

    this.tileLayers.set(layerId, layer);
  }

  unloadTileLayer(layerId) {
    if (!this.tileLayers.has(layerId)) {
      return;
    }

    const layer = this.tileLayers.get(layerId);
    this.baiduMap.removeTileLayer(layer);
  }
}
