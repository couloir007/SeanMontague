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
  protected static $modules = ['schemadotorg_mermaid_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests Mermaid rendering, Panzoom behavior, and SVG downloads.
   */
  public function testMermaidJavaScript(): void {
    $this->drupalGet('schemadotorg-mermaid-test');

    // Check that Mermaid renders an SVG and the Panzoom library is loaded.
    $this->assertJsCondition('document.querySelector(".schemadotorg-mermaid-test-diagram svg") !== null');
    $this->assertJsCondition('typeof window.Panzoom === "function"');

    // Check that the rendered SVG is wrapped in the Panzoom canvas once.
    $this->assertJsCondition('document.querySelectorAll(".schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-canvas").length === 1');
    $this->assertJsCondition('document.querySelector(".schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-canvas > svg") !== null');

    // Check that reattaching Drupal behaviors does not duplicate the wrapper.
    $this->getSession()->executeScript('Drupal.attachBehaviors(document.querySelector(".schemadotorg-mermaid-test-diagram"));');
    $this->assertJsCondition('document.querySelectorAll(".schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-canvas").length === 1');

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

    // Check that wheel zoom changes the Panzoom canvas transform.
    $this->getSession()->executeScript(<<<'JS'
const viewport = document.querySelector('.schemadotorg-mermaid-test-diagram');
const canvas = viewport.querySelector('.schemadotorg-mermaid-panzoom-canvas');
canvas.dataset.initialTransform = getComputedStyle(canvas).transform;
viewport.dispatchEvent(new WheelEvent('wheel', {
  bubbles: true,
  cancelable: true,
  clientX: viewport.getBoundingClientRect().left + 10,
  clientY: viewport.getBoundingClientRect().top + 10,
  deltaY: -120
}));
JS);
    $this->assertJsCondition(<<<'JS'
(function () {
  const canvas = document.querySelector('.schemadotorg-mermaid-test-diagram .schemadotorg-mermaid-panzoom-canvas');
  return getComputedStyle(canvas).transform !== canvas.dataset.initialTransform;
}())
JS);

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
    && !download.svgText.includes('transform:');
}())
JS);

    // Check that Panzoom can be disabled per Mermaid diagram.
    $this->assertJsCondition('document.querySelector(".schemadotorg-mermaid-test-disabled svg") !== null');
    $this->assertJsCondition('document.querySelector(".schemadotorg-mermaid-test-disabled").getAttribute("data-schemadotorg-mermaid-panzoom") === "false"');
    $this->assertJsCondition('document.querySelector(".schemadotorg-mermaid-test-disabled .schemadotorg-mermaid-panzoom-canvas") === null');
    $this->assertJsCondition('getComputedStyle(document.querySelector(".schemadotorg-mermaid-test-disabled svg")).transform === "none"');
  }

}
