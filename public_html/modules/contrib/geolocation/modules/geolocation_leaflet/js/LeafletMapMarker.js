import { GeolocationMapMarker } from "../../../js/Base/GeolocationMapMarker.js";
import { GeolocationCoordinates } from "../../../js/Base/GeolocationCoordinates.js";

/**
 * @prop {L.Marker} leafletMarker
 * @prop {Leaflet} map
 */
export class LeafletMapMarker extends GeolocationMapMarker {
  constructor(coordinates, settings = {}, map = null) {
    super(coordinates, settings, map);

    const markerOptions = {
      title: this.settings.title ?? "",
      label: this.settings.label ?? "",
    };
    if (this.settings.icon) {
      markerOptions.icon = this.settings.icon
        ? L.icon({
            iconUrl: this.settings.icon,
          })
        : L.Icon.Default;
    }
    this.leafletMarker = L.marker([coordinates.lat, coordinates.lng], markerOptions).addTo(this.map.markerLayer);

    if (this.settings.label) {
      this.leafletMarker.bindTooltip(Drupal.checkPlain(String(this.settings.label)), {
        permanent: true,
        direction: "top",
      });
    }

    this.leafletMarker.on("click", () => {
      this.onClick();
    });

    if (this.settings.draggable) {
      this.leafletMarker.dragging.enable();
      this.leafletMarker.on("dragend", (e) => {
        /** @type {L.LatLng} */
        const latLng = e.target.getLatLng();
        this.update(new GeolocationCoordinates(latLng.lat, latLng.lng));
      });
    }
  }

  update(newCoordinates, settings) {
    super.update(newCoordinates, settings);

    if (newCoordinates) {
      if (!newCoordinates.equals(this.leafletMarker.getLatLng().lat, this.leafletMarker.getLatLng().lng)) {
        this.leafletMarker.setLatLng([newCoordinates.lat, newCoordinates.lng]);
      }
    }

    if (this.settings.label) {
      this.leafletMarker.unbindTooltip().bindTooltip(Drupal.checkPlain(String(this.settings.label)), {
        permanent: true,
        direction: "top",
      });
    }

    if (this.settings.icon) {
      this.leafletMarker.setIcon(L.icon({ iconUrl: this.settings.icon }));
    }
  }

  /**
   * Leaflet reacts to marker clicks itself, e.g. by opening a bound popup.
   */
  click() {
    super.click();

    this.leafletMarker.fire("click", { latlng: this.leafletMarker.getLatLng() });
  }

  remove() {
    super.remove();

    this.map.markerLayer.removeLayer(this.leafletMarker);
  }
}
