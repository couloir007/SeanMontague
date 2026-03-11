(function () {
  'use strict';

  ((Drupal, once) => {
    function applyChanges(event) {
      const element = event.target;
      const ref = element.getAttribute('data-sync-changes');
      const value = element.value || element.textContent;
      const msg = {
        type: 'syncChanges',
        settings: {ref, value}
      };
      document.getElementById('me-preview').contentWindow.postMessage(msg);
    }
    Drupal.behaviors.mercuryEditorSyncChanges = {
      attach: function attach(context, settings) {
        once('me-close-builder', '[data-sync-changes]', context).forEach((e) => {
          e.addEventListener('input', applyChanges);
          e.addEventListener('change', applyChanges);
        });
      }
    };
  })(Drupal, once);

})();
