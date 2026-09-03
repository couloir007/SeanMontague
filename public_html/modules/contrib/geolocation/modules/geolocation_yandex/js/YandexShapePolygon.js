import { GeolocationShapePolygon } from "../../../js/Base/GeolocationShapePolygon.js";

/* global ymaps3 */

/**
 * @prop {Yandex} map
 */
export class YandexShapePolygon extends GeolocationShapePolygon {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    this.yandexShapes = [];

    const { YMapFeature } = ymaps3;

    const coordinates = [];
    geometry.points.forEach((value) => {
      coordinates.push([value.lng < 180 ? value.lng : 179.99, value.lat]);
    });

    const polygon = new YMapFeature({
      geometry: {
        type: "Polygon",
        coordinates: [coordinates],
      },
      style: {
        stroke: [{ color: this.strokeColor, width: this.strokeWidth }],
        fill: this.fillColor,
        fillOpacity: this.fillOpacity,
      },
    });

    if (this.title) {
      polygon.update({
        properties: {
          label: this.title,
        },
      });
    }

    this.map.yandexMap.addChild(polygon);

    this.yandexShapes.push(polygon);
  }

  remove() {
    this.yandexShapes.forEach((yandexShape) => {
      yandexShape.remove();
    });

    super.remove();
  }
}
