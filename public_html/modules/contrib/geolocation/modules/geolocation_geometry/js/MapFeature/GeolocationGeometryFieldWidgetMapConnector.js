import GeolocationFieldWidgetMapConnector from "../../../../js/MapFeature/GeolocationFieldWidgetMapConnector.js";
import { GeolocationGeometry } from "../../../../js/Base/GeolocationGeometry.js";

export default class GeolocationGeometryFieldWidgetMapConnector extends GeolocationFieldWidgetMapConnector {
  /**
   * @param {GeolocationShape} shape
   *   Shape.
   *
   * @return {Number}
   *   Index.
   */
  getIndexByShape(shape) {
    return Number(shape.wrapper.dataset.geolocationWidgetIndex ?? 0);
  }

  /**
   * @param {GeolocationShape} shape
   *   Shape.
   * @param {Number|false} index
   *   Index, or FALSE to clear it.
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
   * @param {Number} index
   *   Index.
   *
   * @return {String}
   *   Title.
   */
  getShapeTitle(index) {
    return `${index + 1}`;
  }

  /**
   * Render a geometry the widget holds, without reporting it back to it.
   *
   * @param {Number} index
   *   Index.
   * @param {GeolocationGeometry} geometry
   *   Geometry.
   */
  addShapeSilently(index, geometry) {}

  /**
   * Alter a rendered geometry, without reporting it back to the widget.
   *
   * @param {Number} index
   *   Index.
   * @param {GeolocationGeometry} geometry
   *   Geometry.
   * @param {GeolocationShapeSettings|null} settings
   *   Settings.
   */
  updateShapeSilently(index, geometry, settings = null) {}

  /**
   * Drop a rendered geometry, without reporting it back to the widget.
   *
   * @param {Number} index
   *   Index.
   */
  removeShapeSilently(index) {}

  reorderSilently(newOrder) {
    super.reorderSilently(newOrder);

    this.map.dataLayers.get("default").shapes.forEach((shape) => {
      const oldIndex = this.getIndexByShape(shape);
      const newIndex = newOrder.indexOf(oldIndex);

      shape.geolocationWidgetIgnore = true;
      shape.update(null, {
        title: this.getShapeTitle(newIndex),
        label: newIndex + 1,
      });
      delete shape.geolocationWidgetIgnore;

      this.setIndexByShape(shape, newIndex);
    });
  }

  /**
   * Geometries are drawn, not placed by clicking, so the marker the plain field
   * widget would add here has no meaning for a geometry field.
   *
   * @param {GeolocationCoordinates} coordinates
   *   Coordinates.
   */
  onClick(coordinates) {}

  /**
   * @return {int|false} index
   */
  getNextShapeIndex() {
    if (this.settings.cardinality <= this.map.dataLayers.get("default").shapes.length && this.settings.cardinality !== -1) {
      return false;
    }

    let index = 0;
    this.map.dataLayers.get("default").shapes.forEach((shape) => {
      const shapeIndex = this.getIndexByShape(shape) ?? 0;
      if (shapeIndex >= index) {
        index = shapeIndex + 1;
      }
    });

    return index;
  }

  /**
   * @param {GeolocationGeocodedResult} result
   *   Geocoded result.
   */
  onGeocodedResult(result) {
    if (!this.settings.set_result_on_geocode) {
      return;
    }

    if (this.settings.field_type === "geolocation_geometry_point" || this.settings.field_type === "geolocation_geometry_multipoint") {
      return super.onGeocodedResult(result);
    }

    if (this.settings.field_type === "geolocation_geometry_polygon" && result.boundaries) {
      const geometry = GeolocationGeometry.constructByBoundaries(result.boundaries);
      if (this.settings.cardinality === 1) {
        this.updateShapeSilently(0, geometry);
        this.subscriber?.geometryAltered(geometry, 0);
      } else {
        const newIndex = this.getNextShapeIndex();
        if (newIndex === false) {
          return;
        }

        this.addShapeSilently(newIndex, geometry);
        this.subscriber?.geometryAdded(geometry, newIndex);
      }
    }
  }

  /**
   * @param {GeolocationMapMarker} marker
   *   Marker.
   *
   * @return {GeolocationGeometry}
   *   Point geometry for that marker.
   */
  getGeometryByMarker(marker) {
    return new GeolocationGeometry("Point", [marker.coordinates.lng, marker.coordinates.lat]);
  }

  /**
   * A marker on a geometry widget is a Point geometry, so marker changes are
   * reported as geometry changes. These deliberately skip super(), whose
   * coordinate notification the widget's GeoJSON field cannot consume - and
   * which would make it request field items for coordinates it never stores.
   *
   * @param {GeolocationMapMarker} marker
   *   Marker.
   */
  onMarkerAdded(marker) {
    if (marker.geolocationWidgetIgnore ?? false) return;

    this.subscriber?.geometryAdded(this.getGeometryByMarker(marker), this.getIndexByMarker(marker));
  }

  /**
   * @param {GeolocationMapMarker} marker
   *   Marker.
   */
  onMarkerUpdated(marker) {
    if (marker.geolocationWidgetIgnore ?? false) return;

    this.subscriber?.geometryAltered(this.getGeometryByMarker(marker), this.getIndexByMarker(marker));
  }

  /**
   * @param {GeolocationMapMarker} marker
   *   Marker.
   */
  onMarkerRemove(marker) {
    if (marker.geolocationWidgetIgnore ?? false) return;

    this.subscriber?.geometryRemoved(this.getIndexByMarker(marker));
  }

  onShapeAdded(shape) {
    super.onShapeAdded(shape);

    if (shape.geolocationWidgetIgnore ?? false) return;

    this.subscriber?.geometryAdded(shape.geometry, this.getIndexByShape(shape));
  }

  onShapeClicked(shape, coordinates) {
    super.onShapeClicked(shape, coordinates);

    // Will trigger onShapeRemove and notify the widget.
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
