<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests Schema.org autocomplete JavaScript behavior.
 *
 * @group schemadotorg
 */
class SchemaDotOrgAutocompleteJavaScriptTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schemadotorg_javascript_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests standard and dialog autocomplete actions.
   */
  public function testAutocomplete(): void {
    global $base_path;

    $this->drupalGet('schemadotorg/test/javascript/form');

    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Find the visible Person suggestion.
    $autocomplete = $page->findById('edit-autocomplete');
    $this->assertNotEmpty($autocomplete);
    $this->assertSame(
      $base_path . 'schemadotorg/test/javascript/form?autocomplete=',
      $autocomplete->getAttribute('data-schemadotorg-autocomplete-action'),
    );

    $autocomplete->setValue('Person');
    $suggestion = $assert->waitForElementVisible('css', 'ul.ui-autocomplete li');
    $this->assertNotEmpty($suggestion);
    $this->assertSame('Person', $suggestion->getText());
    $suggestion->click();

    // Check that selecting the result follows the element action.
    $assert->addressEquals('/schemadotorg/test/javascript/form?autocomplete=Person');

    // Check that dialog autocomplete delegates to the dialog opener.
    $this->drupalGet('schemadotorg/test/javascript/form');
    $this->getSession()->executeScript(<<<'JS'
window.schemaDotOrgAutocompleteDialogUrl = null;
Drupal.schemaDotOrgOpenDialog = (url) => {
  window.schemaDotOrgAutocompleteDialogUrl = url;
};
jQuery('#edit-autocomplete-dialog').trigger('autocompleteselect', {
  item: { value: 'Person' }
});
JS);
    // Check that dialog autocomplete delegates without navigating.
    $this->assertJsCondition(<<<'JS'
(function () {
  const url = Drupal.url('schemadotorg/test/javascript/form?autocomplete=Person');
  return window.schemaDotOrgAutocompleteDialogUrl === url
  && window.location.pathname === new URL(url, window.location.origin).pathname
  && window.location.search === ''
}())
JS);
  }

}
