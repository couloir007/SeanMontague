import { GeolocationShapeMultiPolygon } from "../../../js/Base/GeolocationShapeMultiPolygon.js";
import { GoogleShapeTrait } from "./GoogleShapeTrait.js";
import { GeolocationCoordinates } from "../../../js/Base/GeolocationCoordinates.js";

/**
 * @prop {GoogleMaps} map
 *
 * @mixes GoogleShapeTrait
 */
export class GoogleShapeMultiPolygon extends GeolocationShapeMultiPolygon {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    Object.assign(this, GoogleShapeTrait);

    this.googleShapes = [];
    this.geometry.coordinates.forEach((polygonGeometry) => {
      const polygon = new google.maps.Polygon({
        paths: [
          polygonGeometry.coordinates[0].map((value) => {
            return { lat: value[1], lng: value[0] };
          }),
        ],
        strokeColor: this.strokeColor,
        strokeOpacity: this.strokeOpacity,
        strokeWeight: this.strokeWidth,
        fillColor: this.fillColor,
        fillOpacity: this.fillOpacity,
      });
      if (this.title) {
        this.setTitle(polygon, this.title, this.map);
      }

      polygon.addListener("click", (event) => {
        this.click(new GeolocationCoordinates(event.latLng.lat(), event.latLng.lng()));
      });

      polygon.setMap(this.map.googleMap);

      this.googleShapes.push(polygon);
    });
  }

  remove() {
    this.googleShapes.forEach((googleShape) => {
      googleShape.setMap();
    });

    super.remove();
  }
}
