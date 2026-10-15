<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_ui\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests Schema.org UI JavaScript behaviors on the mapping form.
 *
 * @group schemadotorg
 */
class SchemaDotOrgUiJavaScriptTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'field',
    'field_ui',
    'schemadotorg_ui_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $account = $this->drupalCreateUser([
      'administer user fields',
      'administer schemadotorg',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Tests Schema.org UI JavaScript behaviors on the user mapping form.
   */
  public function testUiJavaScript(): void {
    $this->drupalGet('admin/config/people/accounts/schemadotorg');

    $assert = $this->assertSession();

    // Check that the table is initialized and has one visibility toggle.
    $assert->waitForElementVisible('css', 'table.schemadotorg-ui-properties');
    $assert->elementExists('css', 'table.schemadotorg-ui-properties');
    $this->assertCount(1, $this->getSession()->getPage()->findAll('css', '.schemadotorg-ui-properties-toggle'));

    $page = $this->getSession()->getPage();
    $filter = $page->find('css', '.schemadotorg-ui-properties-filter-text');
    $this->assertNotEmpty($filter);
    $filter->setValue('email');
    // Check that filtering marks the matching email property and announces it.
    $this->assertJsCondition('document.querySelector("table.schemadotorg-ui-properties").classList.contains("schemadotorg-ui-properties-filter-matches")');
    $assert->elementAttributeContains('xpath', '//select[@name="mapping[properties][email][field][name]"]/ancestor::tr', 'class', 'schemadotorg-ui-properties-filter-match');
    $assert->waitForElement('xpath', '//*[@id="drupal-live-announce" and contains(., "property is available")]');
    $assert->elementTextContains('css', '#drupal-live-announce', 'property is available');
    $filter->setValue('');
    // Check that clearing the filter removes the match state.
    $this->assertJsCondition(<<<'JS'
!document.querySelector('table.schemadotorg-ui-properties').classList.contains('schemadotorg-ui-properties-filter-matches')
JS);

    $toggle = $page->find('css', '.schemadotorg-ui-properties-toggle');
    $this->assertNotEmpty($toggle);
    $this->assertSame('Show unmapped', $toggle->getText());
    $toggle->click();
    // Check that showing unmapped rows updates the saved preference and label.
    $this->assertJsCondition(<<<'JS'
(function () {
  const rows = document.querySelectorAll('table.schemadotorg-ui-properties tbody tr');
  return localStorage.getItem('schemadotorg-ui-properties-toggle') === '0'
    && document.querySelector('.schemadotorg-ui-properties-toggle').innerText === 'Hide unmapped'
    && [...rows].every((row) => row.style.display === 'table-row');
}())
JS);
    $this->getSession()->reload();
    // Check that the visibility preference survives a page reload.
    $this->assertJsCondition(<<<'JS'
localStorage.getItem('schemadotorg-ui-properties-toggle') === '0'
  && document.querySelector('.schemadotorg-ui-properties-toggle').innerText === 'Hide unmapped'
JS);

    $this->getSession()->executeScript(<<<'JS'
const select = document.querySelector('select[name="mapping[properties][name][field][name]"]');
const row = select.closest('tr');
select.value = '_add_';
select.dispatchEvent(new Event('change', {bubbles: true}));
window.schemaDotOrgUiWarning = row.classList.contains('color-warning');
select.value = '';
select.dispatchEvent(new Event('change', {bubbles: true}));
window.schemaDotOrgUiNeutral = !row.classList.contains('color-success') && !row.classList.contains('color-warning');
select.value = [...select.options].find((option) => option.defaultSelected).value;
select.dispatchEvent(new Event('change', {bubbles: true}));
window.schemaDotOrgUiSuccess = row.classList.contains('color-success');
JS);
    // Check that changing the field mapping updates all row status classes.
    $this->assertJsCondition('window.schemaDotOrgUiWarning && window.schemaDotOrgUiNeutral && window.schemaDotOrgUiSuccess');

    $this->getSession()->executeScript(<<<'JS'
const details = document.querySelector('select[name="mapping[properties][name][field][name]"]').closest('tr').querySelector('details.schemadotorg-ui--add-field');
const label = details.querySelector('input[name$="[label]"]');
const type = details.querySelector('select[name$="[type]"]');
const unlimited = details.querySelector('input[name$="[unlimited]"]');
const required = details.querySelector('input[name$="[required]"]');
label.value = 'Display name';
label.dispatchEvent(new KeyboardEvent('keydown', {bubbles: true}));
type.selectedIndex = 1;
type.dispatchEvent(new Event('change', {bubbles: true}));
unlimited.click();
required.click();
window.schemaDotOrgUiSummary = details.querySelector('.summary').innerText;
JS);
    // Check that changed add-field controls update the details summary.
    $this->assertJsCondition(<<<'JS'
window.schemaDotOrgUiSummary.includes('Display name: ')
  && window.schemaDotOrgUiSummary.includes(' - unlimited')
  && window.schemaDotOrgUiSummary.includes(' - required')
JS);

    // Check that reattaching behaviors does not add another toggle.
    $this->assertJsCondition(<<<'JS'
(function () {
  Drupal.attachBehaviors(document);
  return document.querySelectorAll('.schemadotorg-ui-properties-toggle').length === 1;
}())
JS);
  }

}
