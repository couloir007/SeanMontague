import { GeolocationMapMarker } from "../../../js/Base/GeolocationMapMarker.js";

/* global ymaps3 */

/**
 * @prop {YMapMarker} yandexMarker
 * @prop {Yandex} map
 */
export class YandexMapMarker extends GeolocationMapMarker {
  constructor(coordinates, settings = {}, map = null) {
    super(coordinates, settings, map);

    ymaps3.import("@yandex/ymaps3-markers@0.0.1").then((ymaps3Markers) => {
      const { YMapDefaultMarker } = ymaps3Markers;
      const { YMapMarker } = ymaps3;

      const props = {
        coordinates: [coordinates.lng, coordinates.lat],
        popup: { content: this.getContent() },
        title: this.settings.title ?? "",
        onClick: () => {
          this.click();
        },
      };

      if (this.settings.icon) {
        const markerElement = document.createElement("img");
        markerElement.className = "icon-marker";
        markerElement.src = this.settings.icon;
        markerElement.style.maxWidth = "unset";
        this.yandexMarker = new YMapMarker(props, markerElement);
      } else {
        this.yandexMarker = new YMapDefaultMarker(props);
      }

      if (this.settings.draggable) {
        props.draggable = this.settings.draggable ?? false;
        this.yandexMarker.update({
          onDragEnd: (e) => {
            this.update(this.map.normalizeCoordinates(e.coordinates));
          },
        });
      }

      this.map.yandexMap.addChild(this.yandexMarker);
    });
  }

  update(newCoordinates, settings) {
    super.update(newCoordinates, settings);

    const currentCoordinates = this.map.normalizeCoordinates(this.yandexMarker.geometry.getCoordinates());

    if (newCoordinates) {
      if (!newCoordinates.equals(currentCoordinates.lat, currentCoordinates.lng)) {
        this.yandexMarker.geometry.setCoordinates(this.map.denormalizeCoordinates(newCoordinates));
      }
    }

    if (this.settings.title) {
      this.yandexMarker.update.set("title", this.settings.title);
    }
    if (this.settings.label) {
      this.yandexMarker.options.set("label", this.settings.label);
    }
    if (this.settings.icon) {
      this.yandexMarker.options.set("iconImageHref", this.settings.icon);
    }
  }

  remove() {
    super.remove();

    this.map.yandexMap.removeChild(this.yandexMarker);
  }
}
