import { GeolocationShapeLine } from "../../../js/Base/GeolocationShapeLine.js";
import { clampCoordinates } from "./YandexCoordinates.js";

/* global ymaps3 */

/**
 * @prop {Yandex} map
 */
export class YandexShapeLine extends GeolocationShapeLine {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    this.yandexShapes = [];

    const { YMapFeature } = ymaps3;

    const line = new YMapFeature({
      geometry: {
        type: "LineString",
        coordinates: clampCoordinates(geometry.coordinates),
      },
      style: { stroke: [{ color: this.strokeColor, width: this.strokeWidth }] },
    });

    if (this.title) {
      line.update({
        properties: {
          label: this.title,
        },
      });
    }

    this.map.yandexMap.addChild(line);

    this.yandexShapes.push(line);
  }

  remove() {
    this.yandexShapes.forEach((yandexShape) => {
      this.map.yandexMap.removeChild(yandexShape);
    });

    super.remove();
  }
}
