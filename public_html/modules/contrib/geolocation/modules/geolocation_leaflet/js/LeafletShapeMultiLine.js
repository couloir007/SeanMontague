import { GeolocationShapeMultiLine } from "../../../js/Base/GeolocationShapeMultiLine.js";
import { GeolocationCoordinates } from "../../../js/Base/GeolocationCoordinates.js";

/**
 * @prop {Leaflet} map
 */
export class LeafletShapeMultiLine extends GeolocationShapeMultiLine {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    this.leafletShapes = [];
  }

  addShape() {
    this.geometry.coordinates.forEach((lineGeometry) => {
      const line = L.polyline(
        [
          lineGeometry.map((value) => {
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
        line.bindTooltip(this.title);
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
