import GeolocationGeometryTerraDrawConnectorBase from "./GeolocationGeometryTerraDrawConnectorBase.js";

/**
 * One drawn feature is one field item.
 */
export default class GeolocationGeometryTerraDrawConnector extends GeolocationGeometryTerraDrawConnectorBase {
  /**
   * @param {String} featureId
   *   Terra Draw feature ID.
   *
   * @return {Number|null}
   *   Widget index, or NULL for anything not tracked by this widget.
   */
  getIndexByFeatureId(featureId) {
    let returnValue = null;
    this.featureIds.forEach((currentId, index) => {
      if (currentId === featureId) {
        returnValue = index;
      }
    });

    return returnValue;
  }

  onFeatureDrawn(featureId) {
    if (this.settings.cardinality > 0 && this.featureIds.size >= this.settings.cardinality) {
      this.terraDraw.removeFeatures([featureId]);
      this.setDrawingMode("select");
      this.setMessage(Drupal.t("Maximum number of elements reached."));
      return;
    }

    const geometry = this.getGeometryByFeatureId(featureId);
    if (!geometry) {
      return;
    }

    const index = this.featureIds.size ? Math.max(...this.featureIds.keys()) + 1 : 0;
    this.featureIds.set(index, featureId);

    this.subscriber?.geometryAdded(geometry, index);

    if (this.settings.cardinality > 0 && this.featureIds.size >= this.settings.cardinality) {
      this.setDrawingMode("select");
    }
  }

  onFeatureUpdated(featureId) {
    const index = this.getIndexByFeatureId(featureId);
    if (index === null) {
      return;
    }

    const geometry = this.getGeometryByFeatureId(featureId);
    if (!geometry) {
      return;
    }

    this.subscriber?.geometryAltered(geometry, index);
  }

  onFeatureDeleted(featureId) {
    const index = this.getIndexByFeatureId(featureId);
    if (index === null) {
      return;
    }

    this.featureIds.delete(index);

    this.subscriber?.geometryRemoved(index);
  }

  getNextShapeIndex() {
    if (this.settings.cardinality !== -1 && this.settings.cardinality <= this.featureIds.size) {
      return false;
    }

    return this.featureIds.size ? Math.max(...this.featureIds.keys()) + 1 : 0;
  }

  takeOverRenderedGeometries(geometries) {
    geometries.forEach(({ index, geometry }) => this.addShapeSilently(index, geometry));
  }

  async addShapeSilently(index, geometry) {
    const terraDraw = await this.ready;
    if (!terraDraw) {
      return;
    }

    const added = this.addTerraDrawFeatures([geometry.coordinates])[0];
    if (!added?.valid) {
      return;
    }

    this.featureIds.set(Number(index), added.id);
  }

  async updateShapeSilently(index, geometry) {
    // Terra Draw has no API to alter a stored geometry, so it is replaced.
    await this.removeShapeSilently(index);
    await this.addShapeSilently(index, geometry);
  }

  async removeShapeSilently(index) {
    const terraDraw = await this.ready;
    if (!terraDraw) {
      return;
    }

    const featureId = this.featureIds.get(Number(index));
    if (!featureId) {
      return;
    }

    terraDraw.removeFeatures([featureId]);
    this.featureIds.delete(Number(index));
  }

  reorderSilently(newOrder) {
    const currentFeatureIds = new Map(this.featureIds);

    this.featureIds.clear();
    currentFeatureIds.forEach((featureId, oldIndex) => {
      const newIndex = newOrder.indexOf(oldIndex);
      this.featureIds.set(newIndex === -1 ? oldIndex : newIndex, featureId);
    });
  }
}
