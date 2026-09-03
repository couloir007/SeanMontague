import { GeolocationCircle } from "../../../js/Base/GeolocationCircle.js";

/**
 * @prop {GoogleMaps} map
 */
export class GoogleCircle extends GeolocationCircle {
  constructor(center, radius, map, settings = {}) {
    super(center, radius, map, settings);

    this.googleCircle = new google.maps.Circle({
      center,
      radius,
      fillColor: this.fillColor,
      fillOpacity: this.fillOpacity,
      strokeColor: this.strokeColor,
      strokeOpacity: this.strokeOpacity,
      strokeWeight: this.strokeWidth,
      clickable: false,
    });
    this.googleCircle.setMap(this.map.googleMap);
  }

  update(center, radius, settings) {
    super.update(center, radius, settings);

    this.googleCircle.setCenter(center);
    this.googleCircle.setRadius(radius);
    this.googleCircle.setOptions({
      fillColor: this.fillColor,
      fillOpacity: this.fillOpacity,
      strokeColor: this.strokeColor,
      strokeOpacity: this.strokeOpacity,
      strokeWeight: this.strokeWidth,
    });
  }

  remove() {
    this.googleCircle.setMap(null);

    super.remove();
  }
}
