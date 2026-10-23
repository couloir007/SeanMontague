import { GeolocationShapePolygon } from "../../../js/Base/GeolocationShapePolygon.js";
import { GeolocationCoordinates } from "../../../js/Base/GeolocationCoordinates.js";

/**
 * @prop {Leaflet} map
 */
export class LeafletShapePolygon extends GeolocationShapePolygon {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    this.leafletShapes = [];

    this.addShape();
  }

  addShape() {
    const polygon = L.polygon(
      // The outer ring comes first, any further ring is a hole - which is what
      // Leaflet reads a list of rings as.
      this.geometry.coordinates.map((ring) => {
        return ring.map((position) => {
          return { lat: position[1], lng: position[0] };
        });
      }),
      {
        color: this.strokeColor,
        opacity: this.strokeOpacity,
        weight: this.strokeWidth,
        fillColor: this.fillColor,
        fillOpacity: this.fillOpacity,
        fill: this.fillOpacity > 0,
        editable: this.settings.editable ?? false,
      }
    );

    polygon.parent = this;

    if (this.title) {
      polygon.bindTooltip(Drupal.checkPlain(this.title));
    }

    polygon.on("click", (event) => {
      this.click(new GeolocationCoordinates(event.latlng.lat, event.latlng.lng));
    });

    polygon.addTo(this.map.leafletMap);

    this.leafletShapes.push(polygon);
  }

  update(geometry, settings) {
    super.update(geometry, settings);

    // Shapes left in the list would keep being counted - by the boundaries the
    // map fits itself to, among others - long after leaving the map.
    this.leafletShapes.forEach((leafletShape) => {
      leafletShape.remove();
    });
    this.leafletShapes = [];

    this.addShape();
  }

  remove() {
    this.leafletShapes.forEach((leafletShape) => {
      leafletShape.remove();
    });

    super.remove();
  }
}
