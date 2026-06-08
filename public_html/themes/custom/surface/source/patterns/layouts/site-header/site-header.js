/**
 * Adjusts the site header position and scroll state after the DOM is ready.
 *
 * This accounts for Drupal administration UI elements that may be fixed or
 * partially visible at the top of the viewport, ensuring the site header does
 * not overlap them.
 */
document.addEventListener('DOMContentLoaded', () => {
  /**
   * Main site header element.
   *
   * If the header is not present on the page, no behavior is applied.
   */
  const header = document.querySelector('.site-header');
  if (!header) return;

  /**
   * Drupal toolbar container.
   *
   * This toolbar is fixed to the top of the viewport when present.
   */
  const toolbarBar = document.querySelector('#toolbar');

  /**
   * Drupal administration tray.
   *
   * When active, this tray contributes additional fixed height above the page.
   */
  const adminTray = document.querySelector('#toolbar-item-administration-tray');

  /**
   * Gin theme secondary toolbar shown on the frontend.
   *
   * Unlike the standard Drupal toolbar, this element may scroll out of view,
   * so its visible bottom edge is used when calculating header offset.
   */
  const ginToolbar = document.querySelector('.gin-secondary-toolbar--frontend');

  /**
   * Updates the header state and vertical position.
   *
   * Adds the `scrolled` class once the page has been scrolled beyond 60px.
   * Calculates the total vertical space occupied by Drupal toolbar elements
   * and applies that value to the header's `inset-block-start` style.
   */
  function updateHeader() {
    header.classList.toggle('scrolled', window.scrollY > 40);

    /**
     * Total height occupied by fixed Drupal toolbar elements.
     */
    let fixedHeight = 0;

    if (toolbarBar) {
      fixedHeight += toolbarBar.offsetHeight;
    }

    if (adminTray && adminTray.classList.contains('is-active')) {
      fixedHeight += adminTray.offsetHeight;
    }

    if (ginToolbar) {
      /**
       * Current bottom position of the Gin toolbar relative to the viewport.
       *
       * This represents how much vertical space the toolbar is still occupying
       * while it scrolls out of view.
       */
      const ginBottom = ginToolbar.getBoundingClientRect().bottom;

      header.style.insetBlockStart = `${Math.max(fixedHeight, ginBottom)}px`;
    } else {
      header.style.insetBlockStart = `${fixedHeight}px`;
    }
  }

  /**
   * Run once immediately so the header is correctly positioned on page load.
   */
  updateHeader();

  /**
   * Recalculate the header position as the user scrolls.
   *
   * The listener is passive because it does not call `preventDefault()`.
   */
  window.addEventListener('scroll', updateHeader, { passive: true });

  /**
   * Recalculate the header position when the viewport changes size.
   */
  window.addEventListener('resize', updateHeader, { passive: true });

  /**
   * Recalculate when the Drupal toolbar tray is opened, closed, or changed.
   */
  document.addEventListener('drupalToolbarTrayChange', updateHeader);
});
