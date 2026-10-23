import { GeolocationCircle } from "../../../js/Base/GeolocationCircle.js";

/**
 * @prop {MapLibre} map
 */
/**
 * Positions used to approximate a circle.
 */
const SEGMENTS = 64;

export class MapLibreCircle extends GeolocationCircle {
  constructor(center, radius, map, settings = {}) {
    super(center, radius, map, settings);

    this.map.shapeStore.upsert(this);
  }

  /**
   * @return {Object|null}
   *   The circle as a GeoJSON polygon. MapLibre draws no circle of its own at
   *   a radius in meters - its circle layer sizes in pixels - so the ring is
   *   walked out from the center instead.
   */
  getGeoJsonGeometry() {
    if (!this.center || !this.radius) {
      return null;
    }

    // Meters per degree of latitude; longitude narrows towards the poles.
    const latitudeDegree = this.radius / 111320;
    const longitudeDegree = latitudeDegree / Math.cos((this.center.lat * Math.PI) / 180);

    const ring = [];
    for (let i = 0; i <= SEGMENTS; i++) {
      const angle = (i / SEGMENTS) * 2 * Math.PI;
      ring.push([this.center.lng + longitudeDegree * Math.cos(angle), this.center.lat + latitudeDegree * Math.sin(angle)]);
    }

    return {
      type: "Polygon",
      coordinates: [ring],
    };
  }

  update(center, radius, settings = {}) {
    super.update(center, radius, settings);

    this.map.shapeStore.upsert(this);
  }

  remove() {
    this.map.shapeStore.remove(this);

    super.remove();
  }
}
