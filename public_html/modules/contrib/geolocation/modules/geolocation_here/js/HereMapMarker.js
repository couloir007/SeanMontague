import { GeolocationMapMarker } from "../../../js/Base/GeolocationMapMarker.js";

/**
 * @prop {H.map.Marker} hereMarker
 * @prop {GeolocationMapBase} map
 */
export class HereMapMarker extends GeolocationMapMarker {
  constructor(coordinates, settings = {}, map = null) {
    super(coordinates, settings, map);

    const hereMarkerSettings = {
      title: settings.title,
    };

    if (typeof settings.icon === "string") {
      hereMarkerSettings.icon = new H.map.Icon(settings.icon);
    }

    this.hereMarker = new H.map.Marker(coordinates, settings);

    this.hereMarker.addEventListener("click", () => {
      this.click();
    });
  }

  update(newCoordinates, settings) {
    super.update(newCoordinates, settings);

    if (newCoordinates) {
      if (!newCoordinates.equals(this.hereMarker.getGeometry())) {
        this.hereMarker.setGeometry(newCoordinates);
      }
    }

    if (this.settings.icon) {
      this.hereMarker.setIcon(new H.map.Icon(this.settings.icon));
    }
  }

  remove() {
    super.remove();

    this.hereMap.removeObject(this.hereMarker);
  }
}
