import { GeolocationMapMarker } from "../../../js/Base/GeolocationMapMarker.js";

/**
 * @prop {import("maplibre-gl").Marker} maplibreMarker
 * @prop {MapLibre} map
 */
export class MapLibreMapMarker extends GeolocationMapMarker {
  constructor(coordinates, settings = {}, map = null) {
    super(coordinates, settings, map);

    const options = {
      draggable: this.settings.draggable ?? false,
    };

    if (this.icon) {
      const icon = document.createElement("img");
      icon.className = "geolocation-maplibre-marker-icon";
      icon.src = this.icon;
      icon.alt = this.title ?? "";
      options.element = icon;
    }

    this.maplibreMarker = new this.map.maplibregl.Marker(options).setLngLat(this.map.denormalizeCoordinates(coordinates)).addTo(this.map.maplibreMap);

    const element = this.maplibreMarker.getElement();
    if (this.title) {
      element.title = this.title;
    }
    element.addEventListener("click", () => {
      this.onClick();
    });

    if (options.draggable) {
      this.maplibreMarker.on("dragend", () => {
        this.update(this.map.normalizeCoordinates(this.maplibreMarker.getLngLat()));
      });
    }
  }

  update(newCoordinates, settings) {
    super.update(newCoordinates, settings);

    if (newCoordinates) {
      this.maplibreMarker.setLngLat(this.map.denormalizeCoordinates(newCoordinates));
    }

    const element = this.maplibreMarker.getElement();

    if (this.title) {
      element.title = this.title;
    }

    if (this.icon && element.tagName === "IMG") {
      element.src = this.icon;
    }
  }

  /**
   * MapLibre reacts to marker clicks itself, e.g. by toggling a set popup.
   */
  click() {
    super.click();

    this.maplibreMarker.getElement().click();
  }

  remove() {
    super.remove();

    this.maplibreMarker.remove();
  }
}
