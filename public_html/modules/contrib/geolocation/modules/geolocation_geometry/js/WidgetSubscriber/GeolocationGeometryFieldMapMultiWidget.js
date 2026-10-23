import GeolocationGeometryFieldMapWidget from "./GeolocationGeometryFieldMapWidget.js";

/**
 * The map of one multi geometry field item.
 *
 * Everything drawn on that map forms the item's single geometry, so the map
 * holds no items to tell apart and the index the broker passes around is always
 * this item's own.
 */
export default class GeolocationGeometryFieldMapMultiWidget extends GeolocationGeometryFieldMapWidget {
  addGeometry(geometry, index, source) {
    // Cardinality limits items, not the parts one item is drawn from, so the
    // element count the field wide subscriber checks means nothing here.
    this.getMapFeature().then((feature) => feature.addShapeSilently(index, geometry));
  }
}
