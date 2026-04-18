(function ($, Drupal, drupalSettings) {
  // TODO: This does not seem to be used, but keeping for reference.
  // drupalSettings.mercuryDialog = {
  //   autoOpen: true,
  //   autoResize: false,
  //   dialogClass: '',
  //   buttonClass: 'button',
  //   buttonPrimaryClass: 'button--primary',
  // };

  function dispatchDialogEvent(eventType, dialog, element, settings) {
    // Use the jQuery if the DrupalDialogEvent is not defined for BC.
    if (typeof DrupalDialogEvent === 'undefined') {
      $(window).trigger(`dialog:${eventType}`, [dialog, $(element), settings]);
    } else {
      const event = new DrupalDialogEvent(eventType, dialog, settings || {});
      element.dispatchEvent(event);
    }
  }

  Drupal.mercuryDialog = function (element, options) {
    var undef;
    var $element = $(element);
    // Override the jQuery dialog method to return the element, preventing
    // uncaught errors from core jQuery dialog being thrown.
    $element.dialog = () => $element;
    var dialogElement;
    var dialog = {
      open: false,
      returnValue: undef,
    };

    /**
     * ResizeObserver callback that runs when the shadow dialog element resizes.
     *
     * @param {ResizeObserverEntry[]} entries
     */
    function onDockResize(entries) {
      for (const entry of entries) {
        const element = entry.target;
        const dockDirection = element.getAttribute('data-dock');
        if (!element.open || !dockDirection || dockDirection == 'none') {
          return;
        }

        // Dispatch custom event with resize data
        const resizeEvent = new CustomEvent('mercury:dockResize', {
          detail: {
            width: entry.contentRect.width,
            height: entry.contentRect.height
          },
          bubbles: true
        });

        dialogElement.setAttribute('width', entry.contentRect.width);
        dialogElement.dispatchEvent(resizeEvent);
      }
    }

    const dockObserver = new ResizeObserver(onDockResize);

    /**
     * MutationObserver callback that will start the resize observer when the dialog opens.
     *
     * @param {MutationRecord[]} entries
     */
    function onDialogMutate(records) {
      // @todo: We may only want to attach this if the dialog is transitioning to an open state.
      dockObserver.observe(dialogElement.shadowRoot.querySelector('dialog'));
    }

    const dialogMutationObserver = new MutationObserver(onDialogMutate);

    /**
     * Converts a numeric only value to a px based CSS value.
     * @param {*} value
     * @returns {string}
     *   A CSS length value.
     */
    function getCSSLength(value) {
      if (typeof value === 'undefined' || !/^\d+$/.test(value)) {
        return value;
      }
      return `${value}px`;
    }

    /**
     * Apply options to the <mercury-dialog> element.
     *
     * @param {Element} dialogElement
     * @param {Object} options
     */
    function applyOptions(dialogElement, options) {
      const attributeOptions = [
        'title',
        'modal',
        'dock',
        'push',
        'resizable',
        'moveable',
      ];

      // Set these options as HTML attributes on the element.
      attributeOptions.forEach(option => {
        if (typeof options[option] !== 'undefined') {
          dialogElement.setAttribute(option, options[option]);
        }
      });

      if (options.dialogClass) {
        dialogElement.classList.add(...options.dialogClass.split(' '));
      }

      if (dialogElement.id === 'me-edit-screen') {
        // Options for the main Mercury Editor tray.
        let isTrayCollapsed = localStorage.getItem('mercury-dialog-dock-collapsed') === 'true';
        let savedWidth = localStorage.getItem('mercury-dialog-dock-width');
        let savedHeight = localStorage.getItem('mercury-dialog-dock-height');

        if (!isTrayCollapsed) {
          let dialogWidth = savedWidth || options.width;
          let dialogHeight = savedHeight || options.height;

          if (dialogWidth) {
            dialogElement.setAttribute('width', dialogWidth);
            document.documentElement.style.setProperty('--me-dialog-dock-width', getCSSLength(dialogWidth));
          }

          if (dialogHeight) {
            dialogElement.setAttribute('height', dialogHeight);
            document.documentElement.style.setProperty('--me-dialog-dock-height', getCSSLength(dialogHeight));
          }
        }
        else {
          dialogElement.setAttribute('width', '10');
          document.documentElement.style.setProperty('--me-dialog-dock-width', '10px');
        }
      }
      else {
        // Options for all other dialogs.
        if (options.width) {
          dialogElement.setAttribute('width', options.width);
          document.documentElement.style.setProperty('--me-dialog-width', getCSSLength(options.width));
        }

        if (options.height) {
          dialogElement.setAttribute('height', options.height);
          document.documentElement.style.setProperty('--me-dialog-height', getCSSLength(options.height));
        }
      }

      // TODO: Determine if we need to persist the width and height of docked dialogs.
      // if (options.dock) {
      //   if (savedWidth && ['right', 'left'].includes(options.dock)) {
      //     dialogElement.setAttribute('width', savedWidth);
      //   }
      //   if (savedHeight && ['top', 'bottom'].includes(options.dock)) {
      //     dialogElement.setAttribute('height', savedHeight);
      //   }
      // }

      if (options.drupalAutoButtons && !options.buttons) {
        options.buttons = Drupal.behaviors.mercuryDialog.prepareDialogButtons($(dialogElement));
      }

      if (options.buttons && options.buttons.length) {
        _createButtonPane(options.buttons);
      }

      return dialogElement;
    }

    /**
     * Determines which element to append the dialog to. Defaults to the <body>
     * @returns Element
     */
    function _appendTo() {
      var element = options.appendTo;
      if (element && (element.jquery || element.nodeType)) {
        return $(element);
      }
      return $(document).find(element || "body").eq(0);
    }
    /**
     * Create a button pane similar to jQuery UI dialog.
     */
    function _createButtonPane(buttons) {
      const existing = dialogElement.querySelector('.me-dialog__buttonpane');
      if (existing) {
        existing.remove();
      }
      const uiDialogButtonPane = document.createElement('div');
      uiDialogButtonPane.setAttribute('slot', 'footer');
      uiDialogButtonPane.classList.add('me-dialog__buttonpane');
      dialogElement.appendChild(uiDialogButtonPane);
      if ($.isEmptyObject(buttons) || (Array.isArray(buttons) && !buttons.length)) {
        return;
      }
      buttons.forEach((props) => {
        const button = document.createElement('button');
        button.classList = props.class;
        button.classList.add('button');
        button.appendChild(document.createTextNode(props.text));
        button.addEventListener('click', props.click);
        uiDialogButtonPane.appendChild(button);
      });
    }
    function init(settings) {
      // Wrap the element in a <mercury-dialog> if it isn't already.
      if (element.tagName !== 'MERCURY-DIALOG') {
        const wrapper = $('<mercury-dialog>').append($element).appendTo(_appendTo());
        dialogElement = wrapper[0];
      } else {
        dialogElement = element;
      }
      applyOptions(dialogElement, settings);
    }

    /**
     * Initializes and opens a dialog element.
     * @param {Object} settings
     *   Dialog settings mimicing jQuery UI for compatability.
     */
    function openDialog(settings) {
      settings = { ...drupalSettings.dialog, ...drupalSettings.mercuryEditor, ...options, ...settings };
      dispatchDialogEvent('beforecreate', dialog, $element.get(0), settings);
      init(settings);
      dialogElement[settings.modal ? 'showModal' : 'show']();
      // Set autoResize to false to prevent Drupal core's jQuery dialog from
      // attemping to resize, which would throw an error.
      const originalResizeSetting = settings.autoResize;
      settings.autoResize = false;
      dispatchDialogEvent('aftercreate', dialog, $element.get(0), settings);
      settings.autoResize = originalResizeSetting;
      // Add a mutation observer to the dialog element.
      dialogMutationObserver.observe(dialogElement, { childList: true, attributes: true });
      dialogElement.addEventListener('close', function () {
        closeDialog();
      });
    }

    function closeDialog(value) {
      dispatchDialogEvent('beforeclose', dialog, $element.get(0));
      // Stop observing height and width changes.
      dockObserver.disconnect();
      dialogMutationObserver.disconnect();
      Drupal.detachBehaviors(element, null, 'unload');
      element.close();
      dialog.returnValue = value;
      dispatchDialogEvent('afterclose', dialog, $element.get(0));
      $element.remove();
    }

    dialog.show = function () {
      openDialog({
        modal: false,
      });
    };

    dialog.showModal = function () {
      openDialog({
        modal: true,
      });
    };

    dialog.applyOptions = function (options) {
      init(options);
    };

    dialog.close = closeDialog;
    return dialog;
  };

  Drupal.behaviors.mercuryDialog = {
    attach: function (context, settings) {
      // Provide a known 'drupal-mercury-dialog' DOM element for Drupal-based modal
      // dialogs. Non-modal dialogs are responsible for creating their own
      // elements, since there can be multiple non-modal dialogs at a time.

      if (!$('#drupal-mercury-dialog').length) {
        // Add 'ui-front' jQuery UI class so jQuery UI widgets like autocomplete
        // sit on top of dialogs. For more information see
        // http://api.jqueryui.com/theming/stacking-elements/.
        // @todo: .ui-front just sets a z-index of 100 which is not high enough
        // to overlay Gin's Toolbar.
        $('<mercury-dialog id="drupal-mercury-dialog"></mercury-dialog>')
          .appendTo('body');
      }

      // Special behaviors specific when attaching content within a dialog.
      // These behaviors usually fire after a validation error inside a dialog.
      const $dialog = $(context).closest('mercury-dialog');
      if ($dialog.length) {
        $dialog.trigger('dialogButtonsChange');
      }

    },

    prepareDialogButtons: function prepareDialogButtons($dialog) {
      var buttons = [];
      var $buttons = $dialog.find('.form-actions').last().find('input[type=submit], a.button, a.action-link');
      $buttons.each(function () {
        var $originalButton = $(this).css({
          display: 'none'
        });
        buttons.push({
          text: $originalButton.html() || $originalButton.attr('value'),
          class: $originalButton.attr('class'),
          click: function click(e) {
            if ($originalButton.is('a')) {
              $originalButton[0].click();
            } else {
              $originalButton.trigger('mousedown').trigger('mouseup').trigger('click');
              e.preventDefault();
            }
          }
        });
      });
      return buttons;
    }
  };

  // Moves Layout Paragraphs form buttons into the dialog button pane.
  function moveFormButtonsToDialog(event, dialog, $dialog) {
    if ($dialog[0].tagName !== 'MERCURY-DIALOG') {
      return;
    }
    if ($dialog.attr('id').indexOf('lpb-dialog-') === 0) {
      const buttons = Drupal.behaviors.mercuryDialog.prepareDialogButtons($dialog);
      if (buttons.length) {
        Drupal.mercuryDialog($dialog[0]).applyOptions({ buttons: buttons });
      }
    }
  }
  $(window).on('dialog:aftercreate', moveFormButtonsToDialog);

  /**
   * ResizeObserver callback that resizes the parent iframe based on
   * the height of the child document html element.
   *
   * @param {ResizeObserverEntry[]} entries
   */
  function onBodyResize(iframe) {
    return (entries) => {
      // Resize the parent iframe based on the html's border box height.
      if (iframe && entries.length) {
        iframe.style.height = `${entries[0].borderBoxSize[0].blockSize + 1}px`;
        iframe.style.width = `${entries[0].borderBoxSize[0].inlineSize + 1}px`;
      }
    }
  }

  /**
   * Set a max-width on the iframe's <body> to match the dialog's
   * max-width to prevent horizontal scrolling.
   *
   * @param {HTMLIFrameElement} iframe
   *   The iframe element within a mercury dialog.
   * @param {HTMLBodyElement} framedBody
   *   The body element within the iframe.
   */
  function setFrameBodyMaxWidth(iframe, framedBody) {
    const dialogStyles = window.getComputedStyle(iframe.closest('mercury-dialog').shadowRoot.querySelector('dialog'));
    const dialogMainStyles = window.getComputedStyle(iframe.closest('mercury-dialog').shadowRoot.querySelector('main'));
    framedBody.style.maxWidth = `calc(${dialogStyles.getPropertyValue('max-width')} - ${dialogMainStyles.getPropertyValue('padding-left')} - ${dialogMainStyles.getPropertyValue('padding-right')} - 2px)`;
  }

  /**
   * Resizes an iFrame to match the height of its inner <body> element.
   * @param {HTMLIFrameElement} iframe
   */
  function resizeIframe(iframe) {
    const framedBody = iframe.contentWindow.document.body;
    framedBody.style.width = 'max-content';
    framedBody.style.height = 'fit-content';

    setFrameBodyMaxWidth(iframe, framedBody);

    // Observe changes to the iframe's inner <body> dimensions.
    new ResizeObserver(onBodyResize(iframe)).observe(framedBody, { box: 'border-box' });
  }

  function updateIframeSize(event, dialog, $dialog) {
    if ($dialog[0].tagName !== 'MERCURY-DIALOG') {
      return;
    }

    const iframe = $dialog[0].querySelector('iframe');
    if (!iframe) {
      return;
    }

    $dialog[0].style.setProperty('--me-dialog-height-default', 'fit-content');

    iframe.onload = function () {
      resizeIframe(iframe);
      setFrameBodyMaxWidth(iframe, framedBody);
    };

    const framedBody = iframe?.contentWindow?.document?.body;
    if (framedBody) {
      window.addEventListener('resize', function () {
        setFrameBodyMaxWidth(iframe, framedBody);
      });
    }
  }
  $(window).on('dialog:aftercreate', updateIframeSize);

  // Store open modals.
  const modalStack = [];

  // The following event handlers are used to manage the modal stack.
  // Since native dialog['modal'] elements live in the browser's top-level,
  // we need to make sure any jQuery ui modals that are opended from within
  // a mercury-dialog element get nested within the top-level modal.
  // Otherwise the jQuery dialog will be obscured by the mercury-dialog modal.
  // See https://developer.chrome.com/blog/what-is-the-top-layer/
  function addModalToStack(event, dialog, $dialog) {
    if ($dialog[0].tagName !== 'MERCURY-DIALOG') {
      return;
    }
    if ($dialog[0].hasAttribute('modal') && $dialog[0].getAttribute('modal') !== 'false') {
      modalStack.push($dialog);
    }
  }
  $(window).on('dialog:aftercreate', addModalToStack);

  function removeModalFromStack(event, dialog, $dialog) {
    if ($dialog[0].tagName !== 'MERCURY-DIALOG') {
      return;
    }
    if ($dialog[0].hasAttribute('modal') && $dialog[0].getAttribute('modal') !== 'false') {
      const index = modalStack.indexOf($dialog);
      if (index > -1) {
        modalStack.splice(index, 1);
      }
    }
  }
  $(window).on('dialog:beforeclose', removeModalFromStack);

  function nestDialogInModal(event, dialog, $dialog) {
    if ($dialog[0].tagName !== 'MERCURY-DIALOG') {
      return;
    }
    if (modalStack.length > 0) {
      const $parent = $dialog.parent('.ui-dialog');
      const $overlay = $parent.next('.ui-widget-overlay');
      modalStack.slice(-1)[0].append([$parent, $overlay]);
    }
  }
  $(window).on('dialog:aftercreate', nestDialogInModal);

})(jQuery, Drupal, drupalSettings);
