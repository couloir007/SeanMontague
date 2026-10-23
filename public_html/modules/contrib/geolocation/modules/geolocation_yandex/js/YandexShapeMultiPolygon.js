import { GeolocationShapeMultiPolygon } from "../../../js/Base/GeolocationShapeMultiPolygon.js";
import { clampCoordinates } from "./YandexCoordinates.js";

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

    const multipolygon = new YMapFeature({
      geometry: {
        type: "MultiPolygon",
        coordinates: clampCoordinates(geometry.coordinates),
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
