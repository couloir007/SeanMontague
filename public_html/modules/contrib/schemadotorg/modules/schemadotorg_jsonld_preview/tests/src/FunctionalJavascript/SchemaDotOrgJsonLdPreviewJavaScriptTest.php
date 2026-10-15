<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_jsonld_preview\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\NodeInterface;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;

/**
 * Tests Schema.org JSON-LD preview JavaScript behavior.
 *
 * @group schemadotorg
 */
class SchemaDotOrgJsonLdPreviewJavaScriptTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'schemadotorg_jsonld_preview'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The node with a Schema.org mapping.
   */
  protected NodeInterface $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('schemadotorg_jsonld_preview');
    $this->drupalCreateContentType(['type' => 'thing']);
    $this->node = $this->drupalCreateNode([
      'type' => 'thing',
      'title' => 'Something',
    ]);

    SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'thing',
      'schema_type' => 'Thing',
      'schema_properties' => ['title' => 'name'],
    ])->save();
    drupal_flush_all_caches();

    $this->drupalLogin($this->drupalCreateUser([
      'access content',
      'view schemadotorg jsonld',
    ]));
  }

  /**
   * Tests JSON-LD preview copy feedback.
   */
  public function testJsonLdPreviewJavaScript(): void {
    $this->drupalGet($this->node->toUrl());

    // Check the rendered preview copy controls are initially hidden.
    $this->assertJsCondition(<<<'JS'
(function () {
  const preview = document.querySelector('.js-schemadotorg-jsonld-preview');
  const input = preview.querySelector('input[type="hidden"]');
  const button = preview.querySelector('.schemadotorg-jsonld-preview-copy-button');
  const message = preview.querySelector('.schemadotorg-jsonld-preview-copy-message');
  return input.value.includes('"@type": "Thing"')
    && button !== null
    && getComputedStyle(message).display === 'none';
}())
JS);

    // Open the real preview details before clicking its control.
    $this->getSession()->executeScript(<<<'JS'
document.querySelector('.js-schemadotorg-jsonld-preview').closest('details').open = true;
JS);

    // Replace the browser clipboard with a local write spy.
    $this->getSession()->executeScript(<<<'JS'
window.schemaDotOrgJsonLdPreviewClipboard = [];
Object.defineProperty(window.navigator, 'clipboard', {
  configurable: true,
  value: {
    writeText(value) {
      window.schemaDotOrgJsonLdPreviewClipboard.push(value);
      return Promise.resolve();
    },
  },
});
JS);

    $button = $this->getSession()->getPage()->find('css', '.schemadotorg-jsonld-preview-copy-button');
    $this->assertNotEmpty($button);
    $button->click();

    // Check that copying shows the message and announces the action.
    $this->assertJsCondition(<<<'JS'
(function () {
  const preview = document.querySelector('.js-schemadotorg-jsonld-preview');
  const input = preview.querySelector('input[type="hidden"]');
  const message = preview.querySelector('.schemadotorg-jsonld-preview-copy-message');
  const copied = `<script type="application/ld+json">
${input.value}
</script>`;
  return window.schemaDotOrgJsonLdPreviewClipboard.length === 1
    && window.schemaDotOrgJsonLdPreviewClipboard[0] === copied
    && message.style.display === 'inline-block'
    && document.querySelector('#drupal-live-announce').innerText === 'JSON-LD copied to clipboard…';
}())
JS);

    // Trigger the completed fade transition.
    $this->getSession()->executeScript(<<<'JS'
document.querySelector('.schemadotorg-jsonld-preview-copy-message')
  .dispatchEvent(new Event('transitionend'));
JS);

    // Check that the completed transition hides and resets the message.
    $this->assertJsCondition(<<<'JS'
(function () {
  const message = document.querySelector('.schemadotorg-jsonld-preview-copy-message');
  return message.style.display === 'none' && message.style.opacity === '1';
}())
JS);

    // Reattach behaviors and trigger another copy.
    $this->getSession()->executeScript(<<<'JS'
Drupal.attachBehaviors(document);
document.querySelector('.schemadotorg-jsonld-preview-copy-button').click();
JS);

    // Check that reattachment adds no duplicate copy handler or announcement.
    $this->assertJsCondition(<<<'JS'
window.schemaDotOrgJsonLdPreviewClipboard.length === 2
  && document.querySelector('#drupal-live-announce').innerText === 'JSON-LD copied to clipboard…'
JS);
  }

}
