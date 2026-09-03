import { GeolocationLayerFeature } from "./GeolocationLayerFeature.js";

/**
 * @prop {WidgetSubscriberBase} subscriber
 */
export default class GeolocationMarkerScrollToId extends GeolocationLayerFeature {
  onMarkerClicked(marker) {
    super.onMarkerClicked(marker);

    const id = marker.wrapper.getAttribute("data-scroll-target-id");

    if (id) {
      document.querySelector(`#${id}:visible`)?.scrollIntoView();
    }
  }
}
