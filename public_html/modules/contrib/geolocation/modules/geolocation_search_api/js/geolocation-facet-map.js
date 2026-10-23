/**
 * @file
 * Filters by the current map boundaries, once asked to.
 */

(function (Drupal, once) {
  /**
   * Rounds a coordinate for the URL.
   *
   * @param {Number} value
   *   Coordinate.
   *
   * @return {String}
   *   Rounded coordinate.
   */
  function round(value) {
    return String(Number(Number(value).toFixed(6)));
  }

  Drupal.behaviors.geolocationFacetMap = {
    attach: (context) => {
      once("geolocation-facet-map", ".geolocation-facet-map-filter", context).forEach((button) => {
        Drupal.geolocation.maps.getMap(button.dataset.mapId).then((map) => {
          button.disabled = false;

          button.addEventListener("click", () => {
            const bounds = map.getBoundaries();
            if (!bounds) {
              return;
            }

            const value = [bounds.north, bounds.east, bounds.south, bounds.west].map(round).join(",");
            window.location.href = button.dataset.url.replace(button.dataset.placeholder, encodeURIComponent(value));
          });
        });
      });
    },
  };
})(Drupal, once);
