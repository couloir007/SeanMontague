import { GeolocationCircle } from "../../../js/Base/GeolocationCircle.js";

/**
 * @prop {Baidu} map
 */
export class BaiduCircle extends GeolocationCircle {
  constructor(center, radius, map, settings = {}) {
    super(center, radius, map, settings);

    this.baiduCircle = new BMapGL.Circle(new BMapGL.Point(center.lng, center.lat), radius, {
      fillColor: this.fillColor,
      fillOpacity: this.fillOpacity,
      strokeColor: this.strokeColor,
      strokeOpacity: this.strokeOpacity,
      strokeWeight: this.strokeWidth,
      enableClicking: false,
    });

    this.map.baiduMap.addOverlay(this.baiduCircle);
  }

  update(center, radius, settings) {
    super.update(center, radius, settings);

    this.baiduCircle.setCenter(new BMapGL.Point(center.lng, center.lat));
    this.baiduCircle.setRadius(radius);
    this.baiduCircle.setOptions({
      fillColor: this.fillColor,
      fillOpacity: this.fillOpacity,
      strokeColor: this.strokeColor,
      strokeOpacity: this.strokeOpacity,
      strokeWeight: this.strokeWidth,
    });
  }

  remove() {
    this.baiduCircle.remove();

    super.remove();
  }
}
