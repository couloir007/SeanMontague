import { GeolocationShapeMultiPolygon } from "../../../js/Base/GeolocationShapeMultiPolygon.js";

/**
 * @prop {Baidu} map
 */
export class BaiduShapeMultiPolygon extends GeolocationShapeMultiPolygon {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    this.baiduShapes = [];

    geometry.polygons.forEach((polygonGeometry) => {
      const points = [];
      polygonGeometry.points.forEach((value) => {
        points.push(new BMapGL.Point(value.lng, value.lat));
      });

      const polygon = new BMapGL.Polygon(points, {
        strokeColor: this.strokeColor,
        strokeOpacity: this.strokeOpacity,
        strokeWeight: this.strokeWidth,
        fillColor: this.fillColor,
        fillOpacity: this.fillOpacity,
      });
      if (this.title) {
        /** @type BMapGL.InfoWindow */
        const infoWindow = new BMapGL.InfoWindow(this.title);
        polygon.addEventListener("mouseover", (e) => {
          this.map.baiduMap.openInfoWindow(infoWindow, e.point);
        });
        polygon.addEventListener("mouseout", () => {
          this.map.baiduMap.closeInfoWindow();
        });
      }

      this.map.baiduMap.addOverlay(polygon);

      this.baiduShapes.push(polygon);
    });
  }

  remove() {
    this.baiduShapes.forEach((baiduShape) => {
      baiduShape.remove();
    });

    super.remove();
  }
}
