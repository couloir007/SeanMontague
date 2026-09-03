/**
 * @prop {Number}  north
 * @prop {Number}  east
 * @prop {Number}  south
 * @prop {Number}  west
 */
export class GeolocationBoundaries {
  /**
   * @param {Object} args
   *
   * Alternatively pass:
   *   - northEast, southWest objects
   *   - north, east, south, west primitives
   *
   * @todo: Add range checks.
   */
  constructor(...args) {
    switch (arguments.length) {
      case 1:
        if (typeof args[0] === "object") {
          const { north, east, south, west } = args[0];
          this.north = Number(north);
          this.east = Number(east);
          this.south = Number(south);
          this.west = Number(west);
        }
        break;

      case 2:
        this.north = Number(args[0][0]);
        this.east = Number(args[0][1]);
        this.south = Number(args[1][0]);
        this.west = Number(args[1][1]);
        break;

      case 4:
        this.north = Number(args[0]);
        this.east = Number(args[1]);
        this.south = Number(args[2]);
        this.west = Number(args[3]);
        break;

      default:
        throw new Error("GeolocationBoundary could not be created.");
    }

    this.containToMax();
  }

  /**
   * @param {GeolocationBoundaries} bounds
   *   Boundary to compare.
   *
   * @return {boolean}
   *   Equals given boundary.
   * */
  equals(bounds) {
    if (!bounds) {
      return false;
    }

    let equal = false;

    const precision = 5;
    if (
      this.north.toFixed(precision) === bounds.north.toFixed(precision) &&
      this.east.toFixed(precision) === bounds.east.toFixed(precision) &&
      this.south.toFixed(precision) === bounds.south.toFixed(precision) &&
      this.west.toFixed(precision) === bounds.west.toFixed(precision)
    ) {
      equal = true;
    }

    return equal;
  }

  extend(bounds) {
    if (!bounds) {
      return this;
    }

    this.north = bounds.north > this.north ? bounds.north : this.north;
    this.south = bounds.south < this.south ? bounds.south : this.south;
    this.east = bounds.east > this.east ? bounds.east : this.east;
    this.west = bounds.west < this.west ? bounds.west : this.west;

    return this.containToMax();
  }

  containToMax() {
    this.north = this.north < 90 ? this.north : 90;
    this.south = this.south > -90 ? this.south : -90;
    this.east = this.east < 180 ? this.east : 180;
    this.west = this.west > -180 ? this.west : -180;

    return this;
  }
}
