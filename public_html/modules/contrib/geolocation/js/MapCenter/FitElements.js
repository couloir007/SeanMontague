import { GeolocationMapCenterBase } from "./GeolocationMapCenterBase.js";

/**
 * @prop {int} settings.min_zoom
 */
export default class FitElements extends GeolocationMapCenterBase {
  setCenter() {
    super.setCenter();

    if (this.map.dataLayers.get("default").markers.length === 0 && this.map.dataLayers.get("default").shapes.length === 0) {
      return false;
    }

    this.map.fitMapToElements();

    if (this.settings.min_zoom) {
      this.map.getZoom().then((zoom) => {
        if (this.settings.min_zoom < zoom) {
          this.map.setZoom(this.settings.min_zoom);
        }
      });
    }

    return true;
  }
}
