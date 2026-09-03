/**
 * @file
 * Javascript for the Geolocation GPX elevation chart.
 */

/* global Chart */

(function (Drupal) {
  /**
   * @type {Drupal~behavior}
   * @type {Object} drupalSettings.geolocation
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches Geolocation widget functionality to relevant elements.
   */
  Drupal.behaviors.geolocationGpxElevationChart = {
    /**
     * @param {Document} context
     * @param {Object} drupalSettings
     */
    attach: (context, drupalSettings) => {
      context.querySelectorAll(".geolocation-gpx-elevation-chart").forEach((wrapper) => {
        if (wrapper.classList.contains("processed")) {
          return;
        }
        wrapper.classList.add("processed");

        const table = wrapper.previousElementSibling;
        if (!table) {
          return;
        }
        if (!table.classList.contains("geolocation-gpx-elevation-table")) {
          return;
        }
        table.classList.add("visually-hidden");

        const elevationPoints = [];
        table.querySelectorAll("tbody > tr").forEach((row) => {
          const cells = row.querySelectorAll("td");
          elevationPoints.push({
            index: cells.item(0).textContent,
            elevation: parseFloat(cells.item(1).textContent),
            distance: parseFloat(cells.item(2).textContent),
            time: cells.item(3).textContent,
          });
        });

        new Chart(wrapper, {
          type: "line",
          data: {
            labels: elevationPoints.map((row) => Math.round(row.distance)),
            datasets: [
              {
                label: "Elevation",
                data: elevationPoints,
              },
            ],
          },
          options: {
            parsing: {
              xAxisKey: "distance",
              yAxisKey: "elevation",
            },
            elements: {
              point: {
                pointRadius: 1,
              },
            },
            scales: {
              y: {
                title: {
                  text: Drupal.t("Elevation"),
                  display: true,
                },
                ticks: {
                  // Include a dollar sign in the ticks
                  callback: (value) => {
                    return `${value}m`;
                  },
                },
              },
              x: {
                title: {
                  text: Drupal.t("Distance"),
                  display: true,
                },
                ticks: {
                  callback: (value) => {
                    const distance = new Intl.NumberFormat(Drupal.langcode).format(Math.round(value / 100) / 10);
                    return `${distance}km`;
                  },
                },
              },
            },
            plugins: {
              legend: {
                display: false,
              },
              tooltip: {
                displayColors: false,
                callbacks: {
                  title: () => {
                    return "";
                  },
                  label: (context) => {
                    const label = [];

                    if (context.parsed.y !== null) {
                      const elevation = new Intl.NumberFormat(Drupal.langcode).format(context.parsed.y);
                      label.push(`Elevation: ${elevation}m`);
                    }

                    if (context.parsed.x !== null) {
                      const distance = new Intl.NumberFormat(Drupal.langcode).format(context.parsed.x / 1000);
                      label.push(`Distance: ${distance}km`);
                    }
                    return label;
                  },
                },
              },
            },
          },
        });
      });
    },
  };
})(Drupal);
