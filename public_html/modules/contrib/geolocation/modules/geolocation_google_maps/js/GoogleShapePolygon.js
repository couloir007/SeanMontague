import { GeolocationShapePolygon } from "../../../js/Base/GeolocationShapePolygon.js";
import { GoogleShapeTrait } from "./GoogleShapeTrait.js";
import { GeolocationCoordinates } from "../../../js/Base/GeolocationCoordinates.js";

/**
 * @prop {GoogleMaps} map
 * @prop {Array} googleShapes
 *
 * @mixes GoogleShapeTrait
 */
export class GoogleShapePolygon extends GeolocationShapePolygon {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    Object.assign(this, GoogleShapeTrait);

    this.googleShapes = [];

    this.addShape();
  }

  addShape() {
    const polygon = new google.maps.Polygon({
      // The outer ring comes first, any further ring is a hole - which is what
      // Google reads a list of paths as.
      paths: this.geometry.coordinates.map((ring) => {
        return ring.map((position) => {
          return { lat: position[1], lng: position[0] };
        });
      }),
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
  }

  update(geometry, settings) {
    super.update(geometry, settings);

    // Google shapes take no new geometry, so they are replaced.
    this.detachShapes();
    this.addShape();
  }

  remove() {
    this.detachShapes();

    super.remove();
  }
}
