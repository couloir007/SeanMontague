import { WidgetSubscriberBase } from "./WidgetSubscriberBase.js";

/**
 * @prop {GeolocationMapBase} map
 * @prop {GeolocationFieldWidgetMapConnector} mapFeature
 */
export default class GeolocationFieldMapWidget extends WidgetSubscriberBase {
  constructor(broker, settings) {
    super(broker, settings);

    this.mapFeature = null;
    this.mapFeaturePromise = new Promise((resolve) => {
      Drupal.geolocation.maps.getMap(settings.mapId).then((map) => {
        this.map = map;

        this.map.features.forEach((feature, id) => {
          if (this.settings.feature_id !== id) {
            return;
          }
          this.mapFeature = feature;
          if (typeof this.mapFeature.setWidgetSubscriber === "function") {
            this.mapFeature.setWidgetSubscriber(this);
          }
          resolve(feature);
        });
      });
    });
  }

  /**
   * @return {Promise<GeolocationFieldWidgetMapConnector>}
   *   Feature.
   */
  getMapFeature() {
    return new Promise((resolve) => {
      if (this.mapFeature) {
        resolve(this.mapFeature);
      } else {
        this.mapFeaturePromise.then((feature) => {
          resolve(feature);
        });
      }
    });
  }

  reorder(newOrder, source) {
    super.reorder(newOrder, source);

    this.getMapFeature().then((feature) => feature.reorderSilently(newOrder));
  }

  addCoordinates(coordinates, index, source) {
    super.addCoordinates(coordinates, index, source);

    if (this.broker.settings.cardinality > 0 && this.map.dataLayers.get("default").markers.length >= this.broker.settings.cardinality) {
      console.error(Drupal.t(`Maximum number of entries reached. Cardinality set to ${this.broker.settings.cardinality}`));
      return;
    }

    this.getMapFeature().then((feature) => feature.addMarkerSilently(index, coordinates));
  }

  removeCoordinates(index, source) {
    super.removeCoordinates(index, source);

    this.getMapFeature().then((feature) => feature.removeMarkerSilently(index));
  }

  alterCoordinates(coordinates, index, source) {
    super.alterCoordinates(coordinates, index, source);

    this.getMapFeature().then((feature) => feature.updateMarkerSilently(index, coordinates));
  }

  coordinatesAltered(coordinates, index) {
    this.broker.coordinatesAltered(coordinates, index, this.id);
  }

  coordinatesAdded(coordinates, index) {
    this.broker.coordinatesAdded(coordinates, index, this.id);
  }

  coordinatesRemoved(index) {
    this.broker.coordinatesRemoved(index, this.id);
  }
}
