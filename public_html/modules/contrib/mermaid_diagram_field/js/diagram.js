/* global mermaid, svgPanZoom */
/**
 * @file
 * Render Mermaid diagrams and attach svg-pan-zoom
 */
(function (Drupal, mermaid, once, settings) {
  Drupal.behaviors.diagramDisplay = {
    attach(context, settings) {
      const elements = once('diagram-display', '.mermaid', context);
      if (!elements.length || typeof mermaid === 'undefined') {
        return;
      }

      const extraSettings = settings.mermaidDiagramField.extraSettings;

      mermaid.initialize(extraSettings);

      elements.forEach(async (el, idx) => {
        el.style.opacity = '0';
        const code = el.textContent.trim();
        const id = `mmd-${Date.now()}-${idx}-${Math.random().toString(36).slice(2)}`;

        try {
          const { svg } = await mermaid.render(id, code);
          el.innerHTML = svg;

          const svgEl = el.querySelector('svg');
          if (svgEl && typeof svgPanZoom !== 'undefined') {
            const panZoom = svgPanZoom(svgEl, {
              zoomEnabled: true,
              controlIconsEnabled: true,
              fit: true,
              center: true,
              minZoom: 1,
              maxZoom: 24,
              zoomScaleSensitivity: 0.8,
            });

            // Trigger layout recalculation after rendering
            // Fixes controls appearing off canvas
            requestAnimationFrame(() => {
              panZoom.resize();
              panZoom.fit();
              panZoom.center();
            });

            // Keep responsive on resize
            window.addEventListener('resize', () => {
              panZoom.resize();
              panZoom.fit();
              panZoom.center();
            });
          }

          el.style.opacity = '1';
        } catch (e) {
          el.innerHTML = `<pre class="mermaid-error">Mermaid render error:\n${String(e)}</pre>`;
          el.style.opacity = '1';
        }
      });
    },
  };

  /**
   * Attach download handlers for Mermaid diagrams.
   *
   * This allows users to download the raw Mermaid definition as a .mermaid file.
   */
  Drupal.behaviors.mermaidDiagramDownload = {
    attach(context) {
      const delegates = once('mermaid-diagram-download-delegate', 'body');
      if (!delegates.length) {
        return;
      }

      delegates.forEach((delegateEl) => {
        delegateEl.addEventListener('click', (event) => {
          const button = event.target.closest('.mermaid-download-button');
          if (!button) {
            return;
          }

          const mermaidCode = button.dataset.mermaid;
          if (!mermaidCode) {
            return;
          }

          // Use the title field for the filename if available.
          let filename = 'mermaid-diagram.mermaid';
          const { title } = button.dataset;
          if (title) {
            // Sanitize title for filename: remove unsafe chars, spaces to underscores.
            filename = `${title.replace(/[^a-zA-Z0-9-_]+/g, '_')}.mermaid`;
          }

          const blob = new Blob([mermaidCode], {
            type: 'text/plain;charset=utf-8',
          });
          const url = URL.createObjectURL(blob);

          const link = document.createElement('a');
          link.href = url;
          link.download = filename;
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);

          URL.revokeObjectURL(url);
        });
      });
    },
  };
})(Drupal, mermaid, once, drupalSettings);
