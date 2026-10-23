import { GeolocationMapMarker } from "../../../js/Base/GeolocationMapMarker.js";
import { GeolocationCoordinates } from "../../../js/Base/GeolocationCoordinates.js";

/**
 * @prop {BMapGL.Marker} baiduMarker
 * @prop {Baidu} map
 */
export class BaiduMapMarker extends GeolocationMapMarker {
  constructor(coordinates, settings = {}, map = null) {
    super(coordinates, settings, map);

    const baiduMarkerSettings = {
      // BMapGL.Marker reads title.length, so it must never be undefined.
      title: this.settings.title ?? "",
    };

    if (this.settings.icon) {
      baiduMarkerSettings.icon = new BMapGL.Icon(this.settings.icon, new BMapGL.Size(300, 157));
    }

    this.baiduMarker = new BMapGL.Marker(new BMapGL.Point(coordinates.lng, coordinates.lat), baiduMarkerSettings);

    if (this.settings.label) {
      this.baiduMarker.setLabel(new BMapGL.Label(Drupal.checkPlain(String(this.settings.label))));
    }

    this.baiduMarker.addEventListener("click", () => {
      this.onClick();
    });

    if (this.settings.draggable) {
      this.baiduMarker.enableDragging();
      this.baiduMarker.addEventListener("dragend", (e) => {
        this.update(new GeolocationCoordinates(Number(e.point.lng), Number(e.point.lat)));
      });
    }
  }

  update(newCoordinates, settings) {
    super.update(newCoordinates, settings);

    if (newCoordinates) {
      if (!newCoordinates.equals(this.baiduMarker.getPosition().lat, this.baiduMarker.getPosition().lng)) {
        this.baiduMarker.setPosition(new BMapGL.Point(newCoordinates.lng, newCoordinates.lat));
      }
    }

    if (this.settings.title) {
      this.baiduMarker.setTitle(this.settings.title);
    }
    if (this.settings.label) {
      this.baiduMarker.setLabel(new BMapGL.Label(Drupal.checkPlain(String(this.settings.label))));
    }
    if (this.settings.icon) {
      this.baiduMarker.setIcon(new BMapGL.Icon(this.settings.icon, new BMapGL.Size(300, 157)));
    }
  }

  /**
   * Click the marker through Baidu's own click event, like a real click.
   */
  click() {
    super.click();

    this.baiduMarker.dispatchEvent(new BMapGL.Event("onclick"));
  }

  remove() {
    super.remove();

    this.map.baiduMap.removeOverlay(this.baiduMarker);
  }
}
