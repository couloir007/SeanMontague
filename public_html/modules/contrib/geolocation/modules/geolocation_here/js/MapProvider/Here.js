import { GeolocationCoordinates } from "../../../../js/Base/GeolocationCoordinates.js";
import { GeolocationMapBase } from "../../../../js/MapProvider/GeolocationMapBase.js";
import { GeolocationBoundaries } from "../../../../js/Base/GeolocationBoundaries.js";
import { HereMapMarker } from "../HereMapMarker.js";

/**
 * @prop {string} drupalSettings.geolocation.hereMapsAppId
 * @prop {string} drupalSettings.geolocation.hereMapsAppCode
 */

/**
 * @typedef HereMapSettings
 *
 * @extends GeolocationMapSettings
 *
 * @prop {MapOptions} here_settings
 */

/**
 * @prop {H.Map} hereMap
 * @prop {HereMapSettings} settings
 */
export default class Here extends GeolocationMapBase {
  constructor(mapSettings) {
    super(mapSettings);

    this.settings.here_settings = this.settings.here_settings ?? {};

    // Set the container size.
    if (this.settings.here_settings.height) {
      this.container.style.height = this.settings.here_settings.height;
    }
    if (this.settings.here_settings.width) {
      this.container.style.width = this.settings.here_settings.width;
    }
  }

