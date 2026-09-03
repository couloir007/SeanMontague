<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\FieldFormatter;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the 'custom_list' (HTML list) field formatter.
 *
 * Covers render output for unordered/ordered lists, empty fields, and the
 * settings summary. AJAX settings-form behavior is deferred to a
 * FunctionalJavascript test if needed.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
final class ListFormatterTest extends FormatterTestBase {

  /**
   * {@inheritdoc}
   */
  protected string $displayType = 'custom_list';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->configureFormatter(['list_type' => 'ul']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests default unordered list rendering.
   */
  public function testUnorderedListRender(): void {
    $node = $this->createPopulatedNode([
      'string' => 'Alpha',
      'integer' => 7,
    ]);
    $this->drupalGet('/node/' . $node->id());
    $session = $this->assertSession();

    // Outer list uses the field name as a CSS class.
    $session->elementExists('css', 'ul.field-test.field-test--list');
    // Subfield values appear inside the list.
    $session->pageTextContains('Alpha');
    $session->pageTextContains('7');
    // Should not be an ordered list by default.
    $session->elementNotExists('css', 'ol.field-test');
  }

  /**
   * Tests ordered list rendering when list_type is ol.
   */
  public function testOrderedListRender(): void {
    $this->configureFormatter(['list_type' => 'ol']);
    $node = $this->createPopulatedNode([
      'string' => 'Beta',
      'integer' => 3,
    ]);
    $this->drupalGet('/node/' . $node->id());
    $session = $this->assertSession();

    $session->elementExists('css', 'ol.field-test.field-test--list');
    $session->pageTextContains('Beta');
    $session->pageTextContains('3');
    $session->elementNotExists('css', 'ul.field-test');
  }

  /**
   * Tests that an empty field produces no list markup.
   */
  public function testEmptyFieldProducesNoOutput(): void {
    $node = $this->drupalCreateNode([
      'type' => 'custom_field_entity_test',
      'title' => 'Empty field node',
      // No field_test values.
    ]);
    $this->drupalGet('/node/' . $node->id());
    $this->assertSession()->elementNotExists('css', 'ul.field-test, ol.field-test');
  }

  /**
   * Tests the settings summary reflects the list type.
   */
  public function testSettingsSummary(): void {
    $this->configureFormatter(['list_type' => 'ul']);
    $this->assertSettingsSummaryContains(['List type: Unordered list']);

    $this->configureFormatter(['list_type' => 'ol']);
    $this->assertSettingsSummaryContains(['List type: Numbered list']);
  }

}
