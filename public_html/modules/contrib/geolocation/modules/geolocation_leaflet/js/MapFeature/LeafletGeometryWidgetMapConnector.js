import { LeafletMapFeature } from "./LeafletMapFeature.js";
import { GeolocationCoordinates } from "../../../../js/Base/GeolocationCoordinates.js";

export default class LeafletGeometryWidgetMapConnector extends LeafletMapFeature {
  constructor(settings, map) {
    super(settings, map);

    const drawTypes = {
      polyline: false,
      polygon: false,
      rectangle: false,
      circle: false,
      circlemarker: false,
      marker: false,
    };

    switch (this.settings.field_type.replace("geolocation_geometry_", "")) {
      case "multiline":
      case "line":
        drawTypes.polyline = true;
        break;
      case "multipolygon":
      case "polygon":
        drawTypes.polygon = true;
        break;
      case "multipoint":
      case "point":
        drawTypes.marker = true;
        break;
    }

    this.drawnItemsGroup = L.featureGroup().addTo(this.map.leafletMap);

    const drawControl = new L.Control.Draw({
      draw: drawTypes,
      edit: {
        featureGroup: this.drawnItemsGroup,
      },
    });
    this.map.leafletMap.addControl(drawControl);
  }

  onMapReady() {
    super.onMapReady();

    this.map.dataLayers.get("default").shapes.forEach((shape) => {
      shape.leafletShapes.forEach((leafletShape) => {
        const index = this.getIndexByShape(shape);

        let editableShape;
        switch (shape.geometry.type) {
          case "Point":
            editableShape = L.marker(leafletShape.getLatLng());
            break;
          case "LineString":
            editableShape = L.polyline(leafletShape.getLatLngs());
            break;
          case "Polygon":
            editableShape = L.polygon(leafletShape.getLatLngs());
            break;
        }

        editableShape.widgetIndex = index;
        editableShape.bindTooltip((index + 1).toString());
        this.drawnItemsGroup.addLayer(editableShape);
      });
    });

    this.map.leafletMap.on(
      L.Draw.Event.CREATED,
      /** @param {DrawEvents.Created} event */ (event) => {
        let currentElementCount = 0;
        this.drawnItemsGroup.eachLayer(() => {
          currentElementCount++;
        });

        let newIndex = 0;
        if (this.settings.cardinality > currentElementCount || this.settings.cardinality === -1) {
          this.drawnItemsGroup.eachLayer(
            /**
             * @param {int} element.widgetIndex
             * */
            (element) => {
              if (typeof element.widgetIndex === "undefined") {
                return;
              }
              if (element.widgetIndex >= newIndex) {
                newIndex = element.widgetIndex + 1;
              }
            }
          );
        } else {
          const warning = document.createElement("div");
          warning.innerHTML = `<p>${Drupal.t("Maximum number of element reached.")}</p>`;
          Drupal.dialog(warning, {
            title: Drupal.t("Synchronization"),
          }).showModal();
          return;
        }

        switch (event.layerType) {
          case "marker":
            const geometry = event.layer.toGeoJSON().geometry;
            const marker = this.createMarker(new GeolocationCoordinates(geometry.coordinates[1], geometry.coordinates[0]));
            this.setIndexByMarker(marker, newIndex);
            this.map.dataLayers.get("default").markerAdded(marker);
            break;

          case "polyline":
            const polyline = this.map.createShapeLine(event.layer.toGeoJSON().geometry);
            this.setIndexByShape(polyline, newIndex);
            this.map.dataLayers.get("default").shapeAdded(polyline);
            break;

          case "rectangle":
          case "polygon":
            const polygon = this.map.createShapePolygon(event.layer.toGeoJSON().geometry);
            this.setIndexByShape(polygon, newIndex);
            this.map.dataLayers.get("default").shapeAdded(polygon);
            break;
        }

        event.layer.widgetIndex = newIndex;
        this.drawnItemsGroup.addLayer(event.layer);
      }
    );

    this.map.leafletMap.on(
      L.Draw.Event.EDITVERTEX,
      /**
       * @param {DrawEvents.EditVertex} event
       */
      (event) => {
        if (typeof event.poly === "undefined") {
          return console.error("Updated poly could not be identified.");
        }

        /**
         * @type {Polyline | Polygon}
         *
         * @prop {int} widgetIndex
         */
        const poly = event.poly;

        if (typeof poly.widgetIndex === "undefined") {
          return console.error("Updated poly could not be associated to shape.");
        }

        const shape = this.getShapeByIndex(poly.widgetIndex);
        shape.update(poly.toGeoJSON().geometry);
        this.map.dataLayers.get("default").shapeUpdated(shape);
      }
    );

    this.map.leafletMap.on(
      L.Draw.Event.DELETED,
      /**
       * @param {DrawEvents.Deleted} event
       */
      (event) => {
        event.layers.eachLayer(
          /**
           * @param {Layer} layer
           * @param {int} layer.widgetIndex
           */
          (layer) => {
            if (typeof layer.widgetIndex === "undefined") {
              return console.error("Updated layer could not be associated to shape.");
            }
            const shape = this.getShapeByIndex(layer.widgetIndex);
            shape.remove();
            this.map.dataLayers.get("default").shapeRemoved(shape);
          }
        );
      }
    );
  }

