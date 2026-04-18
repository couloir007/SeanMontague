((Drupal, once) => {
  Drupal.behaviors.mercuryEditorNodeForm = {
    attach: function(context, _settings) {
      if (!once('me-node-form', '.me-node-form', context)) {
        return;
      }
      // Add a class to the form if an input, textarea, or select is changed.
      document.querySelector('form.me-node-form').addEventListener('change', (e) => {
        e.target.closest('form').classList.add('unsaved-changes');
      });
      // Warn the user if attempting to leave the page with unsaved changes.
      window.addEventListener('beforeunload', (e) => {
        if (document.querySelector('.me-node-form.unsaved-changes')) {
          e.preventDefault();
          e.returnValue = '';
        }
      });
    }
  }
})(Drupal, once)
