import { LeafletMapFeature } from "./LeafletMapFeature.js";

export default class LeafletDisableUserInteraction extends LeafletMapFeature {
  constructor(settings, map) {
    super(settings, map);
    map.leafletMap.dragging.disable();
    map.leafletMap.touchZoom.disable();
    map.leafletMap.doubleClickZoom.disable();
    map.leafletMap.scrollWheelZoom.disable();
  }
}
