/**
 * @file
 * Javascript for the Geolocation map formatter.
 */

(function (Drupal) {
  Drupal.geolocation.maps = {
    /**
     * @type {Map<string, GeolocationMapBase>}
     */
    initializedMaps: new Map(),
    mapPromises: new Map(),
    delayedInitialization: new Map(),
    mapProviderSettings: new Map(),
    addInitializedMap(map) {
      this.initializedMaps.forEach((initializedMap, mapId) => {
        if (initializedMap.wrapper && !document.contains(initializedMap.wrapper)) {
          this.removeMap(mapId);
        }
      });

      this.initializedMaps.set(map.id, map);
      if (this.mapPromises.has(map.id)) {
        for (const resolve of this.mapPromises.get(map.id)) {
          resolve(map);
        }
        this.mapPromises.delete(map.id);
      }
    },
    /**
     * Destroy a map that left the page for good and forget it.
     *
     * @param {string} mapId
     *   ID of the map to remove.
     */
    removeMap(mapId) {
      try {
        this.initializedMaps.get(mapId)?.destroy();
      } catch (e) {
        console.error(e, `Geolocation - Map ${mapId} failed destroy: ${e.toString()}`);
      }
      this.initializedMaps.delete(mapId);
    },
    /**
     * @param {string} mapId
     *   ID of the map to retrieve.
     * @return {Promise<GeolocationMapBase>}
     *   Resolves with the map once it has been initialized.
     */
    getMap(mapId) {
      return new Promise((resolve) => {
        if (this.initializedMaps.has(mapId)) {
          resolve(this.initializedMaps.get(mapId));
        } else {
          const promises = this.mapPromises.get(mapId) ?? [];
          promises.push(resolve);
          this.mapPromises.set(mapId, promises);
        }
      });
    },
    addMapProviderCallback(mapProviderId, callback) {
      let mapProviderSettings;
      if (this.mapProviderSettings.has(mapProviderId)) {
        mapProviderSettings = this.mapProviderSettings.get(mapProviderId);
      } else {
        mapProviderSettings = {
          ready: false,
          callbacks: [],
        };
      }

      if (mapProviderSettings.ready) {
        callback();
      } else {
        mapProviderSettings.callbacks.push(callback);
        this.mapProviderSettings.set(mapProviderId, mapProviderSettings);
      }
    },
    mapProviderCallback(mapProviderId) {
      // A provider library can finish loading before any map got as far as
      // registering for it, so readiness is recorded either way - otherwise a
      // callback added afterwards would wait for a signal already given.
      const mapProviderSettings = this.mapProviderSettings.get(mapProviderId) ?? {
        ready: false,
        callbacks: [],
      };
      mapProviderSettings.ready = true;

      while (mapProviderSettings.callbacks.length) {
        mapProviderSettings.callbacks.shift()();
      }
      this.mapProviderSettings.set(mapProviderId, mapProviderSettings);
    },
    delayInitialization(mapId, callback) {
      const callbacks = this.delayedInitialization.get(mapId) ?? [];
      callbacks.push(callback);
      this.delayedInitialization.set(mapId, callbacks);
    },
    initializeDelayed(mapId = null) {
      if (mapId) {
        if (this.delayedInitialization.has(mapId)) {
          for (const callback of this.delayedInitialization.get(mapId)) {
            callback();
          }
          this.delayedInitialization.delete(mapId);

          return true;
        }
        return false;
      }

      this.delayedInitialization.forEach((callbacks, mapId) => {
        for (const callback of this.delayedInitialization.get(mapId) ?? []) {
          callback();
        }
        this.delayedInitialization.delete(mapId);
      });

      return true;
    },
  };

  Drupal.AjaxCommands.prototype.geolocation = function (ajax, response) {
    if (response.method !== "replaceCommonMapView") {
      return;
    }

    const oldContent = document.querySelector(response.selector);

    if (!oldContent) {
      return;
    }

    const template = document.createElement("template");
    template.innerHTML = response.data.trim();
    const newContent = template.content.firstElementChild;

    oldContent.insertAdjacentElement("afterend", newContent);

    new Promise((resolve) => {
      const mapPromises = [];
      oldContent.querySelectorAll(".geolocation-map-wrapper").forEach((oldMapWrapper) => {
        const mapId = oldMapWrapper.getAttribute("id");
        if (!mapId) {
          console.error("Ajax.replaceCommonMapView: Existing Map ID not found.");
          return;
        }

        if (!Drupal.geolocation.maps.initializedMaps.has(mapId)) {
          console.error("Ajax.replaceCommonMapView: Existing Map not found.");
          return;
        }

        const oldMapContainer = oldMapWrapper.querySelector(".geolocation-map-container");
        if (!oldMapContainer) {
          console.error("Ajax.replaceCommonMapView: Existing Map container not found.");
          return;
        }

        const newMapWrapper = newContent.querySelector(".geolocation-map-wrapper");
        if (!newMapWrapper) {
          // Detaching the old content below removes the map.
          console.error(`Ajax.replaceCommonMapView: New Map wrapper '${mapId}' not found.`);
          return;
        }

        const newMapContainer = newMapWrapper.querySelector(".geolocation-map-container");
        if (!newMapContainer) {
          console.error("Ajax.replaceCommonMapView: New Map container not found.");
          return;
        }

        mapPromises.push(
          new Promise((resolve) => {
            const map = Drupal.geolocation.maps.initializedMaps.get(mapId);
            map.wrapper = newMapWrapper;
            const newContainerParent = newMapContainer.parentNode;
            newMapContainer.remove();
            newContainerParent.appendChild(oldMapContainer);
            resolve();
          })
        );
      });

      resolve(Promise.all(mapPromises));
    }).then(() => {
      // Only now, so detaching does not forget the maps carried over above.
      Drupal.detachBehaviors(oldContent, response.settings);
      oldContent.remove();
      Drupal.attachBehaviors(newContent, response.settings);
    });
  };

  /**
   * Find and display all maps.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches Geolocation Maps formatter functionality to relevant elements.
   */
  Drupal.behaviors.geolocationMap = {
    /**
     * @param {HTMLDocument|HTMLElement} context
     *   The context to attach to.
     * @param {Object} drupalSettings
     *   Drupal settings.
     * @param {Object} drupalSettings.geolocation
     *   Geolocation settings.
     * @param {GeolocationMapSettings[]} drupalSettings.geolocation.maps
     *   Settings keyed by map ID.
     */
    attach: (context, drupalSettings) => {
      context.querySelectorAll(".geolocation-map-wrapper").forEach(function (mapWrapper) {
        if (typeof drupalSettings.geolocation === "undefined") {
          throw new Error("Bailing out for lack of settings.");
        }

        if (mapWrapper.length === 0) {
          console.info("Stopping map processing as no map wrapper present.");
          return;
        }

        if (mapWrapper.classList.contains("geolocation-map-processed")) {
          return;
        }
        mapWrapper.classList.add("geolocation-map-processed");

        const mapSettings = {};
        mapSettings.id = mapWrapper.getAttribute("id");
        mapSettings.wrapper = mapWrapper;

        mapSettings.lat = 0;
        mapSettings.lng = 0;

        for (const mapId of Object.keys(drupalSettings.geolocation.maps)) {
          if (mapId === mapSettings.id) {
            Object.assign(mapSettings, drupalSettings.geolocation.maps[mapId]);
          }
        }

        if (mapWrapper.getAttribute("data-lat") && mapWrapper.getAttribute("data-lng")) {
          mapSettings.lat = Number(mapWrapper.getAttribute("data-lat"));
          mapSettings.lng = Number(mapWrapper.getAttribute("data-lng"));
        }

        if (mapWrapper.getAttribute("data-zoom")) {
          mapSettings.zoom = Number(mapWrapper.getAttribute("data-zoom"));
        }

        if (mapWrapper.getAttribute("map-type")) {
          mapSettings.type = mapWrapper.getAttribute("map-type");
        }

        new Promise((resolve, reject) => {
          const map = Drupal.geolocation.maps.initializedMaps.get(mapSettings.id);
          if (map) {
            resolve(map);
          } else {
            reject(new Error("geolocation-map: Could not create GeolocationMap object."));
          }
        })
          .catch(() => {
            return new Promise((resolve) => {
              switch (mapSettings.conditional_initialization ?? "no") {
                case "programmatically":
                  Drupal.geolocation.maps.delayInitialization(mapSettings.id, () => {
                    resolve();
                  });
                  break;

                case "button": {
                  Drupal.geolocation.maps.delayInitialization(mapSettings.id, () => {
                    resolve();
                  });

                  const mapContainer = mapWrapper.querySelector(".geolocation-map-container");
                  if (!mapContainer) {
                    return;
                  }
                  const conditionalInitContainer = document.createElement("div");
                  conditionalInitContainer.classList.add("geolocation-map-conditional");

                  const conditionalInitDescription = document.createElement("div");
                  conditionalInitDescription.textContent = mapSettings.conditional_description ?? Drupal.t("Clicking this button will embed a map.");
                  conditionalInitContainer.appendChild(conditionalInitDescription);

                  const button = document.createElement("button");
                  button.innerHTML = mapSettings.conditional_label ?? Drupal.t("Show map");
                  button.classList.add("button");
                  button.onclick = (event) => {
                    event.preventDefault();
                    Drupal.geolocation.maps.initializeDelayed(mapSettings.id);
                    conditionalInitContainer.remove();
                  };
                  conditionalInitContainer.appendChild(button);

                  mapContainer.appendChild(conditionalInitContainer);
                  break;
                }

                case "viewport": {
                  Drupal.geolocation.maps.delayInitialization(mapSettings.id, () => {
                    resolve();
                  });

                  const mapContainer = mapWrapper.querySelector(".geolocation-map-container");
                  if (!mapContainer) {
                    return;
                  }

                  const observer = new IntersectionObserver(
                    (entries, observer) => {
                      entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                          Drupal.geolocation.maps.initializeDelayed(mapSettings.id);
                          observer.unobserve(entry.target);
                        }
                      });
                    },
                    {
                      root: null,
                      rootMargin: "0px",
                      threshold: mapSettings.conditional_viewport_threshold,
                    }
                  );
                  observer.observe(mapContainer);

                  break;
                }

                default:
                  resolve();
              }
            })
              .then(() => {
                return import(mapSettings.import_path);
              })
              .then((mapProviderImport) => {
                /** @type {GeolocationMapBase} */
                const map = new mapProviderImport.default(mapSettings);
                return map.initialize();
              })
              .then((map) => {
                return map.loadCenterOptions();
              })
              .then((map) => {
                return map.loadFeatures();
              })
              .then((map) => {
                map.removeControls();
                map.wrapper.querySelectorAll(".geolocation-map-controls > *").forEach((control) => {
                  // A single broken control must not cost the map its tile
                  // layers, markers and centering further down the chain.
                  try {
                    map.addControl(control);
                  } catch (e) {
                    console.error(e, `Control ${control.className} could not be added to the map.`);
                  }
                });
                return map;
              });
          })
          .then((map) => {
            return map.loadTileLayers();
          })
          .then((map) => {
            map.removeMapMarkers();
            map.removeMapShapes();
            return map.loadDataLayers();
          })
          .then((map) => {
            if (!map.dataLayers.has("default")) {
              throw new Error("No default layer defined. Breaking");
            }
            return map;
          })
          .then((map) => {
            // Only _after_ markers are loaded.
            if (map.keepViewport) {
              map.keepViewport = false;
            } else {
              map.setCenterByOptions();
            }

            // Just in case.
            map.wrapper.classList.remove("ajax-loading");

            map.wrapper.querySelectorAll(".geolocation-location").forEach((element) => {
              if (element.closest(".geolocation-map-wrapper") !== map.wrapper) {
                return;
              }
              element.style.display = "none";
            });

            map.readyFeatures();

            return map;
          })
          .then((map) => {
            Drupal.geolocation.maps.addInitializedMap(map);
          })
          .catch((e) => {
            console.error(e, "Something failed during map init.");
            return false;
          });
      });
    },
    /**
     * @param {Document|Element} context
     *   The context being detached.
     * @param {Object} drupalSettings
     *   Drupal settings.
     * @param {String} trigger
     *   Why the behaviour is detaching.
     */
    detach: (context, drupalSettings, trigger) => {
      // Behaviours are also detached to serialize a form, where the maps stay
      // exactly where they are.
      if (trigger !== "unload") {
        return;
      }

      // A map moved on to a new wrapper by replaceCommonMapView is kept.
      context.querySelectorAll?.(".geolocation-map-wrapper").forEach((mapWrapper) => {
        const { maps } = Drupal.geolocation;
        const map = maps.initializedMaps.get(mapWrapper.id);
        if (map && map.wrapper !== mapWrapper) {
          return;
        }

        maps.removeMap(mapWrapper.id);

        // Nor may a map still waiting to be initialized come to life later.
        maps.delayedInitialization.delete(mapWrapper.id);
        maps.mapPromises.delete(mapWrapper.id);
      });
    },
  };
})(Drupal);
