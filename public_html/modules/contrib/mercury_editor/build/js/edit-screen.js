(function () {
  'use strict';

  ((Drupal, drupalSettings, $, once) => {
    // The width of the sidebar.
    let sidebarWidth;
    // The state of the sidebar.
    let sidebarState = 'open';
    /**
     * Set the size of the preview iframe.
     * @param string w The width of the preview iframe.
     * @param string h The height of the preview iframe.
     */
    function setPreviewViewportSize(w, h) {
      const iframe = document.querySelector('#me-preview');
      if (w) {
        iframe.style.width = w;
      } else {
        iframe.style.removeProperty('width');
      }
      if (h) {
        iframe.style.height = h;
      } else {
        iframe.style.removeProperty('height');
      }
    }
    $(window).on('dialog:afterclose', (e, dialog, $element) => {
      if (($element[0] || {}).tagName == 'MERCURY-DIALOG') {
        document.getElementById('me-preview').contentWindow.postMessage({type: 'onCloseMercuryDialog'});
      }
    });
    /**
     * Save the content.
     * @param {*} e The event.
     */
    function save(e) {
      const saveBtn = document.querySelector('[data-drupal-selector="edit-submit"]:not([disabled])');
      if (saveBtn) {
        const inputs = saveBtn.closest('form').querySelectorAll('input, textarea, select') || [];
        // Checks visible form inputs for validity errors.
        const invalid = Array.from(inputs).filter((input) => !!( input.offsetWidth || input.offsetHeight || input.getClientRects().length ) && !input.validity.valid);
        if (invalid.length) {
          invalid[0].focus();
          invalid[0].reportValidity();
        } else {
          saveBtn.dispatchEvent(new Event('mousedown'));
        }
      }
    }
    /**
     * Done editing.
     * @param {*} e The event.
     */
    function done(e) {
      const redirectUrl = (document.querySelector('.me-edit-screen-redirect-url') ?? {}).value;
      window.location.href = redirectUrl;
      return false;
    }

    /**
     * Initialize the toolbar.
     */
    function initToolbar() {
      function mobileViewport() {
        const presetsSelect = document.querySelector('.me-mobile-presets');
        if (presetsSelect) {
          const preset = presetsSelect.options[presetsSelect.selectedIndex ?? 0].value;
          const presetValues = preset.split('x');
          setPreviewViewportSize(presetValues[0] + 'px', Math.min(presetValues[1], window.innerHeight - document.getElementById('me-toolbar').offsetHeight - 20 ) + 'px');
        }
        else {
          setPreviewViewportSize('390px', Math.min('844', window.innerHeight - document.getElementById('me-toolbar').offsetHeight - 20 ) + 'px');
        }
      }
      const presetsSelect = document.querySelector('.me-mobile-presets');
      if (presetsSelect) {
        presetsSelect.addEventListener('change', mobileViewport);
      }
      document.querySelector('#me-mobile-toggle-btn').addEventListener('click', (e) => {
        if (presetsSelect) {
          presetsSelect.style.display = 'block';
        }
        mobileViewport();
        window.addEventListener('resize', mobileViewport);
        e.preventDefault();
        e.stopPropagation();
        return false;
      });
      document.querySelector('#me-desktop-toggle-btn').addEventListener('click', (e) => {
        if (presetsSelect) {
          presetsSelect.style.display = 'none';
        }
        window.removeEventListener('resize', mobileViewport);
        setPreviewViewportSize('100%', '100%');
        e.preventDefault();
        e.stopPropagation();
        return false;
      });
      const saveBtn = document.querySelector('[data-drupal-selector="edit-submit"]:not([disabled])');
      if (saveBtn) {
        document.querySelector('#me-save-btn').addEventListener('click', save);
      }
      else {
        document.querySelector('#me-save-btn').remove();
      }
      document.querySelector('#me-done-btn').addEventListener('click', done);

      // Store the default width of the dock.
      if (drupalSettings.mercuryEditor && drupalSettings.mercuryEditor.width) {
        localStorage.setItem('mercury-dialog-dock-default-width', drupalSettings.mercuryEditor.width);
      }

      let isTrayCollapsed = localStorage.getItem('mercury-dialog-dock-collapsed') === 'true';
      sidebarState = isTrayCollapsed ? 'closed' : 'open';

      const sidebarToggle = document.querySelector('#me-sidebar-toggle-btn');
      sidebarToggle.addEventListener('click', (e) => {
        if (sidebarState === 'open') {
          // When closing the sidebar, set the width to 10px.
          document.documentElement.style.setProperty('--me-dialog-dock-width', '10px');
          localStorage.setItem('mercury-dialog-dock-collapsed', 'true');
        }
        else {
          // When re-opening the sidebar, set the width to the default width.
          sidebarWidth = localStorage.getItem('mercury-dialog-dock-default-width');
          if (sidebarWidth) {
            document.documentElement.style.setProperty('--me-dialog-dock-width', `${sidebarWidth}px`);
          }
        }
        e.preventDefault();
        e.stopPropagation();
        return false;
      });

      // Listen for dock resize events.
      document.addEventListener('mercury:dockResize', (e) => {
        // The width of the resize event.
        let width = e.detail.width;

        // If width is greater than 10, the dock is open.
        if (width > 10) {
          sidebarState = 'open';
          sidebarToggle.classList.remove('me-button--sidebar-expand');
          sidebarToggle.classList.add('me-button--sidebar-collapse');
          sidebarToggle.innerHTML = `<span>${Drupal.t('Hide sidebar')}</span>`;
          sidebarToggle.setAttribute('title', Drupal.t('Hide sidebar'));
          localStorage.removeItem('mercury-dialog-dock-collapsed');
        }
        else {
          sidebarState = 'closed';
          sidebarToggle.classList.remove('me-button--sidebar-collapse');
          sidebarToggle.classList.add('me-button--sidebar-expand');
          sidebarToggle.innerHTML = `<span>${Drupal.t('Show sidebar')}</span>`;
          sidebarToggle.setAttribute('title', Drupal.t('Show sidebar'));
          localStorage.setItem('mercury-dialog-dock-collapsed', 'true');
        }

        localStorage.setItem('mercury-dialog-dock-width', width);
      });
    }
    /**
     * Toggles mouse pointer events on the preview iFrame.
     *
     * When dragging the dialog border, the iFrame intercepts mouse events and
     * prematurely stops the drag behavior. Toggleing pointer events prevents
     * this behavior.
     *
     * @param {Event} e
     *   The mouseup or mousedown event
     */
    function iFramePointerEventsToggle(e) {
      const iframe = document.querySelector('#me-preview');
      if (iframe) {
        iframe.style.pointerEvents = e.type == 'mouseup' ? 'auto' : 'none';
      }
    }
    /**
     * Attaches the behavior to the edit screen.
     */
    Drupal.behaviors.mercuryEditorEditScreen = {
      attach: function(context, _settings) {
        // Move focus to the first input with error, if any.
        const firstError = once('me-first-error', '.js-form-item.error', context)[0];
        if (firstError) {
          firstError.focus();
          firstError.scrollIntoView({
            behavior:'smooth',
          });
        }
        // Initialize the toolbar.
        if (once('me-toolbar', '#me-toolbar', context).length) {
          initToolbar();
        }
        // Open the edit tray dialog.
        if (once('me-edit-tray', '#me-edit-screen', context).length) {
          // Opens the dialog and attaches event listeners.
          const mercuryDialogElement = context.querySelector('#me-edit-screen');
          if (mercuryDialogElement) {
            const mercuryDialog = Drupal.mercuryDialog(mercuryDialogElement);
            mercuryDialog.show();
            // Attaches a custome beforeSerialize() method to Drupal.Ajax.
            // This method adds the ajaxPreviewPageState to the ajax request.
            if (typeof Drupal.Ajax !== 'undefined' && typeof Drupal.Ajax.prototype.beforeSerializeMercuryEditor === 'undefined') {
              Drupal.Ajax.prototype.beforeSerializeMercuryEditor = Drupal.Ajax.prototype.beforeSerialize;
              Drupal.Ajax.prototype.beforeSerialize = function (element, options) {
                this.beforeSerializeMercuryEditor.apply(this, arguments);
                const pageState = drupalSettings.ajaxPreviewPageState || {};
                options.data['ajax_preview_page_state[theme]'] = pageState.theme;
                options.data['ajax_preview_page_state[theme_token]'] = pageState.theme_token;
                options.data['ajax_preview_page_state[libraries]'] = pageState.libraries;
              };
            }
            // Remove min-width from the dialog.
            // @todo Refactor once dialog min-width is addressed.
            // var style = document.createElement( 'style' )
            // style.innerHTML = 'dialog { min-width: 0 !important; }'
            // mercuryDialogElement.shadowRoot.appendChild( style );
            // @todo Refactor if we have drag events from dialog.
            document.addEventListener('mousedown', iFramePointerEventsToggle);
            document.addEventListener('mouseup', iFramePointerEventsToggle);
          }
        }
        // Set the iframe URL once other js files have loaded.
        if (once('me-preview-iframe', '#me-preview', context).length) {
           const iframe = document.querySelector('#me-preview');
           iframe.src = iframe.getAttribute('data-src');
        }
      }
    };
  })(Drupal, drupalSettings, jQuery, once);

})();
