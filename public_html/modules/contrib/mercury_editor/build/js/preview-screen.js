(function () {
  'use strict';

  ((Drupal, drupalSettings, $, once) => {

    /**
     * Adds the me_id GET parameter to a URL.
     * @param {String} url
     * @returns {String} The url with the me_id GET parameter.
     */
    function addPreviewParam(url) {
      const mercuryEditorId = drupalSettings.mercuryEditor?.id || null;
      if (!mercuryEditorId) {
        return url;
      }
      const urlObj = new URL(url, window.location.origin);
      if (urlObj.origin !== window.location.origin) {
        return url;
      }
      urlObj.searchParams.set('me_id', mercuryEditorId);
      return urlObj.toString();
    }

    // Intercept all XMLHttpRequests to add me_id GET parameter.
    const originalOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function(method, url, async, user, password) {
      return originalOpen.call(this, method, addPreviewParam(url), async, user, password);
    };

    // Intercept all fetch requests to add me_id GET parameter.
    const originalFetch = window.fetch;
    window.fetch = function(input, init) {
      let url = typeof input === 'string' ? input : input.url;
      if (url) {
        url = addPreviewParam(url);
      }
      return originalFetch(url || input, init);
    };

    /**
     * Prevent a click.
     */
    function preventDefault(e) {
      e.stopPropagation();
      e.preventDefault();
      return false;
    }

    /**
     * Uses window.postMessage() to send UI clicks to the parent window.
     *
     * @param Event e
     *   The click event.
     */
    function lpbClickHander(e) {
      // First, send the ajaxPageState to the parent window.
      window.parent.postMessage({
        type: 'ajaxPreviewPageState',
        settings: drupalSettings.ajaxPageState
      });
      // Remove "is-me-focused" class from all elements.
      document.querySelectorAll('.is-me-focused').forEach((el) => {
        el.classList.remove('is-me-focused');
      });
      // Add "is-me-focused" class to the clicked element.
      e.currentTarget.classList.add('is-me-focused');
      // Then, send the click event.
      const message = {
        type: 'drupalAjax',
        settings: {
          dialogType: e.currentTarget.getAttribute('data-dialog-type'),
          dialog: JSON.parse(e.currentTarget.getAttribute('data-dialog-options')),
          dialogRenderer: JSON.parse(e.currentTarget.getAttribute('data-dialog-renderer')),
          url: addPreviewParam(e.currentTarget.getAttribute('href')),
        }
      };
      window.parent.postMessage(message);
      e.stopPropagation();
      e.preventDefault();
      return false;
    }

    function scaleMirror(e) {
      const mirror = document.querySelector('.gu-mirror');
      if (!mirror) {
        return;
      }
      const scaleAxis = mirror.offsetWidth > mirror.offsetHeight ? 'offsetHeight' : 'offsetWidth';
      const scale = Math.min(300 / mirror[scaleAxis], 1);
      const boundingRect = mirror.getBoundingClientRect();
      const diffX = e.clientX - boundingRect.x;
      const diffY = e.clientY - boundingRect.y;
      mirror.style.setProperty('transform-origin', `${diffX}px ${diffY}px`);
      mirror.style.setProperty('transform', `scale(${scale})`);
      window.removeEventListener('mousemove', scaleMirror);
    }

    /**
     * Simplifies drag and drop visual cues to prevent jumpiness.
     *
     * The default behavior of the dragula library can create excessive
     * jumpiness in some cases. This function simplifies the UI and drag and drop
     * experience in several key ways, including:
     *
     * - Detaches all layout paragraphs UI elements when dragging starts.
     * - Provides a simple "hint" element to show where an item will be dropped.
     * - Leaves a "ghost" copy of the grabbed element in place at the source.
     * - Reattaches all UI elements when dragging ends.
     *
     * @see https://github.com/bevacqua/dragula#drakeon-events.
     *
     * @param {Object} drake
     *   The dragula object.
     */
    function simplifyDragHints($builder, drake) {

      let ghost, grabbed;
      const hint = $('<div class="lp-hint hidden"></div>')[0];
      // Scales the mirror element to make it easier to drag.
      drake.on('cloned', (clone, original, type) => {
        window.addEventListener('mousemove', scaleMirror);
      });
      // Hide UI elements when dragging starts.
      drake.on('drag', (el) => {
        if (el.parentNode) {
          el.parentNode.insertBefore(hint, el);
        }
        $builder.find('.js-lpb-ui').addClass('hidden');
      });
      // Provide a simple hint element to indicate where an item will be dropped.
      drake.on('shadow', (shadow, container, src) => {

        if (shadow.classList.contains('lp-hint')) {
          return;
        }

        hint.style = {
          width: '',
          height: '',
          marginLeft: '',
          marginTop: '',
        };

        const sibling = shadow.nextElementSibling || shadow.previousElementSibling;
        const orientation = sibling && shadow.getBoundingClientRect().top === sibling.getBoundingClientRect().top
            ? 'vertical'
            : 'horizontal';

        if (orientation == 'horizontal') {
          const offset = parseInt(window.getComputedStyle(shadow.parentNode).getPropertyValue('padding-left'));
          hint.style.marginLeft = '-' + offset + 'px';
        }

        if (orientation == 'vertical') {
          const offset = parseInt(window.getComputedStyle(shadow.parentNode).getPropertyValue('padding-top'));
          hint.style.marginTop = '-' + offset + 'px';
        }

        if (orientation === 'vertical') {
          hint.style.height = shadow.parentNode.clientHeight + 'px';
        }
        if (orientation === 'horizontal') {
          hint.style.width = shadow.parentNode.clientWidth + 'px';
        }

        hint.setAttribute('data-orientation', orientation);

        // Remove comments and text nodes from container.
        [...container.childNodes].filter((e) => e.classList === undefined).forEach((e) => e.remove());
        container.replaceChild(hint, shadow);

        // Ensure the hint does not get at the end of the region after the add button.
        if (hint.nextSibling === null && hint.previousSibling !== null && hint.previousSibling.classList.contains('lpb-btn--add')) {
          container.insertBefore(hint, hint.previousSibling);
        }

        const nextIsGhost = hint.nextSibling !== null ? hint.nextSibling.classList.contains('lp-ghost') : false;
        const prevIsGhost = hint.previousSibling !== null ? hint.previousSibling.classList.contains('lp-ghost') : false;
        const ghostAdjacent = nextIsGhost || prevIsGhost;
        if (ghostAdjacent) {
          hint.classList.add('hidden');
          ghost.classList.remove('gu-transit');
        }
        else {
          hint.classList.remove('hidden');
          ghost.classList.add('gu-transit');
        }
      });
      // Leave a copy of the grabbed item in place at the original source.
      drake.on('cloned', (mirror, item) => {
        ghost = item.cloneNode(true);
        ghost.classList.add('lp-ghost');
        if (item.parentNode) {
          item.parentNode.insertBefore(ghost, item);
        }
        grabbed = item;
        item.remove();
      });
      // Show UI elements and remove ghost and hint elements when dragging stops.
      drake.on('dragend', (el) => {
        hint.replaceWith(grabbed);
        ghost.remove();
        $builder.find('.js-lpb-ui').removeClass('hidden');
        // Copied from layout_paragraphs/js/builder.js
        // @todo Remove when resolved:
        // https://www.drupal.org/project/layout_paragraphs/issues/3392717
        $builder[0]
          .querySelectorAll('.lpb-btn--add.center')
          .forEach((buttonElement) => {
            const regionElement = buttonElement.closest('.js-lpb-region');
            if (regionElement?.querySelector('.js-lpb-component')) {
              buttonElement.style.display = 'none';
            } else {
              buttonElement.style.display = 'block';
            }
          });
      });
    }

    /**
     * Calls simplifyDragHints() when the builder is initialized.
     */
    $(document).on('lpb-builder:init', (e) => {
      const builder = e.target;
      const drake = $(builder).data('drake');
      if (drake) {
        simplifyDragHints($(builder), drake);
      }
    });

    function padElements(layout) {
      [...layout.querySelectorAll('[data-region]'), layout].forEach((el) => {
        const computed = getComputedStyle(el);
        if (!el.hasAttribute('data-me-padding')) {
          el.setAttribute('data-me-padding', `${computed.paddingTop} ${computed.paddingRight} ${computed.paddingBottom} ${computed.paddingLeft}`);
        }

        const defaultRolloverPaddingBlock = 10;
        const defaultRolloverPaddingInline = 0;

        let rolloverPaddingBlock = drupalSettings.mercuryEditor.rolloverPaddingBlock ?? defaultRolloverPaddingBlock;
        let rolloverPaddingInline = drupalSettings.mercuryEditor.rolloverPaddingInline ?? defaultRolloverPaddingInline;

        el.style.paddingTop = Math.max(rolloverPaddingBlock, parseInt(computed.paddingTop)) + 'px';
        el.style.paddingRight = Math.max(rolloverPaddingInline, parseInt(computed.paddingRight)) + 'px';
        el.style.paddingBottom = Math.max(rolloverPaddingBlock, parseInt(computed.paddingBottom)) + 'px';
        el.style.paddingLeft = Math.max(rolloverPaddingInline, parseInt(computed.paddingLeft)) + 'px';
      });
    }

    function unpadElements(layout) {
      [...layout.querySelectorAll('[data-region]'), layout].forEach((el) => {
        if (el.hasAttribute('data-me-padding')) {
          getComputedStyle(el);
          el.style.padding = el.getAttribute('data-me-padding');
        }
      });
    }

    function showControls(e) {
      const el = e.target.closest('.lpb-controls, .js-lpb-component');
      el.classList.add('focused');
      el.classList.remove('transitioning');
      el.classList.remove('blurred');
    }

    function hideControls(e) {
      const el = e.target.closest('.lpb-controls, .js-lpb-component');
      el.classList.add('transitioning');
      setTimeout(() => {
        if (el.classList.contains('transitioning')) {
          el.classList.remove('focused');
          el.classList.remove('transitioning');
          el.classList.add('blurred');
        }
      }, 250);
    }

    /**
     * Attaches the behavior to the edit screen.
     */
    Drupal.behaviors.mercuryEditorPreviewScreen = {
      attach: function(context, _settings) {
        const duplicateContainers = [...document.querySelectorAll('[data-me-edit-screen-key]')]
          .map((container) => container.getAttribute('data-me-edit-screen-key'))
          // check for duplicates in array
          .filter((value, index, self) => self.indexOf(value) !== index);
        if (duplicateContainers.length > 0) {
          console.error('Multiple HTML elements found using the same data attribute, "data-me-edit-screen-key", which should be unique. Make sure attributes are not passed to child elements in twig templates.', duplicateContainers);
        }
        // Send the initial ajaxPageState to the parent window.
        window.parent.postMessage({
          type: 'ajaxPreviewPageState',
          settings: drupalSettings.ajaxPageState
        });
        // Attaches click handlers to links that use window.postMessage().
        once('me-msg-broadcaster', '.js-lpb-ui.use-ajax, .js-lpb-ui .use-ajax').forEach((el) => {
          $(el).off();
          el.addEventListener('mousedown', preventDefault);
          el.addEventListener('mouseup', preventDefault);
          el.addEventListener('click', lpbClickHander);
        });
        // Prevent links from working in iframe.
        if (window.parent !== window) {
          once('me-stop-iframed-links', 'a', context).forEach((link) => {
            if (link.closest('.lpb-controls') === null) {
              link.setAttribute('target', '_parent');
              link.addEventListener('click', (e) => {
                e.stopPropagation();
                e.preventDefault();
                return false;
              });
            }
            else {
              // The dragula library prevents links from automatically focusing
              // on mousedown, which can cause issues with keyboard navigation.
              link.addEventListener('mousedown', (e) => e.target.focus());
            }
          });
          once('me-prevent-focus', 'a, button, input, textarea, select, details', context).forEach((focussable) => {
            if (
              focussable.closest('.lpb-controls') === null &&
              focussable.closest('.mercury-editor-ui') === null &&
              !focussable.classList.contains('use-postmessage')
            ) {
              focussable.setAttribute('tabindex', '-1');
            }
          });
          once('me-layout-hover', '.lpb-layout').forEach((layout) => {
            layout.addEventListener('mouseenter', (e) => {
              e.target.setAttribute('data-mouseover', 'true');
              setTimeout(() => {
                if (e.target.getAttribute('data-mouseover')) {
                  padElements(e.target);
                }
              }, 100);
            });
            layout.addEventListener('mouseleave', (e) => {
              e.target.removeAttribute('data-mouseover');
              setTimeout(() => {
                if (!e.target.getAttribute('data-mouseover')) {
                  unpadElements(e.target);
                }
              }, 100);
            });
          });
        }
        once('reveal-on-hover', '.js-lpb-component').forEach((component) => {
          component.addEventListener('mouseenter', showControls);
          component.addEventListener('mouseleave', hideControls);
        });
        once('reveal-on-hover', '.lpb-controls').forEach((el) => {
          el.addEventListener('mouseenter', showControls);
          el.addEventListener("focusin", showControls);
          el.addEventListener('mouseleave', hideControls);
          el.addEventListener("focusout", hideControls);
        });
      }
    };
  })(Drupal, drupalSettings, jQuery, once);

})();
