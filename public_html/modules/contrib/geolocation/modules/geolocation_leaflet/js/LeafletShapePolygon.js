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
      [
        this.geometry.coordinates[0].map((value) => {
          return { lat: value[1], lng: value[0] };
        }),
      ],
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
      polygon.bindTooltip(this.title);
    }

    polygon.on("click", (event) => {
      this.click(new GeolocationCoordinates(event.latlng.lat, event.latlng.lng));
    });

    polygon.addTo(this.map.leafletMap);

    this.leafletShapes.push(polygon);
  }

  update(geometry, settings) {
    super.update(geometry, settings);

    this.leafletShapes.forEach((leafletShape) => {
      leafletShape.remove();
    });

    this.addShape();
  }

  remove() {
    this.leafletShapes.forEach((leafletShape) => {
      leafletShape.remove();
    });

    super.remove();
  }
}
