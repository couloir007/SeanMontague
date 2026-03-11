/**
 * @file
 * JavaScript behaviors for demo admin.
 */

((Drupal, once) => {
  /**
   * Settings element example.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgDemoMercuryEditorDialog = {
    attach: function attach(context) {
      once(
        'schemadotorg-mercury-editor-header',
        '.me-node-form__header',
        context,
      ).forEach((element) => {
        const header = element;
        const editor = element.parentNode;

        // Move status, dates, author, and revisions to top of the sidebar.
        [
          '.field--name-schema-cm-documentation',
          '.entity-meta__header',
          '.field--name-moderation-state',
          'schemadotorg-devel-generate-button',
        ]
          .reverse()
          .forEach((selector) => {
            const node = editor.querySelector(selector);
            if (node) {
              header.after(node);
            }
          });

        // Move delete button to the bottom of the sidebar.
        const meta = editor.querySelector('.entity-meta');
        const actions = editor.querySelector('.form-actions');
        if (actions) {
          meta.appendChild(actions);
        }
      });
    },
  };
})(Drupal, once);
