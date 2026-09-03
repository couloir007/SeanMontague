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
      this.leafletMarker.bindTooltip(String(this.settings.label), {
        permanent: true,
        direction: "top",
      });
    }

    this.leafletMarker.on("click", () => {
      this.click();
    });

    if (this.settings.draggable) {
      this.leafletMarker.dragging.enable();
      this.leafletMarker.on("dragend", (e) => {
        /** @type LatLng */
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
      this.leafletMarker.unbindTooltip().bindTooltip(String(this.settings.label), {
        permanent: true,
        direction: "top",
      });
    }

    if (this.settings.icon) {
      this.leafletMarker.setIcon(L.icon({ iconUrl: this.settings.icon }));
    }
  }

  remove() {
    super.remove();

    this.map.markerLayer.removeLayer(this.leafletMarker);
  }
}
