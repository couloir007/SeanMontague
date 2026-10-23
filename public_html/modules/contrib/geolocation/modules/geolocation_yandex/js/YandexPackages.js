/* global ymaps3 */

const defaultUiThemePackage = "@yandex/ymaps3-default-ui-theme";
const defaultUiThemeVersion = "0.0.25";

export const hint = "@yandex/ymaps3-hint@0.0.1";

let defaultUiThemeRegistered = false;

export function importDefaultUiTheme() {
  if (!defaultUiThemeRegistered) {
    ymaps3.import.registerCdn("https://cdn.jsdelivr.net/npm/{package}", [`${defaultUiThemePackage}@${defaultUiThemeVersion}`]);
    defaultUiThemeRegistered = true;
  }

  return ymaps3.import(defaultUiThemePackage);
}
