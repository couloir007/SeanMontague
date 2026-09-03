/**
 * @file
 * Javascript for the Geolocation shared functionality.
 */

(function (Drupal) {
  Drupal.geolocation = Drupal.geolocation ?? {};
  Drupal.geolocation.addedScripts = Drupal.geolocation.addedScripts ?? {};
  Drupal.geolocation.addedStylesheets = Drupal.geolocation.addedStylesheets ?? {};

  Drupal.geolocation.hash = (url) => {
    let hash = 0;
    for (let i = 0, len = url.length; i < len; i++) {
      const chr = url.charCodeAt(i);
      hash = (hash << 5) - hash + chr;
      hash |= 0; // Convert to 32bit integer
    }

    return hash;
  };

  Drupal.geolocation.addScript = (url, async = false) => {
    if (!url) {
      return Promise.reject(new Error("geolocation-shared: Cannot add script as URL is missing."));
    }

    const hash = Drupal.geolocation.hash(url);

    if (typeof Drupal.geolocation.addedScripts[hash] !== "undefined") {
      return Drupal.geolocation.addedScripts[hash];
    }

    const promise = new Promise((resolve, reject) => {
      const script = document.createElement("script");
      script.src = url;
      script.onload = (event) => {
        resolve(event);
      };
      script.onerror = function (event) {
        reject(event || "");
      };
      if (async) {
        script.async = true;
      }
      document.body.appendChild(script);
    });

    Drupal.geolocation.addedScripts[hash] = promise;

    return promise;
  };

  Drupal.geolocation.addStylesheet = (url) => {
    if (!url) {
      return Promise.reject(new Error("geolocation-shared: Cannot add stylesheet as URL is missing."));
    }

    const hash = Drupal.geolocation.hash(url);

    if (typeof Drupal.geolocation.addedStylesheets[hash] !== "undefined") {
      return Drupal.geolocation.addedStylesheets[hash];
    }

    const link = document.createElement("link");
    link.href = url;
    link.rel = "stylesheet";
    document.head.appendChild(link);

    Drupal.geolocation.addedStylesheets[hash] = true;

    return Promise.resolve();
  };
})(Drupal);
