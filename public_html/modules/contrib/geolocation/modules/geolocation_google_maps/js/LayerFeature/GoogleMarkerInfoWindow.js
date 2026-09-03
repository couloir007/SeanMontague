import { GoogleLayerFeature } from "./GoogleLayerFeature.js";

/**
 * @typedef {Object} MarkerInfoWindowSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {Boolean} info_auto_display
 * @prop {Boolean} disable_auto_pan
 * @prop {Boolean} info_window_solitary
 * @prop {int} max_width
 */

/**
 * @typedef {Object} GoogleInfoWindow
 * @prop {Function} open
 * @prop {Function} close
 */

/**
 * @prop {MarkerInfoWindowSettings} settings
 * @prop {GoogleInfoWindow} GeolocationGoogleMap.infoWindow
 * @prop {function({}):GoogleInfoWindow} GeolocationGoogleMap.InfoWindow
 */
export default class GoogleMarkerInfoWindow extends GoogleLayerFeature {
  onMarkerClicked(marker) {
    super.onMarkerClicked(marker);

    if (this.settings.info_window_solitary) {
      this.layer.map.dataLayers.get("default").markers.forEach((currentMarker) => {
        if (currentMarker.infoWindow) {
          currentMarker.infoWindow.close();
        }
      });
    }

    if (marker.infoWindow) {
      if (marker.infoWindowOpened) {
        marker.infoWindow.close();
        marker.infoWindowOpened = false;
      } else {
        marker.infoWindow.open({
          anchor: marker.googleMarker,
          map: this.layer.map.googleMap,
          shouldFocus: true,
        });
        marker.infoWindowOpened = true;
      }
    }
  }

  onMarkerAdded(marker) {
    super.onMarkerAdded(marker);

    marker.infoWindowOpened = false;

    // Set the info popup text.
    marker.infoWindow = new google.maps.InfoWindow({
      content: marker.getContent(),
      disableAutoPan: this.settings.disable_auto_pan,
      maxWidth: this.settings.max_width ?? undefined,
    });

    if (marker.title) {
      marker.infoWindow.setHeaderContent(marker.title);
    }

    if (this.settings.info_auto_display) {
      marker.infoWindow.open({
        anchor: marker.googleMarker,
        map: this.layer.map.googleMap,
        shouldFocus: false,
      });
      marker.infoWindowOpened = true;
    }
  }

  /**
   * @param {GeolocationShape}        shape
   * @param {google.maps.InfoWindow}  shape.infoWindow
   * @param {boolean}                 shape.infoWindowOpened
   *
   * @param {GeolocationCoordinates}  coordinates
   */
  onShapeClicked(shape, coordinates) {
    super.onShapeClicked(shape, coordinates);

    if (this.settings.info_window_solitary) {
      this.layer.map.dataLayers.get("default").shapes.forEach((currentShape) => {
        if (currentShape.infoWindow) {
          currentShape.infoWindow.close();
        }
      });
    }

    if (shape.infoWindow) {
      if (shape.infoWindowOpened) {
        shape.infoWindow.close();
        shape.infoWindowOpened = false;
      } else {
        shape.infoWindow.setPosition({ lat: coordinates.lat, lng: coordinates.lng });
        shape.infoWindow.open({
          map: this.layer.map.googleMap,
          shouldFocus: true,
        });
        shape.infoWindowOpened = true;
      }
    }
  }

  onShapeAdded(shape) {
    super.onShapeAdded(shape);

    shape.infoWindowOpened = false;

    // Set the info popup text.
    shape.infoWindow = new google.maps.InfoWindow({
      content: shape.getContent(),
      disableAutoPan: this.settings.disable_auto_pan,
      maxWidth: this.settings.max_width ?? undefined,
    });
    shape.infoWindow.addListener("close", () => {
      shape.infoWindowOpened = false;
    });

    if (shape.title) {
      shape.infoWindow.setHeaderContent(shape.title);
    }

    if (this.settings.info_auto_display) {
      shape.infoWindow.open({
        map: this.layer.map.googleMap,
        shouldFocus: false,
      });
      shape.infoWindowOpened = true;
    }
  }
}
