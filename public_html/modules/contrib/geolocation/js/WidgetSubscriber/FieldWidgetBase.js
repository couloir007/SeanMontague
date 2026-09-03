/**
 * @name GeolocationWidgetSettings
 *
 * @prop {String} autoClientLocationMarker
 * @prop {String} id
 * @prop {String} type
 * @prop {String} field_name
 * @prop {String} cardinality
 */

import { WidgetSubscriberBase } from "./WidgetSubscriberBase.js";
import { GeolocationCoordinates } from "../Base/GeolocationCoordinates.js";

/**
 * @abstract
 *
 * @prop {GeolocationWidgetBroker} broker
 * @prop {Object} settings
 */
export class FieldWidgetBase extends WidgetSubscriberBase {
  constructor(broker, settings) {
    super(broker, settings);

    /**
     * @type {Map<string, Object[]>}
     */
    this.ajaxElementResolvesByIndex = new Map();

    jQuery(document).ajaxComplete(() => {
      const allElements = this.getAllInputElements(true);
      allElements.forEach((element) => {
        if (element.hasAttribute("data-geolocation-widget-index")) {
          return;
        }

        /** @type {int[]} */
        const allIndex = Array.from(allElements)
          .filter((currentElement) => currentElement.hasAttribute("data-geolocation-widget-index"))
          .map((currentElement) => currentElement.getAttribute("data-geolocation-widget-index") ?? 0);
        const newIndex = allIndex.length ? Math.max(...allIndex) + 1 : 0;
        element.setAttribute("data-geolocation-widget-index", newIndex.toString());
      });

      this.ajaxElementResolvesByIndex.forEach((resolves, index) => {
        const element = this.form.querySelector(this.getElementSelectorByIndex(index));
        if (element) {
          resolves.forEach((resolve) => {
            resolve.resolve(element);
          });
          this.ajaxElementResolvesByIndex.delete(index);
        } else if (this.getAllInputElements().size === 0 || this.getAllInputElements().size > index) {
          resolves.forEach((resolve) => {
            resolve.reject(`FieldWidgetBase: Requested index $\{index} element unavailable among ${this.getAllInputElements().size} elements.`);
          });
        } else {
          this.triggerAddMoreElement();
        }
      });
    });

    let wrapper = this.broker.form;
    this.form = wrapper.querySelector(`.field--name-${this.settings.field_name.replaceAll("_", "-")}`);
    while (wrapper.parentNode && !this.form) {
      wrapper = wrapper.parentNode;
      this.form = wrapper.querySelector(`.field--name-${this.settings.field_name.replaceAll("_", "-")}`);
    }
    if (!this.form) {
      console.error(this.broker, "Geolocation Field Widget - Form not found by ID");
      return;
    }

    this.getAllInputElements(true).forEach((element, index) => {
      element.setAttribute("data-geolocation-widget-index", index.toString());

      element.addEventListener("change", () => {
        this.onFormChange(element, index);
      });
    });
  }

  initialize() {
    super.initialize();

    const table = this.form.querySelector("table.field-multiple-table");
    if (table) {
      Drupal.geolocation.widgetSubscriberTableSwapHandlers = Drupal.geolocation.widgetSubscriberTableSwapHandlers ?? new Map();
      Drupal.geolocation.widgetSubscriberTableSwapHandlers.set(this.id, () => {
        const newOrder = [];
        this.getAllInputElements().forEach((element, index) => {
          newOrder.push(index);
          const parentRow = element.closest("tr");
          const newIndex = Array.from(parentRow.parentNode.children).indexOf(parentRow);
          element.setAttribute("data-geolocation-widget-index", newIndex);
        });

        const orderUnchanged = newOrder.every((element, index) => {
          return element === index;
        });

        if (orderUnchanged === false) {
          this.broker.orderChanged(newOrder, this.id);
        }
      });

      const tableDrag = Drupal.tableDrag[table.getAttribute("id")];
      if (tableDrag) {
        tableDrag.geolocationOnSwap = null;
        this.waitFinishedSwap = null;
        tableDrag.row.prototype.onSwap = () => {
          clearTimeout(this.waitFinishedSwap);
          this.waitFinishedSwap = setTimeout(() => {
            if (!Drupal.geolocation.widgetSubscriberTableSwapHandlers.has(this.id)) return;
            Drupal.geolocation.widgetSubscriberTableSwapHandlers.get(this.id)();
          }, 500);
        };
      }
    }
  }

  onFormChange(element, index) {
    this.getCoordinatesByElement(element)
      .then((newCoordinates) => {
        if (!newCoordinates) {
          this.broker.coordinatesRemoved(index, this.id);
        } else {
          this.broker.coordinatesAltered(newCoordinates, index, this.id);
        }
      })
      .catch(() => {
        this.broker.coordinatesRemoved(index, this.id);
      });
  }

  /**
   * Get input elements directly or as map.
   *
   * @param {boolean} returnElements
   * @return {Map<int, Element>|NodeListOf<Element>}
   */
  getAllInputElements(returnElements = false) {
    const map = new Map();
    const elements = this.form.querySelectorAll(this.getElementSelector());

    if (returnElements) {
      return elements;
    }

    elements.forEach((element) => {
      map.set(this.getIndexByElement(element), element);
    });

    return map;
  }

