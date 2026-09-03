import { GeolocationMapFeature } from "./GeolocationMapFeature.js";

export default class ControlTileLayers extends GeolocationMapFeature {
  onMapReady() {
    super.onMapReady();

    const controlContainer = this.map.wrapper.querySelector(".geolocation-map-control.control_tile_layers");
    if (!controlContainer) {
      return;
    }

    const tileCheckboxes = controlContainer.querySelectorAll('input[type="checkbox"]');
    tileCheckboxes.forEach((checkbox) => {
      const layerId = checkbox.getAttribute("name");
      checkbox.addEventListener("change", () => {
        if (checkbox.checked) {
          this.map.loadTileLayer(layerId, this.map.settings.tile_layers[layerId] ?? {});
        } else {
          this.map.unloadTileLayer(layerId);
        }
      });
    });
  }
}
