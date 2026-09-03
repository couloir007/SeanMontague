import { GeolocationShapeMultiLine } from "../../../js/Base/GeolocationShapeMultiLine.js";

/* global ymaps3 */

/**
 * @prop {Yandex} map
 */
export class YandexShapeMultiLine extends GeolocationShapeMultiLine {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    this.yandexShapes = [];

    const { YMapFeature } = ymaps3;

    const lines = [];
    geometry.lines.forEach((lineGeometry) => {
      const points = [];
      lineGeometry.points.forEach((value) => {
        points.push([value.lng < 180 ? value.lng : 179.99, value.lat]);
      });

      lines.push(points);
    });

    const multiline = new YMapFeature({
      geometry: {
        type: "MultiLineString",
        coordinates: [lines],
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
      yandexShape.remove();
    });

    super.remove();
  }
}
