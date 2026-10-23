import { GeolocationBaseMap } from "./GeolocationBaseMap.js";

/**
 * Relabelling for base maps serving the OpenMapTiles schema.
 *
 * @prop {Object} settings
 * @prop {String[]} [settings.language_fields]
 */
export class OpenMapTiles extends GeolocationBaseMap {
  attach() {
    if (!this.settings.language_fields?.length) {
      return;
    }

    this.map.maplibreMap.once("load", () => {
      this.relabel(this.settings.language_fields);
    });
  }

  /**
   * Point every label layer at the localized name field.
   *
   * A style document is static - its labels name the field they read, and no
   * service offers a switch for that - so localizing means rewriting the
   * style once it has loaded.
   *
   * @param {String[]} fields
   *   Fields holding the wanted language, most preferred first.
   */
  relabel(fields) {
    const { maplibreMap } = this.map;
    const localized = fields.map((field) => ["get", field]);

    this.originalTextFields = this.originalTextFields ?? new Map();

    (maplibreMap.getStyle()?.layers ?? []).forEach((layer) => {
      if (layer.type !== "symbol") {
        return;
      }

      // Relabelling has to start from the style's own expression every time,
      // or a second call - a language switch, a reloaded style - wraps the
      // first call's expression instead of replacing it.
      if (!this.originalTextFields.has(layer.id)) {
        this.originalTextFields.set(layer.id, layer.layout?.["text-field"]);
      }

      const original = this.originalTextFields.get(layer.id);
      let fallback;

      if (Array.isArray(original)) {
        // An expression can be kept whole as the last resort.
        fallback = [original];
      } else if (typeof original === "string") {
        // A legacy "{name:latin} {name:nonlatin}" token string cannot be
        // nested into an expression - the OpenMapTiles default styles are
        // written this way - so fall back to the fields it would have read.
        fallback = [
          ["get", "name:latin"],
          ["get", "name"],
        ];
      } else {
        // A stops function carries zoom-dependent labels that no expression
        // here would reproduce; replacing it outright would lose them.
        return;
      }

      maplibreMap.setLayoutProperty(layer.id, "text-field", ["coalesce", ...localized, ...fallback]);
    });
  }
}
