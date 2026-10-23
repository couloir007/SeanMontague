import { GeolocationShapeMultiLine } from "../../../js/Base/GeolocationShapeMultiLine.js";
import { GoogleShapeTrait } from "./GoogleShapeTrait.js";
import { GeolocationCoordinates } from "../../../js/Base/GeolocationCoordinates.js";

/**
 * @prop {GoogleMaps} map
 *
 * @mixes GoogleShapeTrait
 */
export class GoogleShapeMultiLine extends GeolocationShapeMultiLine {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    Object.assign(this, GoogleShapeTrait);

    this.googleShapes = [];

    this.addShape();
  }

  addShape() {
    this.geometry.coordinates.forEach((lineCoordinates) => {
      const line = new google.maps.Polyline({
        path: lineCoordinates.map((position) => {
          return { lat: position[1], lng: position[0] };
        }),
        strokeColor: this.strokeColor,
        strokeOpacity: this.strokeOpacity,
        strokeWeight: this.strokeWidth,
      });

      if (this.title) {
        this.setTitle(line, this.title, this.map);
      }

      line.addListener("click", (event) => {
        this.click(new GeolocationCoordinates(event.latLng.lat(), event.latLng.lng()));
      });

      line.setMap(this.map.googleMap);

      this.googleShapes.push(line);
    });
  }

  update(geometry, settings) {
    super.update(geometry, settings);

    // Google shapes take no new geometry, so they are replaced - and a multi
    // geometry can have gained or lost parts along the way.
    this.detachShapes();
    this.addShape();
  }

  remove() {
    this.detachShapes();

    super.remove();
  }
}
