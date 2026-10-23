import GeolocationGeometryTerraDrawConnectorBase from "./GeolocationGeometryTerraDrawConnectorBase.js";
import { GeolocationGeometry } from "../../../../js/Base/GeolocationGeometry.js";

/**
 * Everything drawn on this map together makes up the geometry of one field
 * item, so every change re-assembles the whole multi geometry rather than
 * reporting the drawn feature on its own.
 *
 * Terra Draw stores single geometries only, so the parts are held individually
 * and joined on the way out, split on the way in.
 *
 * This map serves one field item and nothing else, so the index the widget
 * broker passes around is always that item's own.
 */
export default class GeolocationGeometryTerraDrawMultiConnector extends GeolocationGeometryTerraDrawConnectorBase {
  /**
   * @return {Object[]}
   *   Terra Draw features making up this item's geometry.
   */
  getParts() {
    if (!this.terraDraw) {
      return [];
    }

    return this.terraDraw.getSnapshot().filter((feature) => feature.properties.mode === this.mode);
  }

  /**
   * @return {GeolocationGeometry|null}
   *   The multi geometry all parts add up to, NULL while nothing is drawn.
   */
  getGeometry() {
    const parts = this.getParts();

    if (!parts.length) {
      return null;
    }

    // The coordinates of a multi geometry are the coordinates of its parts, one
    // nesting level deeper - which is exactly what collecting them gives.
    return new GeolocationGeometry(
      `Multi${parts[0].geometry.type}`,
      parts.map((part) => part.geometry.coordinates)
    );
  }

  onFeatureDrawn(featureId) {
    const geometry = this.getGeometry();

    if (!geometry) {
      this.subscriber?.geometryRemoved(0);
      return;
    }

    this.subscriber?.geometryAltered(geometry, 0);
  }

  onFeatureUpdated(featureId) {
    // Dragging one vertex updates the selection and midpoint features Terra
    // Draw keeps alongside it, none of which change the stored geometry.
    if (this.terraDraw?.getSnapshotFeature(featureId)?.properties?.mode !== this.mode) {
      return;
    }

    const geometry = this.getGeometry();

    if (!geometry) {
      this.subscriber?.geometryRemoved(0);
      return;
    }

    this.subscriber?.geometryAltered(geometry, 0);
  }

  onFeatureDeleted(featureId) {
    const geometry = this.getGeometry();

    if (!geometry) {
      this.subscriber?.geometryRemoved(0);
      return;
    }

    this.subscriber?.geometryAltered(geometry, 0);
  }

  getNextShapeIndex() {
    return 0;
  }

  /**
   * @param {GeolocationGeometry} geometry
   *   Single or multi geometry.
   *
   * @return {Array}
   *   Coordinates of each part the geometry is made of.
   */
  getPartCoordinates(geometry) {
    return geometry.type.startsWith("Multi") ? geometry.coordinates : [geometry.coordinates];
  }

  /**
   * Drop everything currently drawn.
   */
  clearParts() {
    const ids = this.getParts().map((part) => part.id);

    if (ids.length) {
      this.terraDraw.removeFeatures(ids);
    }
  }

  takeOverRenderedGeometries(geometries) {
    // A MultiPoint arrives as one marker per position, so every element belongs
    // to this one item's geometry and all their parts go in as one store write.
    this.addTerraDrawFeatures(geometries.flatMap(({ geometry }) => this.getPartCoordinates(geometry)));
  }

  /**
   * Draw exactly the parts of a geometry, replacing whatever is on the map.
   *
   * @param {GeolocationGeometry|null} geometry
   *   Geometry, or NULL to leave the map empty.
   */
  async replaceParts(geometry = null) {
    const terraDraw = await this.ready;
    if (!terraDraw) {
      return;
    }

    this.clearParts();

    if (geometry) {
      this.addTerraDrawFeatures(this.getPartCoordinates(geometry));
    }
  }

  async addShapeSilently(index, geometry) {
    await this.replaceParts(geometry);
  }

  async updateShapeSilently(index, geometry) {
    await this.replaceParts(geometry);
  }

  async removeShapeSilently(index) {
    await this.replaceParts();
  }

  reorderSilently(newOrder) {
    // Reordering moves whole items, map and input together, so there is nothing
    // to re-index here.
  }
}
