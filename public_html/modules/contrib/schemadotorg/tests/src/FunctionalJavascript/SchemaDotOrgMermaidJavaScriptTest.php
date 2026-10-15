<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests Mermaid JavaScript behavior.
 *
 * @group schemadotorg
 */
class SchemaDotOrgMermaidJavaScriptTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schemadotorg_javascript_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests Mermaid rendering, Panzoom behavior, and SVG downloads.
   */
  public function testMermaidJavaScript(): void {
    $this->drupalGet('schemadotorg/test/javascript/mermaid');

    // Check that Mermaid renders an SVG and the Panzoom library is loaded.
    $this->assertJsCondition('document.querySelector(".schemadotorg-mermaid-test-diagram svg") !== null');
    $this->assertJsCondition('typeof window.Panzoom === "function"');

    // Check that the rendered SVG and controls are initialized once.
    $this->assertJsCondition('document.querySelector(".schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-canvas > svg") !== null');
    $this->assertCount(1, $this->getSession()->getPage()->findAll('css', '.schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-canvas'));
    $this->assertCount(1, $this->getSession()->getPage()->findAll('css', '.schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-controls'));
    $this->assertCount(7, $this->getSession()->getPage()->findAll('css', '.schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-button'));
    $this->assertCount(1, $this->getSession()->getPage()->findAll('css', '.schemadotorg-mermaid-test-diagram + a.button'));
    $this->assertJsCondition(<<<'JS'
(function () {
  const labels = [...document.querySelectorAll(
    '.schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-button'
  )].map((button) => button.getAttribute('aria-label'));
  return labels.join('|') === 'Zoom in|Zoom out|Reset view|Pan up|Pan down|Pan left|Pan right';
}())
JS);

    // Check that reattaching Drupal behaviors does not duplicate the UI.
    $this->getSession()->executeScript('Drupal.attachBehaviors(document.querySelector(".schemadotorg-mermaid-test-diagram"));');
    $this->assertCount(1, $this->getSession()->getPage()->findAll('css', '.schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-canvas'));
    $this->assertCount(1, $this->getSession()->getPage()->findAll('css', '.schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-controls'));

    // Check that the initial diagram geometry stays inside the viewport.
    $this->assertJsCondition(<<<'JS'
(function () {
  const viewport = document.querySelector('.schemadotorg-mermaid-test-diagram');
  const canvas = viewport.querySelector('.schemadotorg-mermaid-panzoom-canvas');
  const viewportRect = viewport.getBoundingClientRect();
  const canvasRect = canvas.getBoundingClientRect();
  return canvasRect.top >= viewportRect.top && canvasRect.bottom <= viewportRect.bottom;
}())
JS);

    // Check that the Panzoom canvas is centered in the Mermaid viewport.
    $this->assertJsCondition(<<<'JS'
(function () {
  const viewport = document.querySelector('.schemadotorg-mermaid-test-diagram');
  const canvas = viewport.querySelector('.schemadotorg-mermaid-panzoom-canvas');
  const viewportRect = viewport.getBoundingClientRect();
  const canvasRect = canvas.getBoundingClientRect();
  const leftGap = Math.round(canvasRect.left - viewportRect.left);
  const rightGap = Math.round(viewportRect.right - canvasRect.right);
  return Math.abs(leftGap - rightGap) <= 1;
}())
JS);

    // Check that mobile layout does not create document-level horizontal overflow.
    $this->getSession()->resizeWindow(390, 844, 'current');
    $this->assertJsCondition('document.documentElement.scrollWidth <= document.documentElement.clientWidth');

    // Check the zoom, directional pan, and reset controls.
    $this->getSession()->executeScript("document.querySelector('.schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-zoom-in').click();");
    $this->assertJsCondition(<<<'JS'
(function () {
  const canvas = document.querySelector('.schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-canvas');
  return canvas.style.transform.includes('scale(1.1)');
}())
JS);
    $this->getSession()->executeScript("document.querySelector('.schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-pan-right').click();");
    $this->assertJsCondition("document.querySelector('.schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-canvas').style.transform.includes('translate(-100px, 0px)')");
    $this->getSession()->executeScript("document.querySelector('.schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-pan-down').click();");
    $this->assertJsCondition("document.querySelector('.schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-canvas').style.transform.includes('translate(-100px, -100px)')");
    $this->getSession()->executeScript("document.querySelector('.schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-pan-left').click();");
    $this->assertJsCondition("document.querySelector('.schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-canvas').style.transform.includes('translate(0px, -100px)')");
    $this->getSession()->executeScript("document.querySelector('.schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-pan-up').click();");
    $this->assertJsCondition("document.querySelector('.schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-canvas').style.transform.includes('translate(0px, 0px)')");
    $this->getSession()->executeScript("document.querySelector('.schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-reset').click();");
    $this->assertJsCondition("document.querySelector('.schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-canvas').style.transform.includes('scale(1) translate(0px, 0px)')");

    // Check that zooming is clamped to GitHub's minimum and maximum scales.
    $this->getSession()->executeScript(<<<'JS'
const viewport = document.querySelector('.schemadotorg-mermaid-test-diagram');
const zoomOut = viewport.querySelector('.schemadotorg-mermaid-panzoom-zoom-out');
const reset = viewport.querySelector('.schemadotorg-mermaid-panzoom-reset');
reset.click();
for (let index = 0; index < 100; index++) {
  zoomOut.click();
}
JS);
    $this->assertJsCondition("document.querySelector('.schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-canvas').style.transform.includes('scale(0.5)')");
    $this->getSession()->executeScript(<<<'JS'
const viewport = document.querySelector('.schemadotorg-mermaid-test-diagram');
const zoomIn = viewport.querySelector('.schemadotorg-mermaid-panzoom-zoom-in');
for (let index = 0; index < 100; index++) {
  zoomIn.click();
}
JS);
    $this->assertJsCondition("document.querySelector('.schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-canvas').style.transform.includes('scale(8)')");
    $this->getSession()->executeScript("document.querySelector('.schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-reset').click();");

    // Check that pointer dragging pans the diagram.
    $this->getSession()->executeScript(<<<'JS'
const viewport = document.querySelector('.schemadotorg-mermaid-test-diagram');
const canvas = viewport.querySelector('.schemadotorg-mermaid-panzoom-canvas');
canvas.dataset.beforeDragTransform = canvas.style.transform;
viewport.dispatchEvent(new PointerEvent('pointerdown', {
  bubbles: true,
  button: 0,
  buttons: 1,
  clientX: 100,
  clientY: 100,
  isPrimary: true,
  pointerId: 1,
  pointerType: 'mouse'
}));
document.dispatchEvent(new PointerEvent('pointermove', {
  bubbles: true,
  buttons: 1,
  clientX: 140,
  clientY: 130,
  isPrimary: true,
  pointerId: 1,
  pointerType: 'mouse'
}));
document.dispatchEvent(new PointerEvent('pointerup', {
  bubbles: true,
  button: 0,
  clientX: 140,
  clientY: 130,
  isPrimary: true,
  pointerId: 1,
  pointerType: 'mouse'
}));
JS);
    $this->assertJsCondition(<<<'JS'
(function () {
  const canvas = document.querySelector('.schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-canvas');
  return canvas.style.transform !== canvas.dataset.beforeDragTransform;
}())
JS);

    // Check that wheel scrolling is not intercepted and does not zoom.
    $this->getSession()->executeScript(<<<'JS'
const viewport = document.querySelector('.schemadotorg-mermaid-test-diagram');
const canvas = viewport.querySelector('.schemadotorg-mermaid-panzoom-canvas');
viewport.querySelector('.schemadotorg-mermaid-panzoom-reset').click();
canvas.dataset.beforeWheelTransform = canvas.style.transform;
canvas.dataset.wheelNotCanceled = viewport.dispatchEvent(new WheelEvent('wheel', {
  bubbles: true,
  cancelable: true,
  clientX: viewport.getBoundingClientRect().left + 10,
  clientY: viewport.getBoundingClientRect().top + 10,
  deltaY: -120
})) ? 'true' : 'false';
canvas.dataset.afterWheelTransform = canvas.style.transform;
JS);
    $this->assertJsCondition(<<<'JS'
(function () {
  const canvas = document.querySelector('.schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-canvas');
  return canvas.dataset.wheelNotCanceled === 'true'
    && canvas.dataset.afterWheelTransform === canvas.dataset.beforeWheelTransform;
}())
JS);

    // Check that the rendered download control exports the expected diagram.
    $this->getSession()->executeScript(<<<'JS'
window.schemaDotOrgMermaidDownloadControlElement = null;
const originalDownload = Drupal.schemaDotOrgMermaidDownloadSvg;
Drupal.schemaDotOrgMermaidDownloadSvg = (element) => {
  window.schemaDotOrgMermaidDownloadControlElement = element;
};
document.querySelector('.schemadotorg-mermaid-test-diagram + a.button').click();
Drupal.schemaDotOrgMermaidDownloadSvg = originalDownload;
JS);
    $this->assertJsCondition('window.schemaDotOrgMermaidDownloadControlElement === document.querySelector(".schemadotorg-mermaid-test-diagram")');

    // Check that SVG download exports the rendered SVG without Panzoom state.
    $this->getSession()->executeScript(<<<'JS'
window.schemaDotOrgMermaidDownloadTest = {};
const originalCreateObjectUrl = URL.createObjectURL;
const originalRevokeObjectUrl = URL.revokeObjectURL;
const originalClick = HTMLAnchorElement.prototype.click;

URL.createObjectURL = function createObjectUrl(blob) {
  window.schemaDotOrgMermaidDownloadTest.blob = blob;
  return 'blob:schemadotorg-mermaid-test';
};
URL.revokeObjectURL = function revokeObjectUrl(url) {
  window.schemaDotOrgMermaidDownloadTest.revokedUrl = url;
};
HTMLAnchorElement.prototype.click = function click() {
  window.schemaDotOrgMermaidDownloadTest.href = this.href;
  window.schemaDotOrgMermaidDownloadTest.download = this.download;
};

const svg = document.querySelector('.schemadotorg-mermaid-test-diagram svg');
const svgLink = document.createElementNS('http://www.w3.org/2000/svg', 'a');
svgLink.setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', 'https://example.com');
svg.append(svgLink);

Drupal.schemaDotOrgMermaidDownloadSvg(
  document.querySelector('.schemadotorg-mermaid-test-diagram')
);

URL.createObjectURL = originalCreateObjectUrl;
URL.revokeObjectURL = originalRevokeObjectUrl;
HTMLAnchorElement.prototype.click = originalClick;

window.schemaDotOrgMermaidDownloadTest.blob.text().then((svgText) => {
  window.schemaDotOrgMermaidDownloadTest.svgText = svgText;
});
JS);
    $this->assertJsCondition(<<<'JS'
(function () {
  const download = window.schemaDotOrgMermaidDownloadTest;
  return download.href === 'blob:schemadotorg-mermaid-test'
    && download.revokedUrl === 'blob:schemadotorg-mermaid-test'
    && download.download === 'schema-org-mermaid-test-drupal.svg'
    && download.svgText.includes('<svg')
    && download.svgText.includes('flowchart')
    && !download.svgText.includes('schemadotorg-mermaid-panzoom-canvas')
    && !download.svgText.includes('cursor: move')
    && !download.svgText.includes('transform:')
    && !download.svgText.includes('xlink:href');
}())
JS);

    // Check that the language-mermaid selector renders a disabled Panzoom diagram.
    $this->assertJsCondition('document.querySelector(".schemadotorg-mermaid-test-disabled svg") !== null');
    $this->assertJsCondition('document.querySelector(".schemadotorg-mermaid-test-disabled").getAttribute("data-schemadotorg-mermaid-panzoom") === "false"');
    $this->assertJsCondition('document.querySelector(".schemadotorg-mermaid-test-disabled .schemadotorg-mermaid-panzoom-canvas") === null');
    $this->assertJsCondition('getComputedStyle(document.querySelector(".schemadotorg-mermaid-test-disabled svg")).transform === "none"');

    // Check that rendering a language-mermaid diagram restores closed details.
    $this->assertJsCondition(<<<'JS'
(function () {
  const details = document.querySelector('#schemadotorg-mermaid-test-details');
  const diagram = details.querySelector('.schemadotorg-mermaid-test-details-diagram');
  return diagram.querySelector('svg') !== null
    && diagram.querySelector('.schemadotorg-mermaid-panzoom-canvas > svg') !== null
    && !details.open;
}())
JS);
  }

}
