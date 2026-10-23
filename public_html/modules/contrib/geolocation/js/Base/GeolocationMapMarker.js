/**
 * @typedef {Object} GeolocationMarkerSettings
 *
 * @prop {String} [id]
 * @prop {String} [title]
 * @prop {String} [icon]
 * @prop {String} [label]
 * @prop {Element} [wrapper]
 * @prop {String} [content]
 * @prop {Boolean} [draggable]
 */

/**
 * @prop {GeolocationCoordinates} coordinates
 * @prop {String} [id]
 * @prop {String} title
 * @prop {String} [icon]
 * @prop {String} [label]
 * @prop {Element} [wrapper]
 * @prop {GeolocationMapBase} map
 * @prop {String} content
 * @prop {GeolocationMarkerSettings} settings
 * @prop {Boolean} [geolocationWidgetIgnore] @internal Usage
 */
export class GeolocationMapMarker {
  /**
   * @param {GeolocationCoordinates} coordinates
   *   Coordinates.
   * @param {GeolocationMarkerSettings} settings
   *   Settings.
   * @param {GeolocationMapBase} map
   *   Map.
   */
  constructor(coordinates, settings = {}, map = null) {
    this.coordinates = coordinates;
    this.settings = settings;
    this.id = settings.id?.toString() ?? "0";
    this.title = settings.title?.toString() ?? undefined;
    this.label = settings.label?.toString() ?? undefined;
    this.icon = settings.icon ?? undefined;
    this.wrapper = settings.wrapper ?? document.createElement("div");
    this.map = map;
    this.content = settings.content ?? this.getContent();
  }

  getContent() {
    if (!this.content) {
      this.content = this.wrapper?.querySelector(".location-content")?.innerHTML ?? "";
    }

    return this.content;
  }

  getContentElement() {
    if (typeof this.contentElement === "undefined") {
      this.contentElement = this.wrapper?.querySelector(".location-content") ?? null;
    }

    return this.contentElement;
  }

  /**
   * @param {GeolocationCoordinates} [newCoordinates]
   *   New coordinates.
   * @param {GeolocationMarkerSettings} [settings]
   *   Settings.
   */
  update(newCoordinates, settings) {
    if (newCoordinates) {
      this.coordinates = newCoordinates;
    }

    if (settings) {
      this.settings = {
        ...this.settings,
        ...settings,
      };

      if (settings.id) {
        this.id = settings.id.toString();
      }
      if (settings.title) {
        this.title = settings.title.toString();
      }
      if (settings.label) {
        this.label = settings.label.toString();
      }
      if (settings.icon) {
        this.icon = settings.icon;
      }
      if (settings.wrapper) {
        this.wrapper = settings.wrapper;
      }
      if (settings.content) {
        this.content = settings.content;
      }
    }

    this.map.dataLayers.forEach((layer) => {
      layer.markerUpdated(this);
    });
  }

  remove() {
    this.map.dataLayers.forEach((layer) => {
      layer.markerRemoved(this);
    });
  }

  /**
   * React to the marker having been clicked.
   */
  onClick() {
    this.map.dataLayers.forEach((layer) => {
      layer.markerClicked(this);
    });
  }

  /**
   * Click the marker.
   */
  click() {
    // Implemented by provider-specific code.
  }

  animate() {
    // Implemented by provider-specific code.
  }
}
