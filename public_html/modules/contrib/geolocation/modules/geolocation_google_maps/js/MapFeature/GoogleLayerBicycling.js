import { GoogleMapFeature } from "./GoogleMapFeature.js";

export default class GoogleLayerBicycling extends GoogleMapFeature {
  constructor(settings, map) {
    super(settings, map);

    const bikeLayer = new google.maps.BicyclingLayer();
    bikeLayer.setMap(this.map.googleMap);
  }
}
