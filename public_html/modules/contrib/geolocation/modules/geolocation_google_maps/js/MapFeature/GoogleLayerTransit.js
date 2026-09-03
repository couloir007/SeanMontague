import { GoogleMapFeature } from "./GoogleMapFeature.js";

export default class GoogleLayerTransit extends GoogleMapFeature {
  constructor(settings, map) {
    super(settings, map);

    const trafficLayer = new google.maps.TransitLayer();
    trafficLayer.setMap(this.map.googleMap);
  }
}
