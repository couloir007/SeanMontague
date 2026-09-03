import { YandexMapFeature } from "./YandexMapFeature.js";

/* global ymaps3 */

export default class YandexControlZoom extends YandexMapFeature {
  constructor(settings, map) {
    super(settings, map);

    const { YMapControls } = ymaps3;

    ymaps3.import("@yandex/ymaps3-controls@0.0.1").then((ymapsControls) => {
      const { YMapZoomControl } = ymapsControls;

      this.map.yandexMap.addChild(
        new YMapControls({
          position: this.settings.position ?? "right",
        }).addChild(new YMapZoomControl({}))
      );
    });
  }
}
