import { GeolocationMapFeature } from "./GeolocationMapFeature.js";

/**
 * @prop {String|null} activeBaseLayer
 */
export default class ControlTileLayers extends GeolocationMapFeature {
  onMapReady() {
    super.onMapReady();

    const controlContainer = this.map.wrapper.querySelector(".geolocation-map-control.control_tile_layers");
    if (!controlContainer) {
      return;
    }

    this.activeBaseLayer = null;

    // Every configured layer is loaded by the map, so all base layers but the
    // active one have to go again.
    controlContainer.querySelectorAll('input[type="radio"][data-tile-layer-id]').forEach((baseLayerInput) => {
      const layerId = baseLayerInput.getAttribute("data-tile-layer-id");

      if (baseLayerInput.checked) {
        this.activeBaseLayer = layerId;
      } else {
        this.map.unloadTileLayer(layerId);
      }

      baseLayerInput.addEventListener("change", () => {
        if (!baseLayerInput.checked) {
          return;
        }

        if (this.activeBaseLayer) {
          this.map.unloadTileLayer(this.activeBaseLayer);
        }

        this.map.loadTileLayer(layerId, this.map.settings.tile_layers[layerId] ?? {});
        this.activeBaseLayer = layerId;
      });
    });

    controlContainer.querySelectorAll('input[type="checkbox"][data-tile-layer-id]').forEach((overlayInput) => {
      const layerId = overlayInput.getAttribute("data-tile-layer-id");

      overlayInput.addEventListener("change", () => {
        if (overlayInput.checked) {
          this.map.loadTileLayer(layerId, this.map.settings.tile_layers[layerId] ?? {});
        } else {
          this.map.unloadTileLayer(layerId);
        }
      });
    });
  }
}
