import { GeolocationMapMarker } from "../../../js/Base/GeolocationMapMarker.js";
import { importDefaultUiTheme } from "./YandexPackages.js";

/* global ymaps3 */

/**
 * @prop {YMapMarker} yandexMarker
 * @prop {Yandex} map
 */
export class YandexMapMarker extends GeolocationMapMarker {
  constructor(coordinates, settings = {}, map = null) {
    super(coordinates, settings, map);

    importDefaultUiTheme().then((ymaps3Theme) => {
      const { YMapDefaultMarker } = ymaps3Theme;
      const { YMapMarker } = ymaps3;

      const props = {
        coordinates: [coordinates.lng, coordinates.lat],
        title: this.settings.title ?? "",
        onClick: () => {
          this.onClick();
        },
      };

      if (this.settings.icon) {
        const markerElement = document.createElement("img");
        markerElement.className = "icon-marker";
        markerElement.src = this.settings.icon;
        markerElement.style.maxWidth = "unset";
        this.markerElement = markerElement;

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
    const previousCoordinates = this.coordinates;

    super.update(newCoordinates, settings);

    if (!this.yandexMarker) {
      return;
    }

    if (newCoordinates && !newCoordinates.equals(previousCoordinates.lat, previousCoordinates.lng)) {
      const coordinates = this.map.denormalizeCoordinates(newCoordinates);

      this.yandexMarker.update({ coordinates });
    }

    if (this.markerElement) {
      if (this.settings.icon) {
        this.markerElement.src = this.settings.icon;
      }
    } else if (this.settings.title) {
      this.yandexMarker.update({ title: this.settings.title });
    }
  }

  /**
   * Click the marker element, like a real click.
   *
   * Yandex finds the clicked marker by position, so the event has to carry
   * the element's coordinates. Default markers wrap the actual marker.
   */
  click() {
    super.click();

    const element = this.yandexMarker.element ?? this.yandexMarker.children[0].element;
    const rect = element.getBoundingClientRect();

    element.dispatchEvent(new MouseEvent("click", { bubbles: true, clientX: rect.x + rect.width / 2, clientY: rect.y + rect.height / 2 }));
  }

  remove() {
    super.remove();

    this.map.yandexMap.removeChild(this.yandexMarker);
  }
}
