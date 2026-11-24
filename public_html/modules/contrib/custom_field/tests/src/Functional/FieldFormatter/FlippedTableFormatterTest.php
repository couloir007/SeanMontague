<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\FieldFormatter;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the 'flipped_table' field formatter.
 *
 * Covers flipped table markup (subfield labels as row headers), hide-empty
 * rows, and the settings summary. Label-display option changes in the
 * settings form are deferred to FunctionalJavascript coverage.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
final class FlippedTableFormatterTest extends FormatterTestBase {

  /**
   * {@inheritdoc}
   */
  protected string $displayType = 'flipped_table';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->fieldName = 'field_test';
    $this->configureFormatter([
      'hide_empty' => FALSE,
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests basic flipped table structure for a single-value field.
   */
  public function testBasicFlippedTableRender(): void {
    $node = $this->createPopulatedNode([
      'string' => 'Flipped value',
      'integer' => 21,
    ]);
    $this->drupalGet('/node/' . $node->id());
    $session = $this->assertSession();

    $session->elementExists('css', 'table.field-test');
    // Flipped table has no thead; labels are row headers.
    $session->elementExists('css', 'table.field-test th[scope="row"]');
    $session->pageTextContains('Flipped value');
    $session->pageTextContains('21');
    // Subfield labels appear as the first column (row headers).
    $session->pageTextContains('String');
    $session->pageTextContains('Integer');
  }

  /**
   * Tests multi-value flipped table (one column per delta).
   */
  public function testMultiValueFlippedTable(): void {
    $this->fieldName = 'field_test_multiple';
    $this->configureFormatter([
      'hide_empty' => FALSE,
    ]);

    $node = $this->drupalCreateNode([
      'type' => 'custom_field_entity_test',
      'title' => 'Multi flipped node',
      'field_test_multiple' => [
        ['string' => 'One', 'integer' => 10],
        ['string' => 'Two', 'integer' => 20],
      ],
    ]);
    $this->drupalGet('/node/' . $node->id());
    $session = $this->assertSession();

    $session->elementExists('css', 'table.field-test-multiple');
    $session->pageTextContains('One');
    $session->pageTextContains('Two');
    $session->pageTextContains('10');
    $session->pageTextContains('20');
    // Row headers for subfields still present.
    $session->elementExists('css', 'table.field-test-multiple th[scope="row"]');
  }

  /**
   * Tests that an empty field produces no table.
   */
  public function testEmptyFieldProducesNoOutput(): void {
    $this->fieldName = 'field_test';

    $node = $this->drupalCreateNode([
      'type' => 'custom_field_entity_test',
      'title' => 'Empty flipped node',
      $this->fieldName => [],
    ]);

    $node = $this->entityTypeManager->getStorage('node')->loadUnchanged($node->id());
    $this->assertSame(
      [],
      $node->get($this->fieldName)->getValue(),
      'Empty field should have no stored deltas.',
    );

    $this->drupalGet('/node/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Empty flipped node');
    $this->assertSession()->elementNotExists('css', 'table.field-test');
  }

  /**
   * Tests the settings summary for flipped table options.
   */
  public function testSettingsSummary(): void {
    $this->configureFormatter(['hide_empty' => FALSE]);
    $this->assertSettingsSummaryContains([
      'Hide rows with empty columns: No',
    ]);

    $this->configureFormatter(['hide_empty' => TRUE]);
    $this->assertSettingsSummaryContains([
      'Hide rows with empty columns: Yes',
    ]);
  }

}
