import { BaiduMapFeature } from "./BaiduMapFeature.js";

/**
 * @prop {Baidu} map
 * @prop {Object} settings
 * @prop {String} settings.type
 * @prop {String} settings.position
 */
export default class BaiduZoomControl extends BaiduMapFeature {
  constructor(settings, map) {
    super(settings, map);

    this.map.baiduMap.addControl(
      new BMapGL.ZoomControl({
        anchor: this.settings.position,
      })
    );
  }
}
