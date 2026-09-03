/**
 * @file
 * Test behavior for geolocation geometry test js module.
 */

(function (Drupal) {
  /**
   * Test behavior that prints a console log message.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.geolocationGeometryTestJs = {
    attach: (context, settings) => {
      if (document.body.classList.contains("geolocation-geometry-leaflet-country-test")) {
        return;
      }
      document.body.classList.add("geolocation-geometry-leaflet-country-test");
      const message = new Drupal.Message();

      if (typeof Drupal.geolocation.maps === "undefined") {
        return message.add("Geolocation not present.", { type: "error" });
      }

      const wrapper = document.querySelector(".geolocation-map-wrapper");

      if (!wrapper) {
        return message.add("No map element present.", { type: "error" });
      }

      if (typeof wrapper.id === "undefined") {
        return message.add("Map ID not found.", { type: "error" });
      }

      Drupal.geolocation.maps
        .getMap(wrapper.id)
        .then((map) => {
          let countries;
          try {
            countries = map.dataLayers.get("default").shapes;
          } catch (err) {
            return message.add("Geolocation map not loaded. " + err.toString(), { type: "error" });
          }

          if (countries.length === 0) {
            return message.add("Geolocation no shapes found. ", { type: "error" });
          }
          message.add("Geolocation found countries: " + countries.length, { type: "status" });
        })
        .catch((err) => {
          return message.add("Geolocation map not loaded. " + err.toString(), { type: "error" });
        });
    },
  };
})(Drupal);
