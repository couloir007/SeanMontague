<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Type;

use Drupal\custom_field\Plugin\CustomField\FieldType\MapType;
use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldTypeKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the 'map' sub-field type.
 *
 * Modelled on core's field type Kernel tests, closest counterpart
 * core/modules/field/tests/src/Kernel/Map/MapItemTest.php.
 *
 * MapType does not override getConstraints(), and its single property uses
 * MapDataDefinition, whose "map" primitive carries no automatic constraints
 * of its own — so the 'required' field setting has no validation effect at
 * this level (it only affects the widget), and no test is added for it.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldType\MapType
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(MapType::class)]
class MapTypeTest extends CustomFieldTypeKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static function typeId(): string {
    return 'map';
  }

  /**
   * {@inheritdoc}
   */
  protected function roundTripCases(): array {
    return [
      'a list of key/value pairs round-trips unchanged' => [
        'value' => [
          'sub' => [
          ['key' => 'Key1', 'value' => 'Value1'],
          ['key' => 'Key2', 'value' => 'Value2'],
          ],
        ],
        'expected' => [
          'sub' => [
          ['key' => 'Key1', 'value' => 'Value1'],
          ['key' => 'Key2', 'value' => 'Value2'],
          ],
        ],
      ],
      'a longer list of key/value pairs round-trips unchanged' => [
        'value' => [
          'sub' => [
          ['key' => 'New Key1', 'value' => 'New Value1'],
          ['key' => 'New Key2', 'value' => 'New Value2'],
          ['key' => 'New Key3', 'value' => 'New Value3'],
          ],
        ],
        'expected' => [
          'sub' => [
          ['key' => 'New Key1', 'value' => 'New Value1'],
          ['key' => 'New Key2', 'value' => 'New Value2'],
          ['key' => 'New Key3', 'value' => 'New Value3'],
          ],
        ],
      ],
      // CHARACTERIZATION, not contract. CustomItem::preSave() reassigns every
      // non-empty map value to array_values($subfield_value), which silently
      // drops string keys. A genuinely associative value — as opposed to the
      // list-of-{key,value}-pairs shape the map widget produces — therefore
      // loses its keys on save. This is a confirmed data-loss defect, not a
      // safe design; a future fix is expected to either preserve or reject
      // such input, and this expectation would then need to change.
      // ExtraPropertyApiTest::testArrayValuedSubfieldSurvivesRepeatedSetValue()
      // already pins the identical loss from the CustomItem::setValue() angle;
      // this case pins it independently at the type's own round-trip contract.
      'a genuinely associative value silently loses its keys' => [
        'value' => ['sub' => ['color' => 'red', 'size' => 'large']],
        'expected' => ['sub' => ['red', 'large']],
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
   * The schema is a single big, serialized blob column.
   */
  public function testSchema(): void {
    $this->assertSame(
      [
        'sub' => [
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
          'description' => 'A serialized array of values.',
        ],
      ],
      MapType::schema(['name' => 'sub']),
    );
  }

  /**
   * The property definition is a single, optional map.
   */
  public function testPropertyDefinitions(): void {
    $properties = MapType::propertyDefinitions(['name' => 'sub']);

    $this->assertSame(['sub'], array_keys($properties));
    $this->assertSame('map', $properties['sub']->getDataType());
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
    ], MapType::defaultFieldSettings());
  }

  /**
   * The check_empty setting drops an item when a sibling column is populated.
   *
   * Check_empty's own blank-value check runs once, early, via
   * CustomItem::isEmpty() during FieldItemList::filterEmptyItems() — before
   * CustomItem::preSave() normalizes the column. But that early check is not
   * the one that decides what actually gets persisted: the SQL storage's
   * dedicated-table save path re-runs filterEmptyItems() a second time,
   * immediately before insert, against the already-normalized values. So a
   * value that only becomes blank (NULL) as a result of preSave() — such as an
   * empty array — is still correctly caught by the time it matters, exactly
   * like an ordinarily blank value would be. See ::providerCheckEmpty() for
   * the empty-array rows this produces.
   *
   * @param bool $check_empty
   *   The check_empty setting on the column under test.
   * @param array<mixed>|null $value
   *   The value to store in that column.
   * @param bool $expected
   *   Whether the item is expected to survive the save.
   *
   * @dataProvider providerCheckEmpty
   */
  #[DataProvider('providerCheckEmpty')]
  public function testCheckEmpty(bool $check_empty, ?array $value, bool $expected): void {
    $field_name = 'field_ce_' . (int) $check_empty . '_' . ($value === NULL ? 'null' : 'set');
    $this->createCustomField(
      $field_name,
      [
        'sub' => ['name' => 'sub', 'type' => 'map'],
        'keep' => ['name' => 'keep', 'type' => 'string'],
      ],
      [
        'sub' => ['label' => 'Under test', 'check_empty' => $check_empty],
        'keep' => ['label' => 'Populated sibling', 'check_empty' => FALSE],
      ],
    );

    $node = Node::create(['type' => 'page', 'title' => 'check_empty']);
    $node->set($field_name, [['sub' => $value, 'keep' => 'populated']]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $expected
      ? $this->assertNotNull($item, 'The item was kept.')
      : $this->assertNull($item, 'The item was dropped as empty.');
  }

  /**
   * Data provider for ::testCheckEmpty().
   *
   * The two empty-array rows confirm that an empty array is, in the end,
   * treated the same as any other blank value: CustomItem::preSave() first
   * normalizes it to NULL, and the SQL storage's own re-check right before
   * insert (see ::testCheckEmpty()'s docblock) then applies check_empty to
   * that NULL exactly as it would to a value that started out blank.
   *
   * @return array<string, array{bool, array<mixed>|null, bool}>
   *   Test cases.
   */
  public static function providerCheckEmpty(): array {
    return [
      'check_empty on, blank value' => [TRUE, NULL, FALSE],
      'check_empty on, populated value' => [TRUE, [['key' => 'k', 'value' => 'v']], TRUE],
      'check_empty off, blank value' => [FALSE, NULL, TRUE],
      'check_empty on, empty array' => [TRUE, [], FALSE],
      'check_empty off, empty array' => [FALSE, [], TRUE],
    ];
  }

}
