<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\FieldFormatter;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the 'custom_table' field formatter.
 *
 * Covers table markup, header visibility, hide-empty columns, multi-value
 * sorting, and the settings summary. Form process callbacks and AJAX are
 * deferred to FunctionalJavascript coverage.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
final class TableFormatterTest extends FormatterTestBase {

  /**
   * {@inheritdoc}
   */
  protected string $displayType = 'custom_table';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->fieldName = 'field_test';
    $this->configureFormatter([
      'sort_by' => '_delta',
      'sort_order' => 'asc',
      'hide_empty' => FALSE,
      'hide_header' => FALSE,
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests basic table structure and cell values for a single-value field.
   */
  public function testBasicTableRender(): void {
    $node = $this->createPopulatedNode([
      'string' => 'Table cell',
      'integer' => 15,
    ]);
    $this->drupalGet('/node/' . $node->id());
    $session = $this->assertSession();

    $session->elementExists('css', 'table.field-test');
    // Header row is present by default.
    $session->elementExists('css', 'table.field-test thead');
    $session->pageTextContains('Table cell');
    $session->pageTextContains('15');
    // Subfield labels appear as column headers (from field config / settings).
    $session->pageTextContains('String');
    $session->pageTextContains('Integer');
  }

  /**
   * Tests that hide_header omits the table header from the render array.
   */
  public function testHideHeader(): void {
    $this->configureFormatter([
      'hide_header' => TRUE,
      'sort_by' => '_delta',
      'sort_order' => 'asc',
      'hide_empty' => FALSE,
    ]);

    $component = $this->getFieldComponent();
    $this->assertTrue(
      !empty($component['settings']['hide_header']),
      'hide_header setting should be TRUE on the view display component.',
    );

    $node = $this->createPopulatedNode([
      'string' => 'No header',
    ]);

    // Assert via the render array so theme quirks cannot mask the setting.
    $display = EntityViewDisplay::load($this->viewDisplay);
    $build = $display->build($node);
    $this->assertArrayHasKey($this->fieldName, $build);
    $field_build = $build[$this->fieldName];

    // Walk the field build to the table element produced by the formatter.
    $table = $this->findTableElement($field_build);
    $this->assertNotNull($table, 'Expected a table render element for the field.');
    // Plugin sets #header to [] when hide_header is TRUE.
    $this->assertSame(
      [],
      $table['#header'] ?? NULL,
      'Table #header must be empty when hide_header is TRUE.',
    );

    // Still visible on the page as cell content.
    $this->drupalGet('/node/' . $node->id());
    $this->assertSession()->pageTextContains('No header');
  }

  /**
   * Tests multi-value rows and sort by a subfield.
   */
  public function testMultiValueSortByString(): void {
    // Use the multi-value test field for multiple table rows.
    // (Reset $this->fieldName in other tests that expect field_test.)
    $this->fieldName = 'field_test_multiple';
    $this->configureFormatter([
      'sort_by' => 'string',
      'sort_order' => 'asc',
      'hide_empty' => FALSE,
      'hide_header' => FALSE,
    ]);

    $node = $this->drupalCreateNode([
      'type' => 'custom_field_entity_test',
      'title' => 'Multi table node',
      'field_test_multiple' => [
        ['string' => 'Charlie', 'integer' => 3],
        ['string' => 'Alpha', 'integer' => 1],
        ['string' => 'Bravo', 'integer' => 2],
      ],
    ]);
    $this->drupalGet('/node/' . $node->id());
    $session = $this->assertSession();

    $session->elementExists('css', 'table.field-test-multiple');
    // All three values present.
    $session->pageTextContains('Alpha');
    $session->pageTextContains('Bravo');
    $session->pageTextContains('Charlie');

    // Ascending sort by string: Alpha before Bravo before Charlie.
    $page_text = $this->getSession()->getPage()->getText();
    $pos_alpha = strpos($page_text, 'Alpha');
    $pos_bravo = strpos($page_text, 'Bravo');
    $pos_charlie = strpos($page_text, 'Charlie');
    $this->assertNotFalse($pos_alpha);
    $this->assertNotFalse($pos_bravo);
    $this->assertNotFalse($pos_charlie);
    $this->assertLessThan($pos_bravo, $pos_alpha);
    $this->assertLessThan($pos_charlie, $pos_bravo);
  }

  /**
   * Tests that an empty field produces no table output.
   *
   * When the field has no stored deltas, the formatter returns an empty build
   * and the page has no table.field-test markup.
   */
  public function testEmptyFieldProducesNoOutput(): void {
    // Ensure prior tests that switch $fieldName cannot affect this case.
    $this->fieldName = 'field_test';

    $node = $this->drupalCreateNode([
      'type' => 'custom_field_entity_test',
      'title' => 'Empty table node unique title',
      // Explicit empty value list.
      $this->fieldName => [],
    ]);

    // Assert against storage, not only the in-memory entity.
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->entityTypeManager->getStorage('node')->loadUnchanged($node->id());
    $this->assertSame(
      [],
      $node->get($this->fieldName)->getValue(),
      'Empty field should have no stored deltas.',
    );

    $display = EntityViewDisplay::load($this->viewDisplay);
    $build = $display->build($node);

    if (isset($build[$this->fieldName])) {
      $this->assertNull(
        $this->findTableElement($build[$this->fieldName]),
        'Empty field must not produce a table render element.',
      );
    }

    $this->drupalGet('/node/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Empty table node unique title');
    $this->assertSession()->elementNotExists('css', 'table.field-test');
  }

  /**
   * Finds the first table theme element in a render array.
   *
   * @param array $build
   *   A render array (field build or child).
   *
   * @return array|null
   *   The table element, or NULL if not found.
   */
  protected function findTableElement(array $build): ?array {
    if (($build['#theme'] ?? NULL) === 'table') {
      return $build;
    }
    foreach ($build as $key => $child) {
      if (!is_array($child) || (is_string($key) && $key[0] === '#')) {
        continue;
      }
      $found = $this->findTableElement($child);
      if ($found !== NULL) {
        return $found;
      }
    }
    return NULL;
  }

  /**
   * Tests the settings summary for table formatter options.
   */
  public function testSettingsSummary(): void {
    $this->configureFormatter([
      'sort_by' => '_delta',
      'sort_order' => 'asc',
      'hide_empty' => FALSE,
      'hide_header' => FALSE,
    ]);
    $this->assertSettingsSummaryContains([
      'Sort by: Original order (Ascending)',
      'Hide columns with empty rows: No',
      'Hide table header: No',
    ]);

    $this->configureFormatter([
      'sort_by' => '_delta',
      'sort_order' => 'desc',
      'hide_empty' => TRUE,
      'hide_header' => TRUE,
    ]);
    $this->assertSettingsSummaryContains([
      'Sort by: Original order (Descending)',
      'Hide columns with empty rows: Yes',
      'Hide table header: Yes',
    ]);
  }

}