  initialize() {
    return (
      super
        .initialize()
        // These scripts have to be loaded in order. Else they fail. *Shrug*
        .then(() => {
          return Drupal.geolocation.addScript("https://js.api.here.com/v3/3.1/mapsjs-core.js");
        })
        .then(() => {
          return Drupal.geolocation.addScript("https://js.api.here.com/v3/3.1/mapsjs-service.js");
        })
        .then(() => {
          return Drupal.geolocation.addScript("https://js.api.here.com/v3/3.1/mapsjs-mapevents.js");
        })
        .then(() => {
          return Drupal.geolocation.addScript("https://js.api.here.com/v3/3.1/mapsjs-ui.js");
        })
        .then(() => {
          return new Promise((resolve) => {
            const platform = new H.service.Platform({
              apikey: "8uBFTG24EHZR5NGnVl7keO6FSEBwE5tFZLhb2qqRSQ4",
            });

            const defaultLayers = platform.createDefaultLayers();

            // Instantiate (and display) a map object:
            this.hereMap = new H.Map(this.container, defaultLayers.vector.normal.map, {
              zoom: this.settings.here_settings.zoom ?? 10,
              center: { lng: this.settings.lng, lat: this.settings.lat },
            });

            const behavior = new H.mapevents.Behavior(new H.mapevents.MapEvents(this.hereMap));

            H.ui.UI.createDefault(this.hereMap, defaultLayers);

            this.hereMap.getViewPort().resize();

            resolve();
          }).then(() => {
            return new Promise((resolve) => {
              let singleClick;

              this.hereMap.addEventListener("tap", (e) => {
                const coord = this.hereMap.screenToGeo(e.currentPointer.viewportX, e.currentPointer.viewportY);
                singleClick = setTimeout(() => {
                  this.features.forEach((feature) => {
                    feature.onClick(new GeolocationCoordinates(coord.lat, coord.lng));
                  });
                }, 500);
              });

              this.hereMap.addEventListener("dbltap", (event) => {
                clearTimeout(singleClick);
                const coord = this.hereMap.screenToGeo(event.currentPointer.viewportX, event.currentPointer.viewportY);
                this.features.forEach((feature) => {
                  feature.onDoubleClick(new GeolocationCoordinates(coord.lat, coord.lng));
                });
              });

              this.hereMap.addEventListener("contextmenu", (e) => {
                const coord = this.hereMap.screenToGeo(e.viewportX, e.viewportY);
                this.features.forEach((feature) => {
                  feature.onContextClick(new GeolocationCoordinates(coord.lat, coord.lng));
                });
              });

              this.hereMap.addEventListener("dragend", () => {
                this.updatingBounds = false;

                this.features.forEach((feature) => {
                  feature.onMapIdle();
                });
              });

              this.hereMap.addEventListener("dragend", () => {
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
        })
    );
  }

  createMarker(coordinates, settings) {
    const marker = new HereMapMarker(coordinates, settings, this);
    this.hereMap.addObject(marker.hereMarker);

    return marker;
  }

  createShapeLine(geometry, settings) {
    const shape = super.createShapeLine(geometry, settings);

    const lineString = new H.geo.LineString();
    geometry.points.forEach((point) => {
      lineString.pushPoint({ lat: point.lat, lng: point.lng });
    });

    const line = new H.map.Polyline(lineString, {
      style: {
        strokeColor: `rgba(${parseInt(settings.strokeColor.substring(1, 3))}, ${parseInt(settings.strokeColor.substring(3, 5))}, ${parseInt(settings.strokeColor.substring(5, 7))}, ${settings.strokeOpacity})`,
        lineWidth: settings.strokeWidth,
      },
    });

    shape.hereShapes = [line];

    this.hereMap.addObject(line);

    return shape;
  }

  createShapePolygon(geometry, settings) {
    const shape = super.createShapePolygon(geometry, settings);

    const lineString = new H.geo.LineString();
    geometry.points.forEach((point) => {
      lineString.pushPoint({ lat: point.lat, lng: point.lng });
    });

    const polygon = new H.map.Polygon(lineString, {
      style: {
        strokeColor: `rgba(${parseInt(settings.strokeColor.substring(1, 3))}, ${parseInt(settings.strokeColor.substring(3, 5))}, ${parseInt(settings.strokeColor.substring(5, 7))}, ${settings.strokeOpacity})`,
        lineWidth: settings.strokeWidth,
        fillColor: `rgba(${parseInt(settings.fillColor.substring(1, 3))}, ${parseInt(settings.fillColor.substring(3, 5))}, ${parseInt(settings.fillColor.substring(5, 7))}, ${settings.fillOpacity})`,
      },
    });

    shape.hereShapes = [polygon];

    this.hereMap.addObject(polygon);

    return shape;
  }

  /**
   *
   * @param {GeolocationShape} shape
   *   Shape.
   * @param {google.maps.MVCObject[]} shape.googleShapes
   *   Google Shapes.
   */
  removeShape(shape) {
    if (!shape) {
      return;
    }

    // this.hereMap.removeObject(shape);
    if (shape.googleShapes) {
      shape.googleShapes.forEach((googleShape) => {
        googleShape.remove();
      });
    }

    shape.remove();
  }

  getBoundaries() {
    super.getBoundaries();

    return this.normalizeBoundaries(this.hereMap.getViewModel().getLookAtData().bounds.getBoundingBox());
  }

  getMarkerBoundaries(markers) {
    super.getMarkerBoundaries(markers);

    markers = markers || this.dataLayers.get("default").markers;
    if (!markers) {
      return null;
    }

    const bounds = new H.geo.MultiPoint([]);

    markers.forEach((marker) => {
      bounds.push(marker.hereMarker.getGeometry());
    });

    return this.normalizeBoundaries(bounds.getBoundingBox());
  }

  setBoundaries(boundaries) {
    if (super.setBoundaries(boundaries) === false) {
      return false;
    }

    boundaries = this.denormalizeBoundaries(boundaries);

    this.hereMap.getViewModel().setLookAtData({
      bounds: boundaries,
    });

    return this;
  }

  getZoom() {
    this.hereMap.getZoom();
  }

  setZoom(zoom, defer) {
    if (!zoom) {
      zoom = this.settings.here_settings.zoom;
    }
    zoom = parseInt(zoom ?? 10);

    this.hereMap.setZoom(zoom);
  }

  getCenter() {
    const center = this.hereMap.getCenter();

    return new GeolocationCoordinates(center.lat, center.lng);
  }

  setCenterByCoordinates(coordinates, accuracy) {
    super.setCenterByCoordinates(coordinates, accuracy);

    this.hereMap.setCenter(coordinates);
  }

  normalizeBoundaries(boundaries) {
    if (boundaries instanceof GeolocationBoundaries) {
      return boundaries;
    }

    if (boundaries instanceof H.geo.Rect) {
      return new GeolocationBoundaries({
        north: boundaries.getTop(),
        east: boundaries.getLeft(),
        south: boundaries.getBottom(),
        west: boundaries.getRight(),
      });
    }

    return false;
  }

  denormalizeBoundaries(boundaries) {
    if (boundaries instanceof H.geo.Rect) {
      return boundaries;
    }

    if (boundaries instanceof GeolocationBoundaries) {
      return new H.geo.Rect(boundaries.north, boundaries.west, boundaries.south, boundaries.east);
    }

    return false;
  }
}
