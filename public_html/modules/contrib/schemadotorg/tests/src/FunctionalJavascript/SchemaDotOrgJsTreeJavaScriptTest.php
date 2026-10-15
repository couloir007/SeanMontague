<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests Schema.org jsTree JavaScript behavior.
 *
 * @group schemadotorg
 */
class SchemaDotOrgJsTreeJavaScriptTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schemadotorg_javascript_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests jsTree initialization, toggling, and node activation.
   */
  public function testJsTreeJavaScript(): void {
    $this->drupalGet('schemadotorg/test/javascript/jstree');

    // Check that jsTree initializes once and adds its toggle.
    $this->assertJsCondition(<<<'JS'
(function () {
  const tree = document.querySelector('#schemadotorg-javascript-test-jstree');
  const jstree = jQuery(tree).jstree(true);
  return typeof jQuery.fn.jstree === 'function'
    && jstree !== false
    && document.querySelectorAll('.schemadotorg-jstree-toggle').length === 1
    && tree.classList.contains('jstree');
}())
JS);

    $toggle = $this->getSession()->getPage()->find('css', '.schemadotorg-jstree-toggle');
    $this->assertNotEmpty($toggle);
    $this->assertSame('Expand all', $toggle->getText());
    $toggle->click();
    // Check that the first toggle opens all parent nodes.
    $this->assertJsCondition(<<<'JS'
(function () {
  const tree = document.querySelector('#schemadotorg-javascript-test-jstree');
  const jstree = jQuery(tree).jstree(true);
  return jstree.is_open('schemadotorg-javascript-test-jstree-thing')
    && document.querySelector('.schemadotorg-jstree-toggle').innerText === 'Collapse all';
}())
JS);
    $toggle->click();
    // Check that the second toggle closes all parent nodes.
    $this->assertJsCondition(<<<'JS'
(function () {
  const tree = document.querySelector('#schemadotorg-javascript-test-jstree');
  const jstree = jQuery(tree).jstree(true);
  return !jstree.is_open('schemadotorg-javascript-test-jstree-thing')
    && document.querySelector('.schemadotorg-jstree-toggle').innerText === 'Expand all';
}())
JS);

    // Activate a node without a dialog opener.
    $this->getSession()->executeScript(<<<'JS'
const tree = document.querySelector('#schemadotorg-javascript-test-jstree');
jQuery(tree).jstree(true).activate_node('schemadotorg-javascript-test-jstree-person');
JS);
    // Check that activation follows the node link without a dialog opener.
    $this->assertJsCondition("window.location.hash === '#Person'");

    // Add a dialog opener spy and activate the node again.
    $this->drupalGet('schemadotorg/test/javascript/jstree');
    $this->getSession()->executeScript(<<<'JS'
window.schemaDotOrgJsTreeDialogUrl = null;
Drupal.schemaDotOrgOpenDialog = (url) => {
  window.schemaDotOrgJsTreeDialogUrl = url;
};
const tree = document.querySelector('#schemadotorg-javascript-test-jstree');
jQuery(tree).jstree(true).activate_node('schemadotorg-javascript-test-jstree-person');
JS);
    // Check that activation delegates when the dialog opener is available.
    $this->assertJsCondition(<<<'JS'
window.schemaDotOrgJsTreeDialogUrl === '#Person' && window.location.hash === ''
JS);

    // Check that reattaching behaviors does not duplicate the toggle.
    $this->assertJsCondition(<<<'JS'
(function () {
  Drupal.attachBehaviors(document);
  return document.querySelectorAll('.schemadotorg-jstree-toggle').length === 1;
}())
JS);
  }

}
