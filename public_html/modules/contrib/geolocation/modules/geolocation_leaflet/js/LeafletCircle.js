import { GeolocationCircle } from "../../../js/Base/GeolocationCircle.js";

/**
 * @prop {Leaflet} map
 */
export class LeafletCircle extends GeolocationCircle {
  constructor(center, radius, map, settings = {}) {
    super(center, radius, map, settings);

    this.leafletCircle = L.circle(this.center, this.radius, {
      interactive: false,
      color: this.strokeColor,
      opacity: this.strokeOpacity,
      fillColor: this.fillColor,
      fillOpacity: this.fillOpacity,
    });
    this.leafletCircle.addTo(this.map.leafletMap);
  }

  update(center, radius, settings) {
    super.update(center, radius, settings);

    this.leafletCircle.setLatLng(this.center);
    this.leafletCircle.setRadius(this.radius);

    this.leafletCircle.setStyle({
      color: this.strokeColor,
      opacity: this.strokeOpacity,
      fillColor: this.fillColor,
      fillOpacity: this.fillOpacity,
    });
  }

  remove() {
    this.leafletCircle.remove();

    super.remove();
  }
}
