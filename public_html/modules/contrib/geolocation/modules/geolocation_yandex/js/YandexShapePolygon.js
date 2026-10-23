import { GeolocationShapePolygon } from "../../../js/Base/GeolocationShapePolygon.js";
import { clampCoordinates } from "./YandexCoordinates.js";

/* global ymaps3 */

/**
 * @prop {Yandex} map
 */
export class YandexShapePolygon extends GeolocationShapePolygon {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    this.yandexShapes = [];

    const { YMapFeature } = ymaps3;

    const polygon = new YMapFeature({
      geometry: {
        type: "Polygon",
        coordinates: clampCoordinates(geometry.coordinates),
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
      this.map.yandexMap.removeChild(yandexShape);
    });

    super.remove();
  }
}
