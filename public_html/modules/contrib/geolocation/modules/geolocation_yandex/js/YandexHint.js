/* eslint-disable max-classes-per-file */
/* global ymaps3 */

export class YandexHint {
  addHintToMap() {
    return new Promise((resolve) => {
      ymaps3.import("@yandex/ymaps3-hint@0.0.1").then((ymaps3hint) => {
        const { YMapHint, YMapHintContext } = ymaps3hint;
        const { YMapEntity } = ymaps3;
        const hint = new YMapHint({
          hint: (object) => object?.properties?.label,
        });

        // Add your custom hint window to the hint, which will be displayed when you hover over the geo object.
        hint.addChild(
          new (class YandexHintWindowEntity extends YMapEntity {
            _onAttach() {
              this._element = document.createElement("div");
              this._element.className = "geolocation-yandex-hint";

              this._detachDom = ymaps3.useDomContext(this, this._element, this._element);
              this._watchContext(
                YMapHintContext,
                () => {
                  this._element.textContent = this._consumeContext(YMapHintContext)?.hint;
                },
                { immediate: true }
              );
            }

            _onUpdate(props, oldProps) {}

            _onDetach() {
              this._detachDom();
            }
          })()
        );

        resolve(hint);
      });
    });
  }
}
