import { GeolocationShapeMultiLine } from "../../../js/Base/GeolocationShapeMultiLine.js";
import { GeolocationCoordinates } from "../../../js/Base/GeolocationCoordinates.js";

/**
 * @prop {Leaflet} map
 */
export class LeafletShapeMultiLine extends GeolocationShapeMultiLine {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    this.leafletShapes = [];

    this.addShape();
  }

  addShape() {
    this.geometry.coordinates.forEach((lineCoordinates) => {
      const line = L.polyline(
        [
          lineCoordinates.map((value) => {
            return { lat: value[1], lng: value[0] };
          }),
        ],
        {
          color: this.strokeColor,
          opacity: this.strokeOpacity,
          weight: this.strokeWidth,
        }
      );

      if (this.title) {
        line.bindTooltip(Drupal.checkPlain(this.title));
      }

      line.on("click", (event) => {
        this.click(new GeolocationCoordinates(event.latlng.lat, event.latlng.lng));
      });

      line.addTo(this.map.leafletMap);

      this.leafletShapes.push(line);
    });
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
