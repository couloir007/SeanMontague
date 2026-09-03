import { GoogleMapFeature } from "./GoogleMapFeature.js";

export default class GoogleLayerTraffic extends GoogleMapFeature {
  constructor(settings, map) {
    super(settings, map);

    const trafficLayer = new google.maps.TrafficLayer();
    trafficLayer.setMap(this.map.googleMap);
  }
}
