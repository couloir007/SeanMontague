import { GeolocationBaseMap } from "./GeolocationBaseMap.js";

/**
 * Resolves the mapbox:// URLs a Mapbox style names its own resources by.
 *
 * The style document itself is fetched over HTTPS, but the sources, sprites
 * and fonts inside it are addressed in a scheme only Mapbox's own renderer
 * understands. Those requests are only made once the style has been parsed,
 * so installing the rewrite as the map is constructed is early enough.
 */
export default class Mapbox extends GeolocationBaseMap {
  attach() {
    this.map.maplibreMap.setTransformRequest((url) => {
      return this.resolveUrl(url);
    });
  }

  /**
   * The access token, taken from the style URL it already travels in.
   *
   * @return {String}
   *   Access token, empty where the style carries none.
   */
  get accessToken() {
    try {
      return new URL(this.map.settings.maplibre_settings.style_url, window.location.href).searchParams.get("access_token") ?? "";
    } catch (e) {
      return "";
    }
  }

  /**
   * @param {String} url
   *   URL MapLibre is about to request.
   *
   * @return {Object|undefined}
   *   A replacement request, or nothing to leave the URL alone.
   */
  resolveUrl(url) {
    if (!url.startsWith("mapbox://")) {
      return undefined;
    }

    const path = url.replace("mapbox://", "");
    const token = this.accessToken;
    const withToken = (target) => {
      return { url: `${target}${target.includes("?") ? "&" : "?"}access_token=${token}` };
    };

    // Sprites carry a suffix - "sprite@2x.png", "sprite.json" - that belongs
    // after the path rather than in it.
    if (path.startsWith("sprites/")) {
      const [, sprite, suffix = ""] = path.match(/^sprites\/(.+?)(\.[a-z0-9@.]+)?$/i) ?? [];
      return withToken(`https://api.mapbox.com/styles/v1/${sprite}/sprite${suffix}`);
    }

    if (path.startsWith("fonts/")) {
      return withToken(`https://api.mapbox.com/fonts/v1/${path.replace("fonts/", "")}`);
    }

    // Anything else is a tileset, whose TileJSON names the tiles themselves.
    return withToken(`https://api.mapbox.com/v4/${path}.json?secure`);
  }
}
