import { GeolocationShapeLine } from "../../../js/Base/GeolocationShapeLine.js";
import { GoogleShapeTrait } from "./GoogleShapeTrait.js";
import { GeolocationCoordinates } from "../../../js/Base/GeolocationCoordinates.js";

/**
 * @prop {GoogleMaps} map
 *
 * @mixes GoogleShapeTrait
 */
export class GoogleShapeLine extends GeolocationShapeLine {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    Object.assign(this, GoogleShapeTrait);

    this.googleShapes = [];

    const line = new google.maps.Polyline({
      path: [
        geometry.coordinates.map((value) => {
          return { lat: value[1], lng: value[0] };
        }),
      ],
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
  }

  remove() {
    this.googleShapes.forEach((googleShape) => {
      googleShape.setMap();
    });

    super.remove();
  }
}
