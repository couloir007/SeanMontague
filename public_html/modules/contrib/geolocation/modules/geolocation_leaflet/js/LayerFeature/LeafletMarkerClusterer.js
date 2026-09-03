import { LeafletLayerFeature } from "./LeafletLayerFeature.js";

/**
 * @typedef {Object} MarkerClustererSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {String} zoom_to_bounds_on_click
 * @prop {String} show_coverage_on_hover
 * @prop {int} disable_clustering_at_zoom
 * @prop {Object.<string, Object<string, int>>} custom_marker_settings
 */

/**
 * @prop {MarkerClustererSettings} settings
 */
export default class LeafletMarkerClusterer extends LeafletLayerFeature {
  constructor(settings, layer) {
    super(settings, layer);

    const options = {
      showCoverageOnHover: this.settings.show_coverage_on_hover ?? false,
      zoomToBoundsOnClick: this.settings.zoom_to_bounds_on_click ?? false,
    };

    if (this.settings.disable_clustering_at_zoom ?? null) {
      options.disableClusteringAtZoom = this.settings.disable_clustering_at_zoom;
    }

    if (this.settings.custom_marker_settings) {
      options.iconCreateFunction = (cluster) => {
        const childCount = cluster.getChildCount();
        const customMarkers = this.settings.custom_marker_settings;
        let className = " marker-cluster-";
        let radius = 40;

        Object.entries(customMarkers ?? {}).forEach((sizeSettings, size) => {
          if (childCount < sizeSettings.limit ?? 99) {
            className += size;
            radius = sizeSettings.radius ?? 50;
          }
        });

        return new L.DivIcon({
          html: `<div><span>${childCount}</span></div>`,
          className: `marker-cluster${className}`,
          iconSize: new L.Point(radius, radius),
        });
      };
    }

    this.cluster = L.markerClusterGroup(options);

    this.layer.map.leafletMap.removeLayer(this.layer.map.markerLayer);
    this.cluster.addLayer(this.layer.map.markerLayer);

    this.layer.map.leafletMap.addLayer(this.cluster);
  }

  onMarkerAdded(marker) {
    super.onMarkerAdded(marker);

    this.cluster.addLayer(marker.leafletMarker);
  }
}
