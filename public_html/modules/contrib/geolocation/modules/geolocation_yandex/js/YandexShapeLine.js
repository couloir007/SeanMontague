import { GeolocationShapeLine } from "../../../js/Base/GeolocationShapeLine.js";

/* global ymaps3 */

/**
 * @prop {Yandex} map
 */
export class YandexShapeLine extends GeolocationShapeLine {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    this.yandexShapes = [];

    const { YMapFeature } = ymaps3;

    const points = [];
    geometry.points.forEach((value) => {
      points.push([value.lng < 180 ? value.lng : 179.99, value.lat]);
    });

    const line = new YMapFeature({
      geometry: {
        type: "LineString",
        coordinates: points,
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
      yandexShape.remove();
    });

    super.remove();
  }
}
