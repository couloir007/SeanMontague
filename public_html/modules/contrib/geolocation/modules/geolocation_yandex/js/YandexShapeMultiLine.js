import { GeolocationShapeMultiLine } from "../../../js/Base/GeolocationShapeMultiLine.js";
import { clampCoordinates } from "./YandexCoordinates.js";

/* global ymaps3 */

/**
 * @prop {Yandex} map
 */
export class YandexShapeMultiLine extends GeolocationShapeMultiLine {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    this.yandexShapes = [];

    const { YMapFeature } = ymaps3;

    const multiline = new YMapFeature({
      geometry: {
        type: "MultiLineString",
        coordinates: clampCoordinates(geometry.coordinates),
      },
      style: { stroke: [{ color: this.strokeColor, width: this.strokeWidth }] },
    });

    if (this.title) {
      multiline.update({
        properties: {
          label: this.title,
        },
      });
    }

    this.map.yandexMap.addChild(multiline);

    this.yandexShapes.push(multiline);
  }

  remove() {
    this.yandexShapes.forEach((yandexShape) => {
      this.map.yandexMap.removeChild(yandexShape);
    });

    super.remove();
  }
}
