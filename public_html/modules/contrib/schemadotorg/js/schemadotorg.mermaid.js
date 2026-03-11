/* eslint-disable no-undef */

/**
 * @file
 * Schema.org mermaid behaviors.
 */

((Drupal, mermaid, once) => {
  /**
   * Schema.org mermaid behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgMermaid = {
    attach: function attach(context) {
      const mermaids = once('mermaid', '.mermaid, .language-mermaid', context);
      if (!mermaids.length) {
        return;
      }

      let closedDetails = [];
      mermaids.forEach((element) => {
        // Track closed details and open them to after diagram is rendered.
        let parentElement = element.parentNode;
        while (parentElement) {
          // eslint-disable-next-line
          if (parentElement.tagName === 'DETAILS' && !parentElement.getAttribute('open')) {
            parentElement.setAttribute('open', 'open');
            closedDetails.push(parentElement);
          }
          parentElement = parentElement.parentNode;
        }

        // Append download SVG link.
        if (element.classList.contains('mermaid-download')) {
          const link = document.createElement('a');
          link.setAttribute('class', 'button button--small');
          link.innerHTML = `<u>⇩</u> ${Drupal.t('Download SVG')}`;
          link.addEventListener('click', () =>
            Drupal.schemaDotOrgMermaidDownloadSvg(element),
          );
          element.after(link);
        }
      });

      // Via post render close opened details and svg-pan-zoom
      mermaid.run({
        querySelector: '.mermaid, .language-mermaid',
        postRenderCallback: () => {
          // Use set timeout to delay closing details until all diagrams are rendered.
          window.setTimeout(function closeDetails() {
            if (closedDetails) {
              closedDetails.forEach((element) =>
                element.removeAttribute('open'),
              );
            }
            // Set closed details to null to only trigger closing details once.
            closedDetails = null;
          });

          // @see https://github.com/ariutta/svg-pan-zoom
          if (window.svgPanZoom) {
            svgPanZoom('.mermaid svg, .language-mermaid svg', {
              controlIconsEnabled: true,
            });
          }
        },
      });
    },
  };

  /**
   * Download SVG generated via Mermaid.js.
   *
   * @param {HTMLElement} element
   *   The Mermaid.js element.
   *
   * @see https://takuti.me/note/javascript-save-svg-as-image/
   * @see https://gist.github.com/tatsuyasusukida/1261585e3422da5645a1cbb9cf8813d6
   * @see https://zooper.pages.dev/articles/how-to-convert-a-svg-to-png-using-canvas
   */
  Drupal.schemaDotOrgMermaidDownloadSvg = (element) => {
    const svg = element.querySelector('svg').cloneNode(true);

    // Remove svg-pan-zoom widget.
    const svgPanZoomControls = svg.getElementById('svg-pan-zoom-controls');
    if (svgPanZoomControls) {
      svgPanZoomControls.remove();
    }

    // Remove all hrefs.
    const links = [...svg.getElementsByTagName('a')];
    links.forEach((item) => item.removeAttribute('xlink:href'));

    const svgData = new XMLSerializer().serializeToString(svg);
    const blob = new Blob([svgData], { type: 'image/svg+xml' });
    const url = URL.createObjectURL(blob);

    const link = document.createElement('a');
    const fileName = `${document.title.replace(/[^a-z0-9]+/gi, '-').toLowerCase()}.svg`;
    link.href = url;
    link.download = fileName;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  };
})(Drupal, mermaid, once);
