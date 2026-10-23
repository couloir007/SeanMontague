import { GeolocationShapeMultiLine } from "../../../js/Base/GeolocationShapeMultiLine.js";

/**
 * @prop {Baidu} map
 */
export class BaiduShapeMultiLine extends GeolocationShapeMultiLine {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    this.baiduShapes = [];

    geometry.coordinates.forEach((lineCoordinates) => {
      const points = [];
      lineCoordinates.forEach((value) => {
        points.push(new BMapGL.Point(value[0], value[1]));
      });

      const line = new BMapGL.Polyline(points, {
        strokeColor: this.strokeColor,
        strokeOpacity: this.strokeOpacity,
        strokeWeight: this.strokeWidth,
      });

      if (this.title) {
        /** @type {BMapGL.InfoWindow} */
        const infoWindow = new BMapGL.InfoWindow(Drupal.checkPlain(this.title));
        line.addEventListener("mouseover", (e) => {
          this.map.baiduMap.openInfoWindow(infoWindow, e.point);
        });
        line.addEventListener("mouseout", () => {
          this.map.baiduMap.closeInfoWindow();
        });
      }

      this.map.baiduMap.addOverlay(line);

      this.baiduShapes.push(line);
    });
  }

  remove() {
    this.baiduShapes.forEach((baiduShape) => {
      baiduShape.remove();
    });

    super.remove();
  }
}
