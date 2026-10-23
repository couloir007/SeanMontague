import { GeolocationMapMarker } from "../../../js/Base/GeolocationMapMarker.js";
import { GeolocationCoordinates } from "../../../js/Base/GeolocationCoordinates.js";

/**
 * @prop {google.maps.marker.AdvancedMarkerElement} googleMarker
 * @prop {GoogleMaps} map
 */
export class GoogleMapMarker extends GeolocationMapMarker {
  constructor(coordinates, settings = {}, map = null) {
    super(coordinates, settings, map);

    this.googleMarker = new google.maps.marker.AdvancedMarkerElement({
      position: this.coordinates,
      map: this.map.googleMap,
      title: this.title,
    });

    if (this.label) {
      const glyphLabel = document.createElement("span");
      glyphLabel.style.fontWeight = "bold";
      glyphLabel.style.fontSize = "14px";
      glyphLabel.innerText = this.label;
      this.googleMarker.content = new google.maps.marker.PinElement({
        glyph: glyphLabel,
      }).element;
    }

    if (this.icon) {
      const icon = document.createElement("img");
      icon.src = this.icon;
      this.googleMarker.content = icon;
    }

    this.googleMarker.addListener("gmp-click", () => {
      this.onClick();
    });

    if (this.settings.draggable) {
      this.googleMarker.gmpDraggable = true;
      this.googleMarker.addListener("dragend", (e) => {
        this.update(new GeolocationCoordinates(Number(e.latLng.lat()), Number(e.latLng.lng())));
      });
    }
  }

  update(newCoordinates, settings) {
    super.update(newCoordinates, settings);

    if (newCoordinates) {
      const { position } = this.googleMarker;
      const lat = typeof position.lat === "function" ? position.lat() : position.lat;
      const lng = typeof position.lng === "function" ? position.lng() : position.lng;

      if (!newCoordinates.equals(lat, lng)) {
        this.googleMarker.position = this.coordinates;
      }
    }

    if (this.title) {
      this.googleMarker.title = this.title;
    }

    if (this.label) {
      const glyphLabel = document.createElement("span");
      glyphLabel.style.fontWeight = "bold";
      glyphLabel.style.fontSize = "14px";
      glyphLabel.innerText = this.label;
      this.googleMarker.content = new google.maps.marker.PinElement({
        glyph: glyphLabel,
      }).element;
    }

    if (this.icon) {
      const icon = document.createElement("img");
      icon.src = this.icon;
      this.googleMarker.content = icon;
    }
  }

  /**
   * Click the marker through Google's own click event, like a real click.
   */
  click() {
    super.click();

    google.maps.event.trigger(this.googleMarker, "gmp-click");
  }

  remove() {
    super.remove();

    this.googleMarker.remove();
  }
}
