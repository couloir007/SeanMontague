import { GeolocationCoordinates } from "../../../js/Base/GeolocationCoordinates.js";

/**
 * Holds every shape of a map in one GeoJSON source.
 *
 * MapLibre has no shape objects to add and remove the way Leaflet or Google
 * Maps do - it draws sources through style layers. A source and a pair of
 * layers per shape would mean hundreds of both on a busy map, so all shapes
 * share one source and the two layers over it style themselves per feature.
 *
 * @prop {MapLibre} map
 * @prop {Map<Number, GeolocationShape|GeolocationCircle>} shapes
 */
export const SOURCE_ID = "geolocation-shapes";
export const FILL_LAYER_ID = "geolocation-shapes-fill";
export const LINE_LAYER_ID = "geolocation-shapes-line";

export class MapLibreShapeStore {
  /**
   * @param {MapLibre} map
   *   Map.
   */
  constructor(map) {
    this.map = map;
    this.shapes = new Map();
    this.nextFeatureId = 1;
  }

  /**
   * Add the shared source and its layers to the map.
   */
  attach() {
    const { maplibreMap } = this.map;

    maplibreMap.addSource(SOURCE_ID, {
      type: "geojson",
      data: this.toFeatureCollection(),
    });

    maplibreMap.addLayer({
      id: FILL_LAYER_ID,
      type: "fill",
      source: SOURCE_ID,
      filter: ["in", ["geometry-type"], ["literal", ["Polygon", "MultiPolygon"]]],
      paint: {
        "fill-color": ["get", "fillColor"],
        "fill-opacity": ["get", "fillOpacity"],
      },
    });

    // A line layer draws polygon rings as well, so one layer strokes
    // everything the fill layer cannot.
    maplibreMap.addLayer({
      id: LINE_LAYER_ID,
      type: "line",
      source: SOURCE_ID,
      paint: {
        "line-color": ["get", "strokeColor"],
        "line-opacity": ["get", "strokeOpacity"],
        "line-width": ["get", "strokeWidth"],
      },
    });

    [FILL_LAYER_ID, LINE_LAYER_ID].forEach((layerId) => {
      maplibreMap.on("click", layerId, (event) => {
        const shape = this.shapes.get(event.features?.[0]?.id);
        shape?.click?.(new GeolocationCoordinates(event.lngLat.lat, event.lngLat.lng));
      });

      maplibreMap.on("mouseenter", layerId, () => {
        maplibreMap.getCanvas().style.cursor = "pointer";
      });

      maplibreMap.on("mouseleave", layerId, () => {
        maplibreMap.getCanvas().style.cursor = "";
      });
    });
  }

  /**
   * Add a shape, or redraw one already held.
   *
   * @param {GeolocationShape|GeolocationCircle} shape
   *   Shape to draw.
   */
  upsert(shape) {
    if (typeof shape.maplibreFeatureId === "undefined") {
      // Shape IDs are per data layer, so they collide across layers - and a
      // GeoJSON feature ID has to be numeric to be addressable at all.
      shape.maplibreFeatureId = this.nextFeatureId;
      this.nextFeatureId += 1;
    }

    this.shapes.set(shape.maplibreFeatureId, shape);

    this.refresh();
  }

  /**
   * @param {GeolocationShape|GeolocationCircle} shape
   *   Shape to remove.
   */
  remove(shape) {
    this.shapes.delete(shape.maplibreFeatureId);

    this.refresh();
  }

  refresh() {
    this.map.maplibreMap?.getSource(SOURCE_ID)?.setData(this.toFeatureCollection());
  }

  /**
   * @return {Object}
   *   Every held shape as one GeoJSON FeatureCollection.
   */
  toFeatureCollection() {
    const features = [];

    this.shapes.forEach((shape, featureId) => {
      const geometry = shape.getGeoJsonGeometry();
      if (!geometry) {
        return;
      }

      features.push({
        type: "Feature",
        id: featureId,
        geometry,
        properties: {
          title: shape.title ?? "",
          strokeColor: shape.strokeColor ?? "#0000ff",
          strokeOpacity: shape.strokeOpacity ?? 1,
          strokeWidth: shape.strokeWidth ?? 2,
          fillColor: shape.fillColor ?? "#0000ff",
          fillOpacity: shape.fillOpacity ?? 0.2,
        },
      });
    });

    return {
      type: "FeatureCollection",
      features,
    };
  }
}
