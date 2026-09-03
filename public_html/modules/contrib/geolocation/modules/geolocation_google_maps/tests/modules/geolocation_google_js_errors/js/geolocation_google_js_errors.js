sessionStorage.setItem("geolocation_google_js_errors", JSON.stringify([]));

if (typeof console !== "undefined" && console.error) {
  const originalErrorFunction = console.error;
  console.error = (error) => {
    const errors = JSON.parse(sessionStorage.getItem("geolocation_google_js_errors"));
    errors.push(error);
    sessionStorage.setItem("geolocation_google_js_errors", JSON.stringify(errors));
    originalErrorFunction(error);
  };
}
