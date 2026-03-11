/**
 * Sets the fullscreen progress indicator.
 */
Drupal.Ajax.prototype.setProgressIndicatorMercuryeditor = function () {
  const el = Drupal.theme('mercuryAjaxSpinner');
  document.body.append(el);
  this.progress.element = el;
};

Drupal.theme.mercuryAjaxSpinner = function () {
  const el = document.createElement('div');
  el.classList.add('me-ajax-progress');
  el.innerHTML = '<div class="spinner"></div>';
  if (window.frameElement) {
    if (window.frameElement.closest('mercury-dialog')) {
      const main = window.frameElement.closest('mercury-dialog').shadowRoot.querySelector('main');
      const offsetX = main.scrollLeft;
      const offsetY = main.scrollTop;
      const {width, height} = main.getBoundingClientRect();
      const spinner = el.querySelector('.spinner');
      spinner.style.left = offsetX + (width/2) + 'px';
      spinner.style.top = offsetY + (height/2) + 'px';
    }
  }
  return el;
}
