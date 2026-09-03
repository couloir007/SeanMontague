<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\FieldFormatter;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the 'custom_inline' field formatter.
 *
 * Covers inline rendering with and without labels, custom separators, and the
 * settings summary. The show_labels #states visibility is deferred to a
 * FunctionalJavascript test.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
final class InlineFormatterTest extends FormatterTestBase {

  /**
   * {@inheritdoc}
   */
  protected string $displayType = 'custom_inline';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->configureFormatter([
      'show_labels' => FALSE,
      'label_separator' => ': ',
      'item_separator' => ', ',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests inline output without labels (default).
   */
  public function testInlineWithoutLabels(): void {
    $node = $this->createPopulatedNode([
      'string' => 'First',
      'integer' => 42,
    ]);
    $this->drupalGet('/node/' . $node->id());
    $session = $this->assertSession();

    // Values appear on the page.
    $session->pageTextContains('First');
    $session->pageTextContains('42');
    // Default item separator is present between items.
    $session->responseContains(', ');
    // Labels should not be shown when show_labels is FALSE.
    // Subfield labels are "String" / "Integer" from field config.
    $page_text = $this->getSession()->getPage()->getText();
    // The label + separator pattern should not appear for string.
    $this->assertStringNotContainsString('String:', $page_text);
  }

  /**
   * Tests inline output with labels and the default label separator.
   */
  public function testInlineWithLabels(): void {
    $this->configureFormatter([
      'show_labels' => TRUE,
      'label_separator' => ': ',
      'item_separator' => ', ',
    ]);
    $node = $this->createPopulatedNode([
      'string' => 'Labeled',
      'integer' => 9,
    ]);
    $this->drupalGet('/node/' . $node->id());
    $session = $this->assertSession();

    $session->pageTextContains('Labeled');
    $session->pageTextContains('9');
    // Labels from field config should appear with the separator.
    $session->pageTextContains('String');
    $session->pageTextContains('Integer');
    $session->responseContains('String: ');
  }

  /**
   * Tests custom item and label separators.
   */
  public function testCustomSeparators(): void {
    $this->configureFormatter([
      'show_labels' => TRUE,
      'label_separator' => ' = ',
      'item_separator' => ' | ',
    ]);
    $node = $this->createPopulatedNode([
      'string' => 'A',
      'integer' => 1,
    ]);
    $this->drupalGet('/node/' . $node->id());

    $this->assertSession()->responseContains(' = ');
    $this->assertSession()->responseContains(' | ');
    $this->assertSession()->pageTextContains('A');
    $this->assertSession()->pageTextContains('1');
  }

  /**
   * Tests that an empty field does not render subfield values or separators.
   *
   * Core may still emit the outer field wrapper when the component is enabled
   * on the display; we only assert that no inline subfield content is present.
   */
  public function testEmptyFieldProducesNoOutput(): void {
    $node = $this->drupalCreateNode([
      'type' => 'custom_field_entity_test',
      'title' => 'Empty inline node',
    ]);
    $this->drupalGet('/node/' . $node->id());
    $session = $this->assertSession();
    // Default sample values from other tests must not appear.
    $session->pageTextNotContains('Test string');
    $session->pageTextNotContains('test@example.com');
    // No per-subfield markup left behind from an empty value set.
    $session->responseNotContains('class="field--name-string"');
  }

  /**
   * Tests the settings summary for inline formatter options.
   */
  public function testSettingsSummary(): void {
    // Default: show_labels FALSE, item_separator ', '.
    $this->configureFormatter([
      'show_labels' => FALSE,
      'label_separator' => ': ',
      'item_separator' => ', ',
    ]);
    // Item separator always appears in the summary.
    $this->assertSettingsSummaryContains(['Item separator: , ']);

    // When show_labels is TRUE, summary should report Yes and the label sep.
    $this->configureFormatter([
      'show_labels' => TRUE,
      'label_separator' => ' :: ',
      'item_separator' => ' / ',
    ]);
    // pageTextContains normalizes whitespace, so leading spaces in the
    // separator values are collapsed with the space after the colon.
    $this->assertSettingsSummaryContains([
      'Show labels: Yes',
      'Label separator: ::',
      'Item separator: /',
    ]);
  }

}
