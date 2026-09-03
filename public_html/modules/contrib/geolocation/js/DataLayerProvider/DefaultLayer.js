import GeolocationDataLayer from "./GeolocationDataLayer.js";

export default class DefaultLayer extends GeolocationDataLayer {
  async loadMarkers(selector) {
    selector = ".geolocation-location:not(.geolocation-map-layer .geolocation-location)";
    return super.loadMarkers(selector);
  }

  async loadShapes(selector) {
    selector = ".geolocation-geometry:not(.geolocation-map-layer .geolocation-geometry)";
    return super.loadShapes(selector);
  }

  markerAdded(marker) {
    super.markerAdded(marker);

    this.map.features.forEach((feature) => {
      try {
        feature.onMarkerAdded(marker);
      } catch (e) {
        console.error(e, `Feature  ${feature.constructor.name} failed onMarkerAdded: ${e.toString()}`);
      }
    });
  }

  markerUpdated(marker) {
    super.markerUpdated(marker);

    this.map.features.forEach((feature) => {
      try {
        feature.onMarkerUpdated(marker);
      } catch (e) {
        console.error(e, `Feature  ${feature.constructor.name} failed onMarkerUpdated: ${e.toString()}`);
      }
    });
  }

  markerRemoved(marker) {
    super.markerRemoved(marker);

    this.map.features.forEach((feature) => {
      try {
        feature.onMarkerRemove(marker);
      } catch (e) {
        console.error(e, `Feature  ${feature.constructor.name} failed onMarkerRemove: ${e.toString()}`);
      }
    });
  }

  markerClicked(marker) {
    super.markerClicked(marker);

    this.map.features.forEach((feature) => {
      try {
        feature.onMarkerClicked(marker);
      } catch (e) {
        console.error(e, `Feature  ${feature.constructor.name} failed onMarkerClicked: ${e.toString()}`);
      }
    });
  }

  shapeAdded(shape) {
    super.shapeAdded(shape);

    this.map.features.forEach((feature) => {
      try {
        feature.onShapeAdded(shape);
      } catch (e) {
        console.error(e, `Feature  ${feature.constructor.name} failed onShapeAdded: ${e.toString()}`);
      }
    });

    return shape;
  }

  shapeUpdated(shape) {
    super.shapeUpdated(shape);

    this.map.features.forEach((feature) => {
      try {
        feature.onShapeUpdated(shape);
      } catch (e) {
        console.error(e, `Feature  ${feature.constructor.name} failed onShapeUpdated: ${e.toString()}`);
      }
    });
  }

  shapeRemoved(shape) {
    super.shapeRemoved(shape);

    this.map.features.forEach((feature) => {
      try {
        feature.onShapeRemove(shape);
      } catch (e) {
        console.error(e, `Feature  ${feature.constructor.name} failed onShapeRemove: ${e.toString()}`);
      }
    });
  }

  shapeClicked(shape, coordinates) {
    super.shapeClicked(shape, coordinates);

    this.map.features.forEach((feature) => {
      try {
        feature.onShapeClicked(shape, coordinates);
      } catch (e) {
        console.error(e, `Feature  ${feature.constructor.name} failed onShapeClicked: ${e.toString()}`);
      }
    });
  }
}
