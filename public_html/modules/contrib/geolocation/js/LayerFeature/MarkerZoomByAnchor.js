import { GeolocationBoundaries } from "../Base/GeolocationBoundaries.js";
import { GeolocationLayerFeature } from "./GeolocationLayerFeature.js";

/**
 * Zoom to a marker when a link pointing at its anchor ID is clicked.
 */
export default class MarkerZoomByAnchor extends GeolocationLayerFeature {
  /**
   * @param {GeolocationLayerFeatureSettings} settings
   *   Settings.
   * @param {GeolocationDataLayer} layer
   *   Layer.
   */
  constructor(settings, layer) {
    super(settings, layer);

    // Delegated, so links added by AJAX after the map loaded work as well.
    this.clickHandler = (event) => {
      this.onLinkClicked(event);
    };

    document.addEventListener("click", this.clickHandler);
  }

  destroy() {
    document.removeEventListener("click", this.clickHandler);

    super.destroy();
  }

  /**
   * @param {MouseEvent} event
   *   Click event.
   */
  onLinkClicked(event) {
    const link = event.target?.closest?.("a.geolocation-marker-zoom");

    if (!link) {
      return;
    }

    const anchorId = decodeURIComponent(link.hash.slice(1));

    if (!anchorId) {
      return;
    }

    const marker = this.layer.markers.find((candidate) => {
      return candidate.wrapper?.getAttribute("data-marker-zoom-anchor-id") === anchorId;
    });

    // Any other map or layer on the page might own this anchor instead.
    if (!marker) {
      return;
    }

    event.preventDefault();

    const { wrapper } = this.layer.map;
    if (wrapper?.getClientRects().length) {
      wrapper.scrollIntoView({ behavior: "smooth", block: "start" });
    }

    // A single marker has no extent, fitting the map to it would zoom in as far
    // as the map allows. Frame roughly a kilometer around it instead.
    const lngSpan = 0.005 / Math.max(Math.cos(marker.coordinates.lat * (Math.PI / 180)), 0.01);
    this.layer.map.setBoundaries(new GeolocationBoundaries(marker.coordinates.lat + 0.005, marker.coordinates.lng + lngSpan, marker.coordinates.lat - 0.005, marker.coordinates.lng - lngSpan));

    marker.animate();

    if (this.settings.click_marker) {
      marker.click();
    }
  }
}
