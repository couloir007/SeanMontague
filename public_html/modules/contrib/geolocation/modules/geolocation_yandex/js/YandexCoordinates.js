/**
 * Yandex draws nothing beyond the 180th meridian, so longitudes are kept just
 * inside it.
 *
 * Geometry types differ only in how deeply their coordinates nest positions,
 * so this descends until it reaches one instead of assuming a depth.
 *
 * @param {Array} coordinates
 *   A position, or an array of them at any nesting depth.
 *
 * @return {Array}
 *   The same structure, with every longitude inside the meridian.
 */
export function clampCoordinates(coordinates) {
  if (typeof coordinates[0] === "number") {
    return [coordinates[0] < 180 ? coordinates[0] : 179.99, coordinates[1]];
  }

  return coordinates.map(clampCoordinates);
}
