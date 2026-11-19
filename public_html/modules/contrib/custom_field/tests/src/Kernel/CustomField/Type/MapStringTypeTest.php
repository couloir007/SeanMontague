<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Type;

use Drupal\custom_field\Plugin\CustomField\FieldType\MapStringType;
use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldTypeKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the 'map_string' sub-field type.
 *
 * Modelled on core's field type Kernel tests, closest counterpart
 * core/modules/field/tests/src/Kernel/Map/MapItemTest.php.
 *
 * MapStringType extends MapType and only overrides generateSampleValue();
 * schema(), propertyDefinitions(), fieldSettingsForm() and the (inherited,
 * empty) getConstraints() are all unchanged, so this class's own coverage
 * mirrors MapTypeTest's, but exercised through MapStringType's own class so
 * TypeRegistryTest's per-class allowlist is satisfied. Like MapType, it has
 * no validation constraints of its own, so 'required' has no effect at this
 * level and is not tested here.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldType\MapStringType
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(MapStringType::class)]
class MapStringTypeTest extends CustomFieldTypeKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static function typeId(): string {
    return 'map_string';
  }

  /**
   * {@inheritdoc}
   */
  protected function roundTripCases(): array {
    return [
      'a flat list of strings round-trips unchanged' => [
        'value' => ['sub' => ['text1', 'text2', 'text3', 'text4']],
        'expected' => ['sub' => ['text1', 'text2', 'text3', 'text4']],
      ],
      'a different flat list of strings round-trips unchanged' => [
        'value' => ['sub' => ['new text1', 'new text2', 'new text3']],
        'expected' => ['sub' => ['new text1', 'new text2', 'new text3']],
      ],
      // CHARACTERIZATION, not contract. Same gap as MapType, pinned
      // independently here at map_string's own round-trip contract:
      // CustomItem::preSave() reassigns every non-empty value to
      // array_values($subfield_value), so a genuinely associative value loses
      // its keys on save. See MapTypeTest::roundTripCases() for the full
      // rationale and ExtraPropertyApiTest::
      // testArrayValuedSubfieldSurvivesRepeatedSetValue() for the same loss
      // pinned from the CustomItem::setValue() angle.
      'a genuinely associative value silently loses its keys' => [
        'value' => ['sub' => ['first' => 'alpha', 'second' => 'beta']],
        'expected' => ['sub' => ['alpha', 'beta']],
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
   * The schema is a single big, serialized blob column, inherited from map.
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
      MapStringType::schema(['name' => 'sub']),
    );
  }

  /**
   * The property definition is a single, optional map, inherited from map.
   */
  public function testPropertyDefinitions(): void {
    $properties = MapStringType::propertyDefinitions(['name' => 'sub']);

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
    ], MapStringType::defaultFieldSettings());
  }

  /**
   * The check_empty setting drops an item when a sibling column is populated.
   *
   * Same mechanism as MapTypeTest::testCheckEmpty(): check_empty's own blank-
   * value check runs once, early, via CustomItem::isEmpty() during
   * FieldItemList::filterEmptyItems() — before CustomItem::preSave()
   * normalizes the column. But that early check is not the one that decides
   * what actually gets persisted: the SQL storage's dedicated-table save path
   * re-runs filterEmptyItems() a second time, immediately before insert,
   * against the already-normalized values. So a value that only becomes blank
   * (NULL) as a result of preSave() — such as an empty array — is still
   * correctly caught by the time it matters, exactly like an ordinarily blank
   * value would be. See ::providerCheckEmpty() for the empty-array rows this
   * produces.
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
        'sub' => ['name' => 'sub', 'type' => 'map_string'],
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
      'check_empty on, populated value' => [TRUE, ['kept'], TRUE],
      'check_empty off, blank value' => [FALSE, NULL, TRUE],
      'check_empty on, empty array' => [TRUE, [], FALSE],
      'check_empty off, empty array' => [FALSE, [], TRUE],
    ];
  }

}
