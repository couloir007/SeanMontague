import { GoogleMapFeature } from "./GoogleMapFeature.js";

export default class GoogleMapTilt extends GoogleMapFeature {
  constructor(settings, map) {
    super(settings, map);

    this.map.googleMap.setTilt(0);
  }
}
