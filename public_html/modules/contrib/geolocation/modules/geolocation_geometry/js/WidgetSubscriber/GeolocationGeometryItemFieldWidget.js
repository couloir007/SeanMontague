import { WidgetSubscriberBase } from "../../../../js/WidgetSubscriber/WidgetSubscriberBase.js";
import { GeolocationGeometry } from "../../../../js/Base/GeolocationGeometry.js";

/**
 * The GeoJSON input of one multi geometry field item.
 *
 * The field wide input subscriber addresses every item by index; here the
 * broker covers a single item, so there is exactly one input and the index the
 * broker passes around is always its own.
 *
 * @prop {HTMLTextAreaElement|null} element
 */
export default class GeolocationGeometryItemFieldWidget extends WidgetSubscriberBase {
  constructor(broker, settings) {
    super(broker, settings);

    this.element = this.broker.form.querySelector(".geolocation-geometry-widget-geojson-input");

    this.element?.addEventListener("change", () => {
      this.onFormChange();
    });
  }

  onFormChange() {
    const geometry = this.getGeometry();

    if (!geometry) {
      this.broker.geometryRemoved(0, this.id);
      return;
    }

    this.broker.geometryAltered(geometry, 0, this.id);
  }

  /**
   * @return {GeolocationGeometry|null}
   *   Geometry currently in the input, NULL while it holds nothing usable.
   */
  getGeometry() {
    if (!this.element?.value) {
      return null;
    }

    let geometry;
    try {
      geometry = JSON.parse(this.element.value);
    } catch (e) {
      console.error(`GeolocationGeometryItemFieldWidget: Cannot read geometry due to JSON error. ${e.toString()}`);
      return null;
    }

    if (!geometry.type || !geometry.coordinates) {
      return null;
    }

    return new GeolocationGeometry(geometry.type, geometry.coordinates);
  }

  /**
   * @param {GeolocationGeometry|null} geometry
   *   Geometry to write, NULL to empty the input.
   */
  setGeometry(geometry) {
    if (!this.element) {
      return;
    }

    this.element.value = geometry ? JSON.stringify(geometry) : "";
    this.element.setAttribute("data-geolocation-current-value", this.element.value);
  }

  addGeometry(geometry, index, source) {
    this.setGeometry(geometry);
  }

  alterGeometry(geometry, index, source) {
    this.setGeometry(geometry);
  }

  removeGeometry(index, source) {
    this.setGeometry(null);
  }
}
