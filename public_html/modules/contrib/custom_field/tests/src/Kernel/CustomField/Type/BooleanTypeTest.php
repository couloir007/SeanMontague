<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Type;

use Drupal\custom_field\Plugin\CustomField\FieldType\BooleanType;
use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldTypeKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the 'boolean' sub-field type.
 *
 * Modelled on core's field type Kernel tests, closest counterpart
 * core/modules/field/tests/src/Kernel/Boolean/BooleanItemTest.php.
 *
 * BooleanType is one of the "genuinely trivial" types: its schema(),
 * propertyDefinitions() and (inherited, empty) getConstraints() all ignore
 * $settings entirely, so there is no settings-variation matrix to build.
 * Its only distinguishing behavior is `never_check_empty: TRUE`, covered
 * below alongside CustomItem::isEmpty()'s sibling check_empty interaction.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldType\BooleanType
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(BooleanType::class)]
class BooleanTypeTest extends CustomFieldTypeKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static function typeId(): string {
    return 'boolean';
  }

  /**
   * {@inheritdoc}
   */
  protected function roundTripCases(): array {
    return [
      'string "1" round-trips as stored 1' => [
        'value' => ['sub' => '1'],
        'expected' => ['sub' => '1'],
      ],
      'zero (int) round-trips as 0' => [
        'value' => ['sub' => 0],
        'expected' => ['sub' => '0'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function validationCases(): array {
    return [];
  }

  /**
   * The schema is a fixed tiny int column; $settings is otherwise ignored.
   */
  public function testSchema(): void {
    $this->assertSame(
      ['sub' => ['type' => 'int', 'size' => 'tiny']],
      BooleanType::schema(['name' => 'sub']),
    );
  }

  /**
   * The property definition is a single, optional boolean.
   */
  public function testPropertyDefinitions(): void {
    $properties = BooleanType::propertyDefinitions(['name' => 'sub']);

    $this->assertSame(['sub'], array_keys($properties));
    $this->assertSame('boolean', $properties['sub']->getDataType());
    $this->assertFalse($properties['sub']->isRequired());
  }

  /**
   * The declared default field settings are the base class's unmodified.
   */
  public function testDefaultFieldSettings(): void {
    $this->assertSame([
      'label' => '',
      'required' => FALSE,
      'translatable' => FALSE,
      'description' => '',
      'description_display' => 'after',
      'check_empty' => FALSE,
    ], BooleanType::defaultFieldSettings());
  }

  /**
   * CONTRACT: a real, stored 0 is never treated as an empty value.
   *
   * CustomItem::isEmpty() only treats a subfield as blank when its raw value
   * is '' or (for a non-never_check_empty column) NULL; a boolean 0 is
   * neither, so a single-column field carrying only 0 survives the
   * all-columns-empty fallback. This is "0 is a real FALSE, not an absence."
   */
  public function testZeroIsNotTreatedAsEmpty(): void {
    $field_name = $this->createCaseField([], 'zero_alone');

    $node = Node::create(['type' => 'page', 'title' => 'zero alone']);
    $node->set($field_name, [['sub' => 0]]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item, 'A real 0 value is not dropped by the all-columns-empty fallback.');
    $this->assertSame('0', $item->get('sub')->getValue());
  }

  /**
   * CONTRACT: 0 survives next to a blank sibling that has check_empty off.
   *
   * A populated boolean 0 plus one blank sibling is only one empty column out
   * of two, and that column's own check_empty is off, so neither of
   * CustomItem::isEmpty()'s two removal paths (this-column-checked, or
   * every-column-empty) fires.
   */
  public function testZeroSurvivesAlongsideNonCheckedEmptySibling(): void {
    $field_name = 'field_boolean_ce_kept';
    $this->createCustomField(
      $field_name,
      [
        'sub' => ['name' => 'sub', 'type' => 'boolean'],
        'keep' => ['name' => 'keep', 'type' => 'string'],
      ],
      [
        'sub' => ['label' => 'Under test', 'check_empty' => FALSE],
        'keep' => ['label' => 'Blank sibling', 'check_empty' => FALSE],
      ],
    );

    $node = Node::create(['type' => 'page', 'title' => 'zero with blank sibling']);
    $node->set($field_name, [['sub' => 0, 'keep' => '']]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item, 'The item survives: only one of two columns is empty, and that column has check_empty off.');
    $this->assertSame('0', $item->get('sub')->getValue());
  }

  /**
   * CONTRACT: a sibling's own check_empty drops the row despite a real 0.
   *
   * The boolean column's never_check_empty protects only its OWN emptiness
   * from the all-columns-empty fallback; it is not a veto over the rest of
   * the item. When the sibling column is itself blank and has check_empty
   * TRUE, CustomItem::isEmpty() returns TRUE on that sibling's iteration,
   * regardless of what the boolean column holds.
   */
  public function testSiblingCheckEmptyDropsTheRowDespitePopulatedBoolean(): void {
    $field_name = 'field_boolean_ce_dropped';
    $this->createCustomField(
      $field_name,
      [
        'sub' => ['name' => 'sub', 'type' => 'boolean'],
        'keep' => ['name' => 'keep', 'type' => 'string'],
      ],
      [
        'sub' => ['label' => 'Under test', 'check_empty' => FALSE],
        'keep' => ['label' => 'Checked sibling', 'check_empty' => TRUE],
      ],
    );

    $node = Node::create(['type' => 'page', 'title' => 'zero with checked blank sibling']);
    $node->set($field_name, [['sub' => 0, 'keep' => '']]);
    $node->save();

    $this->assertNull(
      $this->storedItem($node, $field_name),
      "The sibling column's own check_empty, not the boolean column's never_check_empty, decides the item's fate.",
    );
  }

  /**
   * CONTRACT: NULL in a never_check_empty column is not treated as empty.
   *
   * Deliberately the same single-column field shape as
   * ::testEmptyStringInBooleanColumnStillCountsAsEmpty() immediately below —
   * the only variable that differs between the two tests is the value
   * written to the never_check_empty column, which makes the NULL-vs-''
   * asymmetry directly visible. With only one column, CustomItem::isEmpty()'s
   * all-columns-empty fallback ($emptyCounter === $field_count) fires as soon
   * as that single column counts as empty at all, so whether the item
   * survives here depends entirely on whether NULL counts. It does not: the
   * never_check_empty exemption ($item_value === NULL && !$no_check) guards
   * exactly this branch, so $emptyCounter never increments and the item is
   * kept.
   */
  public function testNullInNeverCheckEmptyColumnIsNotTreatedAsEmpty(): void {
    $field_name = $this->createCaseField([], 'null_alone');

    $node = Node::create(['type' => 'page', 'title' => 'null boolean alone']);
    $node->set($field_name, [['sub' => NULL]]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item, "A never_check_empty column's NULL value is invisible to the all-columns-empty fallback.");
    $this->assertNull($item->get('sub')->getValue(), 'The NULL value round-trips unchanged.');
  }

  /**
   * CHARACTERIZATION, not contract: an empty string still counts as empty.
   *
   * Same single-column field shape as
   * ::testNullInNeverCheckEmptyColumnIsNotTreatedAsEmpty() immediately
   * above, with only the written value differing: NULL survives, '' does
   * not. CustomItem::isEmpty()'s never_check_empty exemption only guards the
   * NULL branch of its condition ($item_value === NULL && !$no_check); the
   * $item_value === '' branch is unconditional. A boolean subfield's property
   * (DataDefinition::create('boolean')) does not coerce the incoming value on
   * setValue() — Drupal\Core\TypedData\PrimitiveBase stores it verbatim — so
   * an explicit '' reaches isEmpty() unchanged and the all-columns-empty
   * fallback fires immediately, exactly as it would for a plain string
   * column. This is questionable under the "always meaningful" definition
   * never_check_empty is meant to express, but it is the current behavior,
   * not an asserted contract.
   */
  public function testEmptyStringInBooleanColumnStillCountsAsEmpty(): void {
    $field_name = $this->createCaseField([], 'empty_string');

    $node = Node::create(['type' => 'page', 'title' => 'empty string boolean']);
    $node->set($field_name, [['sub' => '']]);
    $node->save();

    $this->assertNull(
      $this->storedItem($node, $field_name),
      'An empty string is dropped even though the column is never_check_empty.',
    );
  }

}
