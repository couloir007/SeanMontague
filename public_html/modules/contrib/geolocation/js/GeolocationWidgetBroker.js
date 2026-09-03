/**
 * @typedef {Object} WidgetSubscriberSettings
 *
 * @prop {String} import_path
 * @prop {Object} settings
 */

/**
 * @prop {Map<string,WidgetSubscriberBase>} subscribers
 * @prop {Element} form
 * @prop {Object} settings
 * @prop {Object.<string, WidgetSubscriberSettings>} settings.widgetSubscribers
 */
export default class GeolocationWidgetBroker {
  constructor(form, settings) {
    this.form = form;
    this.settings = settings;

    this.subscribers = new Map();

    const subscriberImports = [];

    for (const [widgetSubscriberId, widgetSubscriberSettings] of Object.entries(this.settings.widgetSubscribers)) {
      const widgetSubscriberImport = import(widgetSubscriberSettings.import_path);
      subscriberImports.push(widgetSubscriberImport);
      widgetSubscriberImport.then((value) => {
        const currentSubscriber = new value.default(this, this.settings.widgetSubscribers[widgetSubscriberId].settings ?? {});
        this.subscribers.set(widgetSubscriberId, currentSubscriber);
      });
    }

    Promise.allSettled(subscriberImports).then(() => {
      this.subscribers.forEach((subscriber, widgetSubscriberId) => {
        subscriber.id = widgetSubscriberId;
        subscriber.initialize();
      });
    });
  }

  /**
   * @param {Number[]} newOrder
   *   New Order.
   * @param {String} caller
   *   Calling element.
   */
  orderChanged(newOrder /* - Crystal :) */, caller) {
    this.subscribers.forEach((subscriber, id) => {
      if (id === caller) {
        return;
      }
      subscriber.reorder(newOrder, caller);
    });
  }

  /**
   * @param {GeolocationCoordinates} coordinates
   *   Coordinates.
   * @param {Number} index
   *   Index.
   * @param {String} caller
   *   Calling entity.
   */
  coordinatesAdded(coordinates, index, caller) {
    this.subscribers.forEach((subscriber, id) => {
      if (id === caller) {
        return;
      }
      try {
        subscriber.addCoordinates(coordinates, index, caller);
      } catch (e) {
        console.error(e, `Subscriber ${subscriber.id} failed addCoordinates: ${e.toString()}`);
      }
    });
  }

  /**
   * @param {Number} index
   *   Index.
   * @param {String} caller
   *   Caller.
   */
  coordinatesRemoved(index, caller) {
    this.subscribers.forEach((subscriber, id) => {
      if (id === caller) {
        return;
      }
      try {
        subscriber.removeCoordinates(index, caller);
      } catch (e) {
        console.error(e, `Subscriber ${subscriber.id} failed removeCoordinates: ${e.toString()}`);
      }
    });
  }

  /**
   * @param {GeolocationCoordinates} coordinates
   *   Coordinates.
   * @param {Number} index
   *   Index.
   * @param {String} caller
   *   Caller.
   */
  coordinatesAltered(coordinates, index, caller) {
    this.subscribers.forEach((subscriber, id) => {
      if (id === caller) {
        return;
      }
      try {
        subscriber.alterCoordinates(coordinates, index, caller);
      } catch (e) {
        console.error(e, `Subscriber ${subscriber.id} failed alterCoordinates: ${e.toString()}`);
      }
    });
  }

  /**
   * @param {GeolocationGeometry} geometry
   *   Geometry.
   * @param {Number} index
   *   Index.
   * @param {String} caller
   *   Calling entity.
   */
  geometryAdded(geometry, index, caller) {
    this.subscribers.forEach((subscriber, id) => {
      if (id === caller) {
        return;
      }
      try {
        subscriber.addGeometry(geometry, index, caller);
      } catch (e) {
        console.error(e, `Subscriber ${subscriber.id} failed addGeometry: ${e.toString()}`);
      }
    });
  }

  /**
   * @param {Number} index
   *   Index.
   * @param {String} caller
   *   Caller.
   */
  geometryRemoved(index, caller) {
    this.subscribers.forEach((subscriber, id) => {
      if (id === caller) {
        return;
      }
      try {
        subscriber.removeGeometry(index, caller);
      } catch (e) {
        console.error(e, `Subscriber ${subscriber.id} failed removeGeometry: ${e.toString()}`);
      }
    });
  }

  /**
   * @param {GeolocationGeometry} geometry
   *   Geometry.
   * @param {Number} index
   *   Index.
   * @param {String} caller
   *   Caller.
   */
  geometryAltered(geometry, index, caller) {
    this.subscribers.forEach((subscriber, id) => {
      if (id === caller) {
        return;
      }
      try {
        subscriber.alterGeometry(geometry, index, caller);
      } catch (e) {
        console.error(e, `Subscriber ${subscriber.id} failed alterGeometry: ${e.toString()}`);
      }
    });
  }
}
