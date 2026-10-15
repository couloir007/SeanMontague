<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests Schema.org settings element JavaScript behavior.
 *
 * @group schemadotorg
 */
class SchemaDotOrgSettingsElementJavaScriptTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schemadotorg_javascript_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the settings element example disclosure behavior.
   */
  public function testSettingsElementJavaScript(): void {
    $this->drupalGet('schemadotorg/test/javascript/form');

    $assert = $this->assertSession();

    // Check the example is moved beside its description with ARIA state.
    $assert->waitForElementVisible('css', '.schemadotorg-settings-example a[aria-expanded="false"]');
    $assert->elementAttributeContains('css', '#schemadotorg-javascript-test-settings', 'class', 'schemadotorg-codemirror');
    $assert->elementAttributeContains('css', '#schemadotorg-javascript-test-settings', 'data-mode', 'yaml');
    $assert->elementAttributeContains('css', '.schemadotorg-settings-example a', 'aria-controls', 'schemadotorg-javascript-test-settings-example');
    $assert->elementAttributeContains('css', '.schemadotorg-settings-example a', 'aria-expanded', 'false');
    $this->assertJsCondition(<<<'JS'
(function () {
  const settings = document.querySelector('#schemadotorg-javascript-test-settings');
  const formItem = settings.closest('.form-item');
  const description = formItem.querySelector('.form-item__description, .description');
  const example = document.querySelector('.schemadotorg-settings-example');
  const link = example.querySelector('a');
  return settings.nextElementSibling.classList.contains('CodeMirror')
    && example.parentNode === formItem
    && example.previousElementSibling === description
    && link !== null;
}())
JS);

    // Check mouse and keyboard disclosure behavior.
    $this->assertJsCondition(<<<'JS'
(function () {
  const example = document.querySelector('.schemadotorg-settings-example');
  const link = example.querySelector('a');
  const linkWrapper = link.parentNode;
  const keydown = (which) => {
    const event = new Event('keydown', { bubbles: true, cancelable: true });
    Object.defineProperty(event, 'which', { value: which });
    linkWrapper.dispatchEvent(event);
  };

  link.click();
  const mouseOpened = example.classList.contains('is-open')
    && link.getAttribute('aria-expanded') === 'true';
  keydown(32);
  const spaceClosed = !example.classList.contains('is-open')
    && link.getAttribute('aria-expanded') === 'false';

  Drupal.attachBehaviors(document);
  keydown(13);
  return mouseOpened
    && spaceClosed
    && example.classList.contains('is-open')
    && link.getAttribute('aria-expanded') === 'true';
}())
JS);
  }

}
