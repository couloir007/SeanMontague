import { GeolocationShape } from "./GeolocationShape.js";

/**
 * @prop {GeolocationGeometry} geometry
 */
export class GeolocationShapePolygon extends GeolocationShape {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    this.type = "polygon";
  }
}
