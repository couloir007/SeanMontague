import { GeolocationShape } from "./GeolocationShape.js";

/**
 * @prop {GeolocationGeometry} geometry
 */
export class GeolocationShapeMultiLine extends GeolocationShape {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    this.type = "multiline";
  }
}
