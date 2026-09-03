import { LeafletMapFeature } from "./LeafletMapFeature.js";

export default class LeafletGestureHandling extends LeafletMapFeature {
  constructor(settings, map) {
    super(settings, map);
    L.Util.setOptions(map.leafletMap, {
      gestureHandlingOptions: {
        duration: 1000,
      },
    });
    map.leafletMap.gestureHandling.enable();
  }
}
