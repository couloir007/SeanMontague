import { GeolocationGeometry } from "../../../js/Base/GeolocationGeometry.js";

/**
 * Google Shape support functions.
 *
 * @mixin
 */
export const GoogleShapeTrait = {
  /**
   * @param {google.maps.MVCObject} shape
   *   Shape to attach the title to.
   * @param {String} title
   *   Title shown in the info window on hover.
   * @param {GoogleMaps} map
   *   Map the shape belongs to.
   */
  setTitle(shape, title, map) {
    const infoWindow = new google.maps.InfoWindow({
      disableAutoPan: true,
      headerDisabled: true,
    });
    google.maps.event.addListener(shape, "mouseover", (e) => {
      infoWindow.setPosition(e.latLng);
      infoWindow.setContent(Drupal.checkPlain(title));
      infoWindow.open({
        map: map.googleMap,
      });
    });
    google.maps.event.addListener(shape, "mouseout", () => {
      infoWindow.close();
    });
  },

  /**
   * Take every Google shape off the map and forget it.
   *
   * Shapes left in the list would keep being counted - by the boundaries the
   * map fits itself to, among others - long after leaving the map.
   */
  detachShapes() {
    this.googleShapes.forEach((googleShape) => {
      googleShape.setMap(null);
    });

    this.googleShapes = [];
  },

  /**
   * @param {google.maps.Polyline|google.maps.Polygon|google.maps.Rectangle} googleShape
   *   Google shape to read the geometry from.
   */
  updateByGoogleShape(googleShape) {
    if (googleShape instanceof google.maps.Rectangle) {
      this.geometry = new GeolocationGeometry("Polygon", [
        [
          [googleShape.getBounds().getSouthWest().lng(), googleShape.getBounds().getNorthEast().lat()],
          [googleShape.getBounds().getSouthWest().lng(), googleShape.getBounds().getSouthWest().lat()],
          [googleShape.getBounds().getNorthEast().lng(), googleShape.getBounds().getSouthWest().lat()],
          [googleShape.getBounds().getNorthEast().lng(), googleShape.getBounds().getNorthEast().lat()],
          [googleShape.getBounds().getSouthWest().lng(), googleShape.getBounds().getNorthEast().lat()],
        ],
      ]);
    } else if (googleShape instanceof google.maps.Polyline) {
      const coordinates = [];
      googleShape.getPath().forEach((coordinate) => {
        coordinates.push([coordinate.lng(), coordinate.lat()]);
      });
      this.geometry = new GeolocationGeometry("LineString", coordinates);
    } else if (googleShape instanceof google.maps.Polygon) {
      const coordinates = [];
      googleShape.getPaths().forEach((path) => {
        let first = null;
        path.forEach((coordinate) => {
          if (first === null) {
            first = coordinate;
          }
          coordinates.push([coordinate.lng(), coordinate.lat()]);
        });
        coordinates.push([first.lng(), first.lat()]);
      });
      this.geometry = new GeolocationGeometry("Polygon", [coordinates]);
    } else {
      return false;
    }

    this.detachShapes();
    this.googleShapes.push(googleShape);
  },
};
