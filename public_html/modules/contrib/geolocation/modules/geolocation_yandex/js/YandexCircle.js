import { GeolocationCircle } from "../../../js/Base/GeolocationCircle.js";

/* global turf */
/* global ymaps3 */

/**
 * @prop {Yandex} map
 */
export class YandexCircle extends GeolocationCircle {
  constructor(center, radius, map, settings = {}) {
    super(center, radius, map, settings);

    const { YMapFeature } = ymaps3;

    const turfLink = "https://cdn.jsdelivr.net/npm/@turf/turf@7/turf.min.js";

    import(turfLink).then(() => {
      this.yandexCircle = new YMapFeature({
        geometry: turf.circle([this.center.lng, this.center.lat], this.radius, { units: "meters" }).geometry,
        style: {
          simplificationRate: 0,
          stroke: [{ color: this.strokeColor, width: this.strokeWidth }],
          fill: this.fillColor,
          fillOpacity: this.fillOpacity,
        },
      });

      this.map.yandexMap.addChild(this.yandexCircle);
    });
  }

  update(center, radius, settings) {
    super.update(center, radius, settings);

    this.yandexCircle.update({
      geometry: turf.circle([this.center.lng, this.center.lat], this.radius, { units: "meters" }).geometry,
      style: {
        simplificationRate: 0,
        stroke: [{ color: this.strokeColor, width: this.strokeWidth }],
        fill: this.fillColor,
        fillOpacity: this.fillOpacity,
      },
    });
  }

  remove() {
    this.map.yandexMap.removeChild(this.yandexCircle);
    super.remove();
  }
}
