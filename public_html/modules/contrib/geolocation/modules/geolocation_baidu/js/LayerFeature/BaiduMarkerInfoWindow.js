import { BaiduLayerFeature } from "./BaiduLayerFeature.js";

/**
 * @prop {Baidu} map
 * @prop {Object} settings
 * @prop {String} settings.type
 * @prop {String} settings.position
 */
export default class BaiduMarkerInfoWindow extends BaiduLayerFeature {
  onMarkerClicked(marker) {
    super.onMarkerClicked(marker);

    this.layer.map.baiduMap.openInfoWindow(
      new BMapGL.InfoWindow(marker.getContent(), {
        width: 200,
        height: 100,
        title: marker.title,
      }),
      marker.baiduMarker.getPosition()
    );
  }
}
