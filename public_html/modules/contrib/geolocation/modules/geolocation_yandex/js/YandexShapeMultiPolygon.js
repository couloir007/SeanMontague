import { GeolocationShapeMultiPolygon } from "../../../js/Base/GeolocationShapeMultiPolygon.js";

/* global ymaps3 */

/**
 * @prop {Yandex} map
 */
export class YandexShapeMultiPolygon extends GeolocationShapeMultiPolygon {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    /**
     * @type {YMapFeature[]}
     */
    this.yandexShapes = [];

    const { YMapFeature } = ymaps3;

    const polygons = [];
    geometry.polygons.forEach((polygonGeometry) => {
      const points = [];
      polygonGeometry.points.forEach((value) => {
        points.push([value.lng < 180 ? value.lng : 179.99, value.lat]);
      });

      polygons.push(points);
    });

    const multipolygon = new YMapFeature({
      geometry: {
        type: "MultiPolygon",
        coordinates: [polygons],
      },
      style: {
        stroke: [{ color: this.strokeColor, width: this.strokeWidth }],
        fill: this.fillColor,
        fillOpacity: this.fillOpacity,
      },
    });

    if (this.title) {
      multipolygon.update({
        properties: {
          label: this.title,
        },
      });
    }

    this.map.yandexMap.addChild(multipolygon);

    this.yandexShapes.push(multipolygon);
  }

  remove() {
    this.yandexShapes.forEach((yandexShape) => {
      this.map.yandexMap.removeChild(yandexShape);
    });

    super.remove();
  }
}
