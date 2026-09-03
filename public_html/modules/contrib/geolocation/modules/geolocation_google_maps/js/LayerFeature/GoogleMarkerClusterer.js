import { GoogleLayerFeature } from "./GoogleLayerFeature.js";

/**
 * @typedef {Object} MarkerClustererSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {String} image_path
 * @prop {Object} styles
 * @prop {Number} max_zoom
 * @prop {Number} grid_size
 * @prop {Boolean} zoom_on_click
 * @prop {Number} average_center
 * @prop {Number} minimum_cluster_size
 */

/* global markerClusterer */

/**
 * @prop {MarkerClustererSettings} settings
 */
export default class GoogleMarkerClusterer extends GoogleLayerFeature {
  constructor(settings, layer) {
    super(settings, layer);

    if (typeof markerClusterer === "undefined") {
      throw new Error("MarkerCluster not found");
    }

    this.markerClusterer = new markerClusterer.MarkerClusterer({
      map: this.layer.map.googleMap,
    });
  }

  onMarkerAdded(marker) {
    super.onMarkerAdded(marker);

    this.markerClusterer.addMarker(marker.googleMarker);
  }

  onMarkerRemove(marker) {
    super.onMarkerRemove(marker);

    this.markerClusterer.removeMarker(marker.googleMarker);
  }
}