  /**
   * Get index.
   *
   * @param {Element} element
   *   Element.
   *
   * @return {int}
   *   Index.
   */
  getIndexByElement(element) {
    return parseInt(element.getAttribute("data-geolocation-widget-index"));
  }

  getElementSelector() {
    return ".geolocation-widget-input";
  }

  getElementSelectorByIndex(index) {
    return `[data-geolocation-widget-index='${index.toString()}']`;
  }

  triggerAddMoreElement() {
    this.form.querySelector(`[name="${this.settings.field_name}_add_more"]`)?.dispatchEvent(new Event("mousedown"));
  }

  /**
   *
   * @param {int} index
   *   Index.
   * @return {Promise<Element>}
   *   Element.
   */
  getElementByIndex(index) {
    const promise = new Promise((resolve, reject) => {
      if (Number.isNaN(index)) {
        reject(new Error(`FieldWidgetBase: Cannot get element by index as it is not a number: ${index}`));
        return;
      }

      if (!Number.isInteger(index)) {
        reject(new Error(`FieldWidgetBase: Cannot get element by index as it is not an integer: ${index}`));
        return;
      }

      const element = this.form.querySelector(this.getElementSelectorByIndex(index));
      if (element) {
        resolve(element);
        return;
      }

      const resolvesByIndex = this.ajaxElementResolvesByIndex.get(index.toString()) ?? [];
      resolvesByIndex.push({ resolve, reject });
      this.ajaxElementResolvesByIndex.set(index.toString(), resolvesByIndex);

      this.triggerAddMoreElement();
    });
    promise.catch((error) => {
      console.error(`Geolocation Field synchronization failed: ${error}`);
    });
    return promise;
  }

  /**
   * @param {GeolocationCoordinates} coordinates
   * @param {Element} element
   */
  setCoordinatesByElement(coordinates, element) {}

  /**
   * @param {GeolocationGeometry} geometry
   * @param {Element} element
   */
  setGeometryByElement(geometry, element) {}

  /**
   * @param {Element} element
   *   Element.
   *
   * @return {Promise<GeolocationCoordinates>}
   *   Coordinates.
   */
  getCoordinatesByElement(element) {
    return null;
  }

  /**
   * @param {Element} element
   *   Element.
   *
   * @return {Promise<GeolocationGeometry>}
   *   Coordinates.
   */
  getGeometryByElement(element) {
    return null;
  }

  reorder(newOrder, source) {
    super.reorder(newOrder, source);

    const table = this.form.querySelector("table.field-multiple-table");
    const tableDrag = Drupal.tableDrag[table.getAttribute("id")];

    const max = new Map();
    max.set("delta", -1);
    const min = new Map();
    min.set("delta", 0);

    let delta = 0;

    newOrder.forEach((oldIndex, newIndex) => {
      if (newIndex === oldIndex) return;
      delta = oldIndex - newIndex;
      if (delta > max.get("delta")) {
        max.set("index", newIndex);
        max.set("delta", delta);
      }
      if (delta <= min.get("delta")) {
        min.set("index", newIndex);
        min.set("delta", delta);
      }
    });

    if (!max.has("index") || !min.has("index")) return;

    let fromRow;
    let toRow;
    let direction = "before";

    if (Math.abs(max.get("delta")) > Math.abs(min.get("delta"))) {
      fromRow = table.querySelector(`tbody tr:nth-child(${min.get("index") + 1})`);
      toRow = table.querySelector(`tbody tr:nth-child(${max.get("index") + 1})`);
    } else {
      direction = "after";
      fromRow = table.querySelector(`tbody tr:nth-child(${max.get("index") + 1})`);
      toRow = table.querySelector(`tbody tr:nth-child(${min.get("index") + 1})`);
    }

    if (!fromRow || !toRow) {
      console.error("FieldWidgetBase: Cannot reorder due to non-existing rows.");
    }

    new tableDrag.row(fromRow).swap(direction, toRow);
  }

  addCoordinates(coordinates, index, source) {
    super.addCoordinates(coordinates, index, source);

    this.getElementByIndex(index).then((element) => {
      this.setCoordinatesByElement(coordinates, element);
    });
  }

  removeCoordinates(index, source) {
    super.removeCoordinates(index, source);

    this.getElementByIndex(index).then((element) => {
      this.setCoordinatesByElement(null, element);
    });
  }

  alterCoordinates(coordinates, index, source) {
    super.alterCoordinates(coordinates, index, source);

    this.getElementByIndex(index).then((element) => {
      this.setCoordinatesByElement(coordinates, element);
    });
  }

  addGeometry(geometry, index, source) {
    super.addGeometry(geometry, index, source);

    this.getElementByIndex(index).then((element) => {
      this.setGeometryByElement(geometry, element);
    });
  }

  removeGeometry(index, source) {
    super.removeGeometry(index, source);

    this.getElementByIndex(index).then((element) => {
      this.setGeometryByElement(null, element);
    });
  }

  alterGeometry(geometry, index, source) {
    super.alterGeometry(geometry, index, source);

    this.getElementByIndex(index).then((element) => {
      this.setGeometryByElement(geometry, element);
    });
  }
}
