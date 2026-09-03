import { GeolocationGeometry } from "../../../js/Base/GeolocationGeometry.js";

/**
 * Google Shape support functions.
 *
 * @mixin
 */
export const GoogleShapeTrait = {
  /**
   * @param {google.maps.MVCObject} shape
   * @param {String} title
   * @param {GoogleMaps} map
   */
  setTitle(shape, title, map) {
    const infoWindow = new google.maps.InfoWindow({
      disableAutoPan: true,
      headerDisabled: true,
    });
    google.maps.event.addListener(shape, "mouseover", (e) => {
      infoWindow.setPosition(e.latLng);
      infoWindow.setContent(title);
      infoWindow.open({
        map: map.googleMap,
      });
    });
    google.maps.event.addListener(shape, "mouseout", () => {
      infoWindow.close();
    });
  },

  /**
   *
   * @param {google.maps.Polyline|google.maps.Polygon|google.maps.Rectangle} googleShape
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
      this.geometry = new GeolocationGeometry("Polyline", coordinates);
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

    this.googleShapes.push(googleShape);
  },
};
