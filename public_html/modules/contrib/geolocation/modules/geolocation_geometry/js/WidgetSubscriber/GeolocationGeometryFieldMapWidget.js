import GeolocationFieldMapWidget from "../../../../js/WidgetSubscriber/GeolocationFieldMapWidget.js";

/**
 * @prop {GeolocationMapBase} map
 * @prop {GeolocationMapFeature} mapFeature
 */
export default class GeolocationGeometryFieldMapWidget extends GeolocationFieldMapWidget {
  /**
   * @return {Promise<GeolocationMapFeature>}
   */
  getMapFeature() {
    return super.getMapFeature();
  }

  onClick() {}

  /**
   * @param {GeolocationGeometry} geometry
   *   Shape.
   * @param {Number} index
   *   Index.
   * @param {String} source
   *   Source.
   */
  addGeometry(geometry, index, source) {
    super.addGeometry(geometry, index, source);

    switch (geometry.type) {
      case "Point":
      case "MultiPoint":
        if (this.broker.settings.cardinality > 0 && this.map.dataLayers.get("default").markers.length >= this.broker.settings.cardinality) {
          console.error(Drupal.t(`Maximum number of entries reached. Cardinality set to ${this.broker.settings.cardinality}`));
          return;
        }
        break;

      default:
        if (this.broker.settings.cardinality > 0 && this.map.dataLayers.get("default").shapes.length >= this.broker.settings.cardinality) {
          console.error(Drupal.t(`Maximum number of entries reached. Cardinality set to ${this.broker.settings.cardinality}`));
          return;
        }
    }
    this.getMapFeature().then((feature) => feature.addShapeSilently(index, geometry));
  }

  /**
   * @param {Number} index
   *   Index.
   * @param {String} source
   *   Source.
   */
  removeGeometry(index, source) {
    super.removeGeometry(index, source);

    this.getMapFeature().then((feature) => feature.removeShapeSilently(index));
  }

  /**
   * @param {GeolocationGeometry} geometry
   *   Shape.
   * @param {Number} index
   *   Index.
   * @param {String} source
   *   Source.
   */
  alterGeometry(geometry, index, source) {
    super.alterGeometry(geometry, index, source);

    this.getMapFeature().then((feature) => feature.updateShapeSilently(index, geometry));
  }

  geometryAltered(geometry, index) {
    this.broker.geometryAltered(geometry, index, this.id);
  }

  geometryAdded(geometry, index) {
    this.broker.geometryAdded(geometry, index, this.id);
  }

  geometryRemoved(index) {
    this.broker.geometryRemoved(index, this.id);
  }
}
