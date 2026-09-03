import { GeolocationMapFeature } from "./GeolocationMapFeature.js";

export default class ControlViewFullscreen extends GeolocationMapFeature {
  constructor(settings, map) {
    super(settings, map);

    const button = this.map.wrapper.querySelector("button.geolocation-control-view-fullscreen");
    const viewContainer = this.map.wrapper.closest(".views-element-container");

    button.addEventListener("click", () => {
      let fullscreen = false;
      if (typeof document.fullscreenElement !== "undefined") {
        if (document.fullscreenElement === viewContainer) {
          fullscreen = true;
        }
      } else if (typeof viewContainer.fullscreen !== "undefined") {
        if (viewContainer.fullscreen) {
          fullscreen = true;
        }
      }
      if (fullscreen) {
        document.exitFullscreen();
      } else {
        viewContainer.requestFullscreen();
      }
    });
  }
}
