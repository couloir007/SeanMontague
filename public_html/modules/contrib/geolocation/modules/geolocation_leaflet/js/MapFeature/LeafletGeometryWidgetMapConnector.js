import { GeolocationCoordinates } from "../../../../js/Base/GeolocationCoordinates.js";
import GeolocationGeometryFieldWidgetMapConnector from "../../../geolocation_geometry/js/MapFeature/GeolocationGeometryFieldWidgetMapConnector.js";

/**
 * Leaflet Draw based geometry widget connector.
 *
 * @prop {L.Map} map
 * @prop {L.FeatureGroup} drawnItemsGroup
 */
export default class LeafletGeometryWidgetMapConnector extends GeolocationGeometryFieldWidgetMapConnector {
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
      /** @param {DrawEvents.Created} event Draw event for the newly created shape. */ (event) => {
        let currentElementCount = 0;
        this.drawnItemsGroup.eachLayer(() => {
          currentElementCount++;
        });

        let newIndex = 0;
        if (this.settings.cardinality > currentElementCount || this.settings.cardinality === -1) {
          this.drawnItemsGroup.eachLayer(
            /**
             * @param {Layer} element
             *   Layer already present in the drawn items group.
             * @param {int} element.widgetIndex
             *   Widget index the layer is associated with.
             */
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
            const { geometry } = event.layer.toGeoJSON();
            const marker = this.map.createMarker(new GeolocationCoordinates(geometry.coordinates[1], geometry.coordinates[0]));
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
       *   Draw event for the edited vertex.
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
        const { poly } = event;

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
       *   Draw event for the deleted layers.
       */
      (event) => {
        event.layers.eachLayer(
          /**
           * @param {Layer} layer
           *   Layer that was deleted.
           * @param {int} layer.widgetIndex
           *   Widget index the layer is associated with.
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
}
