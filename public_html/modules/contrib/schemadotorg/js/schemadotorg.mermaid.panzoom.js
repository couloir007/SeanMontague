/* eslint-disable no-undef */

/**
 * @file
 * Schema.org Mermaid pan and zoom behaviors.
 */

((Drupal, once, Panzoom) => {
  /**
   * Schema.org Mermaid pan and zoom behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgMermaidPanzoom = {
    attach: function attach(context) {
      // Find newly rendered Mermaid SVGs that have not been initialized.
      once(
        'schemadotorg-mermaid-panzoom',
        '.mermaid svg, .language-mermaid svg',
        context,
      ).forEach(function initializePanzoom(svg) {
        // Find the diagram viewport and respect its Panzoom opt-out.
        const viewport = svg.closest('.mermaid, .language-mermaid');
        if (
          !viewport ||
          viewport.getAttribute('data-schemadotorg-mermaid-panzoom') === 'false'
        ) {
          return;
        }

        // Wrap the SVG in a transformable canvas when needed.
        let canvas = svg.parentElement;
        if (
          !canvas ||
          !canvas.classList.contains('schemadotorg-mermaid-panzoom-canvas')
        ) {
          canvas = document.createElement('div');
          canvas.classList.add('schemadotorg-mermaid-panzoom-canvas');
          svg.before(canvas);
          canvas.append(svg);
        }

        // Create the Panzoom instance with GitHub-compatible scale limits.
        // @see https://github.com/timmywil/panzoom
        const panzoom = Panzoom(canvas, {
          canvas: true,
          maxScale: 8,
          minScale: 0.5,
        });

        // Change the current scale in exact 0.1 increments.
        const zoom = (increment) => {
          const scale = Math.round((panzoom.getScale() + increment) * 10) / 10;
          panzoom.zoom(scale);
        };

        // Create a control container that Panzoom ignores during pointer input.
        const controls = document.createElement('div');
        controls.classList.add(
          'schemadotorg-mermaid-panzoom-controls',
          'panzoom-exclude',
        );

        // Define the label, character, and action for each control.
        const buttons = [
          {
            action: 'zoom-in',
            label: Drupal.t('Zoom in'),
            character: '+',
            callback: () => zoom(0.1),
          },
          {
            action: 'zoom-out',
            label: Drupal.t('Zoom out'),
            character: '−',
            callback: () => zoom(-0.1),
          },
          {
            action: 'reset',
            label: Drupal.t('Reset view'),
            character: '⟳',
            callback: () => panzoom.reset(),
          },
          {
            action: 'pan-up',
            label: Drupal.t('Pan up'),
            character: '↑',
            callback: () => panzoom.pan(0, 100, { relative: true }),
          },
          {
            action: 'pan-down',
            label: Drupal.t('Pan down'),
            character: '↓',
            callback: () => panzoom.pan(0, -100, { relative: true }),
          },
          {
            action: 'pan-left',
            label: Drupal.t('Pan left'),
            character: '←',
            callback: () => panzoom.pan(100, 0, { relative: true }),
          },
          {
            action: 'pan-right',
            label: Drupal.t('Pan right'),
            character: '→',
            callback: () => panzoom.pan(-100, 0, { relative: true }),
          },
        ];

        // Create each accessible control button and add it to the panel.
        buttons.forEach((item) => {
          const button = document.createElement('button');
          button.setAttribute('type', 'button');
          button.setAttribute('aria-label', item.label);
          button.setAttribute('title', item.label);
          button.classList.add(
            'schemadotorg-mermaid-panzoom-button',
            `schemadotorg-mermaid-panzoom-${item.action}`,
            'panzoom-exclude',
          );
          button.textContent = item.character;
          button.addEventListener('click', item.callback);
          controls.append(button);
        });

        // Overlay the completed controls on the diagram viewport.
        viewport.append(controls);
      });
    },
  };
})(Drupal, once, window.Panzoom);
