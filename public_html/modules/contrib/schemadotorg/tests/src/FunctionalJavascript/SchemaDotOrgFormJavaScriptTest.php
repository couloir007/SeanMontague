<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests Schema.org form JavaScript behavior.
 *
 * @group schemadotorg
 */
class SchemaDotOrgFormJavaScriptTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schemadotorg_javascript_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that form submission is visibly limited to one submission.
   */
  public function testFormSubmitOnce(): void {
    $this->drupalGet('schemadotorg/test/javascript/form');

    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $assert->elementExists('css', '#edit-submit');
    $assert->elementExists('css', '#edit-cancel');
    $assert->elementAttributeNotExists('css', '#edit-submit', 'disabled');

    // Prevent navigation after the production submit listener updates the UI.
    $this->getSession()->executeScript(<<<'JS'
const form = document.querySelector('form.js-schemadotorg-submit-once');
const submit = form.querySelector('.form-actions input[type="submit"]');
form.addEventListener('submit', (event) => event.preventDefault());
submit.click();
submit.click();
JS);

    // Check that the duplicate click keeps one throbber and removes cancel.
    $assert->waitForElementVisible('css', '.ajax-progress-throbber');
    $assert->elementAttributeExists('css', '#edit-submit', 'disabled');
    $this->assertCount(1, $page->findAll('css', '.ajax-progress-throbber'));
    $assert->elementNotExists('css', '#edit-cancel');
  }

}
