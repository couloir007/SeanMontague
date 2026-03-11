((Drupal) => {
  /**
   * Post a message to the preview iframe.
   * @param {Object} msg The message to post.
   */
  function postMessage(msg) {
    document.getElementById('me-preview').contentWindow.postMessage(msg);
  }
  /**
   * Ajax command wrapper to send a set of ajax commands to iFrame.
   * @param {*} ajax The ajax object.
   * @param {*} response The ajax response.
   * @param {*} status The ajax status.
   */
  Drupal.AjaxCommands.prototype.mercuryEditorEditIframeCommandsWrapper = function (ajax, response, status) {
    postMessage({
      type: 'ajaxCommands',
      settings: {commands: response.commands, status}
    });
  }
})(Drupal)
