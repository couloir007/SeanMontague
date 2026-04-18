(function () {
  'use strict';

  ((Drupal, drupalSettings, once) => {
    /**
     * Collection of functions to respond to postMessage events.
     */
    const iFrameActions = {
      /**
       * Ajax click handler for Layout Paragraphs UI elements in iframe.
       * @param {Object} settings The ajax settings.
       */
      drupalAjax: function (settings) {
        Drupal.ajax(settings).execute();
      },
      /**
       *
       * @param {Object} data
       */
      syncChanges: function (data) {
        const {ref, value} = data;
        document.querySelector(`[data-sync-changes="${ref}"]`).innerHTML = value;
      },
      /**
       * Runs a set of ajax commands.
       * @param {Object} data The ajax commands.
       */
      ajaxCommands: function(data) {
        const {commands, status} = data;
        const ajaxObj = Drupal.ajax({url: ''});
        const ajaxCommands = new Drupal.AjaxCommands();
        Object.keys(commands || {}).reduce(function (executionQueue, key) {
          return executionQueue
            .then(function () {
              var command = commands[key].command;
              if (command && ajaxCommands[command]) {
                return ajaxCommands[command](ajaxObj, commands[key], status);
              }
            })
            .catch(console.error);
        }, Promise.resolve());
      },
      ajaxPreviewPageState: function (pageState) {
        drupalSettings.ajaxPreviewPageState = pageState;
      },
      onCloseMercuryDialog: function () {
        document.querySelectorAll('.is-me-focused').forEach((el) => {
          el.focus();
          el.classList.remove('is-me-focused');
        });
      }
    };
    Drupal.behaviors.mercuryEditorPostMessagesListener = {
      attach: function(context, _settings) {
        // Listen for window.postMessage() to handle iFrame behavior.
        if (once('me-msg-listener', 'html').length) {
          window.addEventListener("message", (e) => {
            if (iFrameActions[e.data.type]) {
              iFrameActions[e.data.type](e.data.settings);
            }
          });
        }
      }
    };
  })(Drupal, drupalSettings, once);

})();
