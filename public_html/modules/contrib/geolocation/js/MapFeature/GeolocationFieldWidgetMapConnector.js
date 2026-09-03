/**
 * @typedef {Object} GeolocationFieldWidgetMapConnectorSettings
 *
 * @prop {int} cardinality
 * @prop {string} field_type
 */

import { GeolocationMapFeature } from "./GeolocationMapFeature.js";

/**
 * @prop {WidgetSubscriberBase} subscriber
 *
 * @prop {GeolocationFieldWidgetMapConnectorSettings} settings
 */
export default class GeolocationFieldWidgetMapConnector extends GeolocationMapFeature {
  setWidgetSubscriber(subscriber) {
    this.subscriber = subscriber;
  }

  /**
   * @param {GeolocationMapMarker} marker
   *  Marker.
   *
   * @return {int|null}
   *   Index.
   */
  getIndexByMarker(marker) {
    return Number(marker.wrapper.dataset.geolocationWidgetIndex ?? 0);
  }

  /**
   * @param {int} index
   *  Index.
   *
   * @return {GeolocationMapMarker|null}
   *   Marker.
   */
  getMarkerByIndex(index) {
    let returnValue = null;
    this.map.dataLayers.get("default").markers.forEach((marker) => {
      if (index === this.getIndexByMarker(marker)) {
        returnValue = marker;
      }
    });

    return returnValue;
  }

  /**
   * @param {GeolocationMapMarker} marker
   *   Marker.
   * @param {int|false} index
   *   Index.
   */
  setIndexByMarker(marker, index = false) {
    if (index === false) {
      delete marker.wrapper.dataset.geolocationWidgetIndex;
    } else {
      marker.wrapper.dataset.geolocationWidgetIndex = index.toString();
    }
  }

  /**
   * @param {Number} index
   *   Index.
   * @param {GeolocationCoordinates} coordinates
   *   Coordinates.
   *
   * @return {String}
   *   Title.
   */
  getMarkerTitle(index, coordinates) {
    return `${index + 1}: ${coordinates.lat.toString()}, ${coordinates.lng.toString()}`;
  }

  addMarkerSilently(index, coordinates) {
    const marker = this.map.createMarker(coordinates, {
      title: this.getMarkerTitle(index, coordinates),
      label: index + 1,
      draggable: true,
    });

    this.setIndexByMarker(marker, index);

    marker.geolocationWidgetIgnore = true;
    this.map.dataLayers.get("default").markerAdded(marker);
    delete marker.geolocationWidgetIgnore;

    this.map.fitMapToElements();

    return marker;
  }

  reorderSilently(newOrder) {
    this.map.dataLayers.get("default").markers.forEach((marker) => {
      const oldIndex = this.getIndexByMarker(marker);
      const newIndex = newOrder.indexOf(oldIndex);

      marker.geolocationWidgetIgnore = true;
      marker.update(null, {
        title: this.getMarkerTitle(newIndex, marker.coordinates),
        label: newIndex + 1,
      });
      delete marker.geolocationWidgetIgnore;

      this.setIndexByMarker(marker, newIndex);
    });
  }

  updateMarkerSilently(index, coordinates, settings = null) {
    const marker = this.getMarkerByIndex(index);

    if (!marker) return this.addMarkerSilently(index, coordinates);

    if (marker.coordinates === coordinates && !settings) {
      return;
    }

    marker.geolocationWidgetIgnore = true;
    marker.update(coordinates, settings ?? {});
    delete marker.geolocationWidgetIgnore;

    this.map.fitMapToElements();

    return marker;
  }

  removeMarkerSilently(index) {
    const marker = this.getMarkerByIndex(index);

    marker.geolocationWidgetIgnore = true;
    marker.remove();

    this.map.fitMapToElements();
  }

  onClick(coordinates) {
    super.onClick(coordinates);

    if (this.settings.cardinality > this.map.dataLayers.get("default").markers.length || this.settings.cardinality === -1) {
      let newIndex = 0;
      this.map.dataLayers.get("default").markers.forEach((marker) => {
        const markerIndex = this.getIndexByMarker(marker) ?? 0;
        if (markerIndex >= newIndex) {
          newIndex = markerIndex + 1;
        }
      });

      const marker = this.map.createMarker(coordinates, {
        title: this.getMarkerTitle(newIndex, coordinates),
        label: (newIndex + 1).toString(),
        draggable: true,
      });

      this.setIndexByMarker(marker, newIndex);

      // Will trigger onMarkerAdded and notify broker.
      this.map.dataLayers.get("default").markerAdded(marker);
      return;
    }

    if (this.settings.cardinality > 1) {
      const warning = document.createElement("div");
      warning.innerHTML = `<p>${Drupal.t("Maximum number of locations reached.")}</p>`;
      Drupal.dialog(warning, {
        title: Drupal.t("Synchronization"),
      }).showModal();
    } else {
      const marker = this.getMarkerByIndex(0);
      if (!marker) {
        console.error(this, "Marker not found");
      }
      marker?.update(coordinates);
    }
  }

  onMarkerAdded(marker) {
    super.onMarkerAdded(marker);

    if (marker.geolocationWidgetIgnore ?? false) return;

    this.subscriber?.coordinatesAdded(marker.coordinates, this.getIndexByMarker(marker) ?? 0);
  }

  onMarkerClicked(marker) {
    super.onMarkerClicked(marker);

    // Will trigger onMarkerRemoved and notify broker.
    marker.remove();
  }

  onMarkerUpdated(marker) {
    super.onMarkerUpdated(marker);

    if (marker.geolocationWidgetIgnore ?? false) return;

    this.subscriber?.coordinatesAltered(marker.coordinates, this.getIndexByMarker(marker));
  }

  onMarkerRemove(marker) {
    super.onMarkerRemove(marker);

    if (marker.geolocationWidgetIgnore ?? false) return;

    this.subscriber?.coordinatesRemoved(this.getIndexByMarker(marker));
  }
}
