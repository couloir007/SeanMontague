import { GeolocationLayerFeature } from "../../../../js/LayerFeature/GeolocationLayerFeature.js";

export class MapLibreLayerFeature extends GeolocationLayerFeature {
  /**
   * The map the layer is drawn on.
   *
   * The base class holds the layer, not the map - so reaching the MapLibre
   * library and the map itself goes through it.
   *
   * @return {MapLibre}
   *   Map.
   */
  get map() {
    return this.layer.map;
  }
}