  setWidgetSubscriber(subscriber) {
    this.subscriber = subscriber;
  }

  /**
   * @param {GeolocationShape} shape
   *   Marker.
   *
   * @return {int|null}
   *   Index.
   */
  getIndexByShape(shape) {
    return Number(shape.wrapper.dataset.geolocationWidgetIndex ?? 0);
  }

  /**
   * @param {int} index
   *   Index.
   *
   * @return {GeolocationShape|null}
   *   Shape.
   */
  getShapeByIndex(index) {
    let returnValue = null;
    this.map.dataLayers.get("default").shapes.forEach((shape) => {
      if (index === this.getIndexByShape(shape)) {
        returnValue = shape;
      }
    });

    return returnValue;
  }

  /**
   * @param {GeolocationShape} shape
   *   Marker.
   * @param {int|false} index
   *   Index.
   */
  setIndexByShape(shape, index = false) {
    if (index === false) {
      delete shape.wrapper.dataset.geolocationWidgetIndex;
    } else {
      shape.wrapper.dataset.geolocationWidgetIndex = index.toString();
    }
  }

  /**
   * @param {Number} index
   *   Index.
   *
   * @return {String}
   *   Title.
   */
  getShapeTitle(index) {
    return `${index + 1}`;
  }

  addShapeSilently(index, geometry) {
    let shape;

    switch (geometry.type) {
      case "Point":
        const coordinates = new GeolocationCoordinates(geometry.coordinates[1], geometry.coordinates[0]);
        const marker = this.map.createMarker(coordinates, {
          title: this.getMarkerTitle(index, coordinates),
          label: index + 1,
          draggable: true,
        });

        this.setIndexByMarker(marker, index);

        marker.geolocationWidgetIgnore = true;
        this.map.dataLayers.get("default").markerAdded(marker);
        delete marker.geolocationWidgetIgnore;

        this.map.fitMapToElements();

        return marker;

      case "LineString":
        shape = this.map.createShapeLine(geometry, {
          title: this.getShapeTitle(index),
          label: index + 1,
          draggable: true,
        });
        break;

      case "Polygon":
        shape = this.map.createShapePolygon(geometry, {
          title: this.getShapeTitle(index),
          label: index + 1,
          draggable: true,
        });
        break;
    }

    if (!shape) return;

    this.setIndexByShape(shape, index);

    shape.geolocationWidgetIgnore = true;
    this.map.dataLayers.get("default").shapeAdded(shape);
    delete shape.geolocationWidgetIgnore;

    this.map.fitMapToElements();

    return shape;
  }

  reorderSilently(newOrder) {
    this.map.dataLayers.get("default").markers.forEach((marker) => {
      const oldIndex = this.getIndexByMarker(marker);
      const newIndex = newOrder.indexOf(oldIndex);

      marker.geolocationWidgetIgnore = true;
      marker.update(null, {
        title: this.getShapeTitle(newIndex),
        label: newIndex + 1,
      });
      delete marker.geolocationWidgetIgnore;

      this.setIndexByMarker(marker, newIndex);
    });
  }

  updateShapeSilently(index, geometry, settings = null) {
    const shape = this.getShapeByIndex(index);

    if (!shape) return this.addShapeSilently(index, geometry);

    if (shape.geometry === geometry && !settings) {
      return;
    }

    shape.geolocationWidgetIgnore = true;
    shape.update(geometry, settings ?? {});
    delete shape.geolocationWidgetIgnore;

    this.map.fitMapToElements();

    return shape;
  }

  removeShapeSilently(index) {
    const shape = this.getShapeByIndex(index);

    shape.geolocationWidgetIgnore = true;
    shape.remove();

    this.map.fitMapToElements();
  }

  onShapeAdded(shape) {
    super.onShapeAdded(shape);

    if (shape.geolocationWidgetIgnore ?? false) return;

    this.subscriber?.geometryAdded(shape.geometry, this.getIndexByShape(shape) ?? 0);
  }

  onShapeClicked(shape, coordinates) {
    super.onShapeClicked(shape, coordinates);

    // Will trigger onShapeRemove and notify broker.
    shape.remove();
  }

  onShapeUpdated(shape) {
    super.onShapeUpdated(shape);

    if (shape.geolocationWidgetIgnore ?? false) return;

    this.subscriber?.geometryAltered(shape.geometry, this.getIndexByShape(shape));
  }

  onShapeRemove(shape) {
    super.onShapeRemove(shape);

    if (shape.geolocationWidgetIgnore ?? false) return;

    this.subscriber?.geometryRemoved(this.getIndexByShape(shape));
  }
}
