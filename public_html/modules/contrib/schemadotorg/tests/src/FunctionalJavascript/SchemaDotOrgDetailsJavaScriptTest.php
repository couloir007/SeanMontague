<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests Schema.org details JavaScript behavior.
 *
 * @group schemadotorg
 */
class SchemaDotOrgDetailsJavaScriptTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schemadotorg_javascript_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests details state, hash targets, and toggle behavior.
   */
  public function testDetailsJavaScript(): void {
    $this->drupalGet('schemadotorg/test/javascript/form');

    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $toggle = $page->find('css', '.schemadotorg-details-toggle');
    $this->assertNotEmpty($toggle);
    $this->assertSame('+ Expand all', $toggle->getText());

    // Check that local storage restores a details widget's open state.
    $this->getSession()->executeScript(<<<'JS'
localStorage.setItem('schemadotorg-javascript-test-details-closed', '1');
location.reload();
JS);
    $assert->waitForElement('css', '#schemadotorg-javascript-test-details-closed[open]');
    $assert->elementAttributeExists('css', '#schemadotorg-javascript-test-details-closed', 'open');

    // Check that a hash target takes precedence over stored closed state.
    $this->getSession()->executeScript(<<<'JS'
localStorage.setItem('schemadotorg-javascript-test-details-hash', '0');
window.location.hash = 'schemadotorg-javascript-test-details-hash';
window.location.reload();
JS);
    $assert->waitForElement('css', '#schemadotorg-javascript-test-details-hash[open]');
    $assert->elementAttributeExists('css', '#schemadotorg-javascript-test-details-hash', 'open');

    // Reset stored state before testing the expand/collapse control.
    $this->getSession()->executeScript(<<<'JS'
document.querySelectorAll('.region-help details').forEach((element) => {
  localStorage.setItem(element.dataset.schemadotorgDetailsKey, '0');
});
window.location.hash = '';
window.location.reload();
JS);

    // Check that expanding all details persists their open state.
    $toggle = $this->getSession()->getPage()->find('css', '.schemadotorg-details-toggle');
    $this->assertNotEmpty($toggle);
    $toggle->click();
    $assert->waitForElement('css', '#schemadotorg-javascript-test-details-closed[open]');
    $assert->elementAttributeExists('css', '#schemadotorg-javascript-test-details-closed', 'open');
    $assert->elementAttributeExists('css', '#schemadotorg-javascript-test-details-open', 'open');
    $assert->elementAttributeExists('css', '#schemadotorg-javascript-test-details-hash', 'open');
    $assert->elementTextEquals('css', '.schemadotorg-details-toggle', '− Collapse all');
    $assert->waitForElement('xpath', '//*[@id="drupal-live-announce" and text() = "All details have been expanded."]');
    $assert->elementTextEquals('css', '#drupal-live-announce', 'All details have been expanded.');
    $this->assertJsCondition(<<<'JS'
(function () {
  const details = document.querySelectorAll('.region-help details');
  return [...details].every((element) => localStorage.getItem(element.dataset.schemadotorgDetailsKey) === '1')
    && document.querySelector('.schemadotorg-details-toggle') === document.activeElement;
}())
JS);

    // Check that collapsing all details persists their closed state.
    $toggle->click();
    $assert->waitForElement('css', '#schemadotorg-javascript-test-details-closed:not([open])');
    $assert->elementAttributeNotExists('css', '#schemadotorg-javascript-test-details-closed', 'open');
    $assert->elementAttributeNotExists('css', '#schemadotorg-javascript-test-details-open', 'open');
    $assert->elementAttributeNotExists('css', '#schemadotorg-javascript-test-details-hash', 'open');
    $assert->elementTextEquals('css', '.schemadotorg-details-toggle', '+ Expand all');
    $assert->waitForElement('xpath', '//*[@id="drupal-live-announce" and text() = "All details have been collapsed."]');
    $assert->elementTextEquals('css', '#drupal-live-announce', 'All details have been collapsed.');
    $this->assertJsCondition(<<<'JS'
(function () {
  const details = document.querySelectorAll('.region-help details');
  return [...details].every((element) => localStorage.getItem(element.dataset.schemadotorgDetailsKey) === '0')
    && document.querySelector('.schemadotorg-details-toggle') === document.activeElement;
}())
JS);

    $assert->pageTextContains('Closed details content.');
  }

}
