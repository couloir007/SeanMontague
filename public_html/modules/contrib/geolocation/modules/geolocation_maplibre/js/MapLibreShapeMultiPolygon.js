import { GeolocationShapeMultiPolygon } from "../../../js/Base/GeolocationShapeMultiPolygon.js";

/**
 * @prop {MapLibre} map
 */
export class MapLibreShapeMultiPolygon extends GeolocationShapeMultiPolygon {
  constructor(geometry, settings, map) {
    super(geometry, settings, map);

    this.map.shapeStore.upsert(this);
  }

  /**
   * @return {Object}
   *   The shape as a GeoJSON geometry. The module stores geometries as
   *   GeoJSON already, which is what MapLibre draws, so there is nothing to
   *   convert.
   */
  getGeoJsonGeometry() {
    return this.geometry;
  }

  update(geometry, settings) {
    super.update(geometry, settings);

    this.map.shapeStore.upsert(this);
  }

  remove() {
    this.map.shapeStore.remove(this);

    super.remove();
  }
}
