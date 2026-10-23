import { GeolocationLayerFeature } from "./GeolocationLayerFeature.js";

/**
 * @prop {WidgetSubscriberBase} subscriber
 */
export default class GeolocationMarkerScrollToId extends GeolocationLayerFeature {
  onMarkerClicked(marker) {
    super.onMarkerClicked(marker);

    const id = marker.wrapper.getAttribute("data-scroll-target-id");

    if (!id) {
      return;
    }

    const target = document.getElementById(id);

    if (target && target.getClientRects().length) {
      target.scrollIntoView({ behavior: "smooth", block: "start" });
    }
  }
}
