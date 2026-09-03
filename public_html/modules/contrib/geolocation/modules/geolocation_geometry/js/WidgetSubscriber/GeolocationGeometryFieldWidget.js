/**
 * @name GeolocationWidgetSettings
 *
 * @prop {String} id
 * @prop {String} type
 * @prop {String} fieldName
 * @prop {String} cardinality
 */

import { FieldWidgetBase } from "../../../../js/WidgetSubscriber/FieldWidgetBase.js";
import { GeolocationGeometry } from "../../../../js/Base/GeolocationGeometry.js";

export default class GeolocationGeometryFieldWidget extends FieldWidgetBase {
  getElementSelector() {
    return ".geolocation-geometry-widget-geojson-input";
  }

  onFormChange(element, index) {
    this.getGeometryByElement(element)
      .then((newGeometry) => {
        if (!newGeometry) {
          this.broker.geometryRemoved(index, this.id);
        } else {
          this.broker.geometryAltered(newGeometry, index, this.id);
        }
      })
      .catch(() => {
        this.broker.geometryRemoved(index, this.id);
      });
  }

  setGeometryByElement(geometry, element) {
    if (geometry) {
      element.value = JSON.stringify(geometry);
      element.setAttribute("data-geolocation-current-value", JSON.stringify(geometry));
    } else {
      element.value = "";
      element.setAttribute("data-geolocation-current-value", "");
    }
  }

  getElementSelectorByIndex(index) {
    return `.geolocation-geometry-widget-geojson-input[data-geolocation-widget-index='${index.toString()}']`;
  }

  getGeometryByElement(element) {
    let geometry;

    try {
      geometry = JSON.parse(element.value);
    } catch (e) {
      return Promise.reject(new Error(`GeolocationGeometryFieldWidget: Cannot get geometry by element due to JSON error. ${e.toString()}`));
    }

    if (!geometry.type || !geometry.coordinates) {
      return Promise.reject(new Error("GeolocationGeometryFieldWidget: Cannot get geometry as type or coordinates are missing."));
    }

    return Promise.resolve(new GeolocationGeometry(geometry.type, geometry.coordinates));
  }

  alterGeometry(geometry, index, source) {
    this.getElementByIndex(index).then((element) => {
      this.getGeometryByElement(element)
        .then((currentGeometry) => {
          if (currentGeometry === geometry) {
            return;
          }

          super.alterGeometry(geometry, index, source);
        })
        .catch(() => {
          super.alterGeometry(geometry, index, source);
        });
    });
  }
}
