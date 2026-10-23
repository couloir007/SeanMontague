import { YandexMapFeature } from "./YandexMapFeature.js";
import { importDefaultUiTheme } from "../YandexPackages.js";

/* global ymaps3 */

export default class YandexControlGeolocation extends YandexMapFeature {
  constructor(settings, map) {
    super(settings, map);

    const { YMapControls } = ymaps3;

    importDefaultUiTheme().then((ymaps3Theme) => {
      const { YMapGeolocationControl } = ymaps3Theme;

      this.map.yandexMap.addChild(
        new YMapControls({
          position: this.settings.position ?? "right",
        }).addChild(new YMapGeolocationControl({}))
      );
    });
  }
}
