import { GeolocationShapeLine } from "../../../js/Base/GeolocationShapeLine.js";

/**
 * @prop {Baidu} map
 */
export class BaiduShapeLine extends GeolocationShapeLine {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    this.baiduShapes = [];

    const points = [];
    geometry.points.forEach((value) => {
      points.push(new BMapGL.Point(value.lng, value.lat));
    });

    const line = new BMapGL.Polyline(points, {
      strokeColor: this.strokeColor,
      strokeOpacity: this.strokeOpacity,
      strokeWeight: this.strokeWidth,
    });

    if (this.title) {
      /** @type BMapGL.InfoWindow */
      const infoWindow = new BMapGL.InfoWindow(this.title);
      line.addEventListener("mouseover", (e) => {
        this.map.baiduMap.openInfoWindow(infoWindow, e.point);
      });
      line.addEventListener("mouseout", () => {
        this.map.baiduMap.closeInfoWindow();
      });
    }

    this.map.baiduMap.addOverlay(line);

    this.baiduShapes.push(line);
  }

  remove() {
    this.baiduShapes.forEach((baiduShape) => {
      baiduShape.remove();
    });

    super.remove();
  }
}
