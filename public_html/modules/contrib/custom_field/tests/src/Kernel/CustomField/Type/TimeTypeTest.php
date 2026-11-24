<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Type;

use Drupal\custom_field\Plugin\CustomField\FieldType\TimeType;
use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldTypeKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the 'time' sub-field type.
 *
 * Modelled loosely on core's field type Kernel tests, closest counterpart
 * core/modules/field/tests/src/Kernel/Timestamp/TimestampItemTest.php.
 *
 * TimeType stores seconds passed through midnight in an unsigned int column
 * and declares a CustomFieldTime constraint, so validationCases() is
 * non-empty. It also has a reserved "empty" sentinel (86401) that the
 * validator treats as valid but that CustomItem::preSave() then converts to a
 * real NULL at save time; that behavior needs a two-column fixture to observe
 * because a single-column item whose only value becomes NULL can get dropped
 * entirely by CustomItem::isEmpty()'s all-columns-empty fallback, so it gets
 * its own dedicated test methods rather than living in roundTripCases().
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldType\TimeType
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(TimeType::class)]
class TimeTypeTest extends CustomFieldTypeKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static function typeId(): string {
    return 'time';
  }

  /**
   * {@inheritdoc}
   */
  protected function roundTripCases(): array {
    return [
      'midnight, the minimum of the valid range' => [
        'value' => ['sub' => 0],
        'expected' => ['sub' => '0'],
      ],
      'end of day, the maximum of the valid range' => [
        'value' => ['sub' => 86400],
        'expected' => ['sub' => '86400'],
      ],
      // Time(13, 40, 30)->getTimestamp().
      'afternoon time' => [
        'value' => ['sub' => 49230],
        'expected' => ['sub' => '49230'],
      ],
      // Time(6, 40, 30)->getTimestamp().
      'morning time' => [
        'value' => ['sub' => 24030],
        'expected' => ['sub' => '24030'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function validationCases(): array {
    return [
      // The max time in range is 86400.
      'value above the valid range' => [
        'value' => ['sub' => 90400],
        'property' => 'sub',
        'message' => 'The value 90400 is not a valid time.',
      ],
    ];
  }

  /**
   * The schema is a single unsigned int column.
   */
  public function testSchema(): void {
    $this->assertSame(
      ['sub' => ['type' => 'int', 'unsigned' => TRUE]],
      TimeType::schema(['name' => 'sub']),
    );
  }

  /**
   * The property definition is a single, unsigned, optional integer.
   */
  public function testPropertyDefinitions(): void {
    $properties = TimeType::propertyDefinitions(['name' => 'sub']);

    $this->assertSame(['sub'], array_keys($properties));
    $this->assertSame('integer', $properties['sub']->getDataType());
    $this->assertFalse($properties['sub']->isRequired());
    $this->assertTrue($properties['sub']->getSetting('unsigned'));
    $this->assertSame(
      'Seconds passed through midnight',
      (string) $properties['sub']->getDescription(),
    );
  }

  /**
   * The declared default field settings, including time's seconds options.
   */
  public function testDefaultFieldSettings(): void {
    $this->assertSame([
      'seconds_enabled' => FALSE,
      'seconds_step' => 5,
      'label' => '',
      'required' => FALSE,
      'translatable' => FALSE,
      'description' => '',
      'description_display' => 'after',
      'check_empty' => FALSE,
    ], TimeType::defaultFieldSettings());
  }

  /**
   * The empty sentinel (86401) passes validation but normalizes to NULL.
   *
   * A populated sibling column is essential here: without one, every column
   * would be empty and CustomItem::isEmpty()'s all-columns-empty fallback
   * would drop the item regardless of what actually happened to the "sub"
   * column.
   */
  public function testSentinelPassesValidationButNormalizesToNullWithSibling(): void {
    $field_name = 'field_time_sentinel_sibling';
    $this->createCustomField(
      $field_name,
      [
        'sub' => ['name' => 'sub', 'type' => 'time'],
        'keep' => ['name' => 'keep', 'type' => 'string'],
      ],
      [
        'sub' => ['label' => 'Under test', 'check_empty' => FALSE],
        'keep' => ['label' => 'Populated sibling', 'check_empty' => FALSE],
      ],
    );

    $node = Node::create(['type' => 'page', 'title' => 'time sentinel']);
    $node->set($field_name, [['sub' => 86401, 'keep' => 'populated']]);

    $this->assertNoViolations($node, 'The sentinel is treated as empty by the validator, so it produces no violation.');
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item, 'The item survives: the sibling column keeps it alive.');
    $this->assertNull(
      $item->get('sub')->getValue(),
      'The sentinel is converted to a real NULL at save time.',
    );
    $this->assertSame('populated', $item->get('keep')->getValue());
  }

  /**
   * Exploratory: what happens to a single-column field given the sentinel.
   *
   * This traces through CustomItem::isEmpty() and preSave(): the item's
   * FieldItemList::preSave() calls filterEmptyItems() (using isEmpty())
   * BEFORE delegating to CustomItem::preSave(), so at that point the column
   * still holds 86401, which is neither NULL nor an empty string and is
   * therefore not filtered out yet. CustomItem::preSave() then normalizes the
   * value to NULL. Immediately before the SQL insert,
   * SqlContentEntityStorage re-runs filterEmptyItems() a second time against
   * the already-normalized values; with a single column now NULL, isEmpty()'s
   * all-columns-empty fallback applies and the item is dropped. The outcome
   * -- no item after a reload -- is what this test asserts; the two-pass
   * mechanism behind it is traced from the source and not asserted step by
   * step.
   */
  public function testSentinelOnSingleColumnFieldIsDropped(): void {
    $field_name = $this->createCaseField([], 'sentinel_single');

    $node = Node::create(['type' => 'page', 'title' => 'time sentinel single column']);
    $node->set($field_name, [['sub' => 86401]]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNull($item, 'The single-column item is dropped: after normalization its only column is NULL.');
  }

  /**
   * The check_empty setting drops an item when a sibling column is populated.
   *
   * @param bool $check_empty
   *   The check_empty setting on the column under test.
   * @param int|null $value
   *   The value to store in that column.
   * @param bool $expected
   *   Whether the item is expected to survive the save.
   *
   * @dataProvider providerCheckEmpty
   */
  #[DataProvider('providerCheckEmpty')]
  public function testCheckEmpty(bool $check_empty, ?int $value, bool $expected): void {
    $field_name = 'field_ce_' . (int) $check_empty . '_' . ($value ?? 'null');
    $this->createCustomField(
      $field_name,
      [
        'sub' => ['name' => 'sub', 'type' => 'time'],
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
   * @return array<string, array{bool, int|null, bool}>
   *   Test cases.
   */
  public static function providerCheckEmpty(): array {
    return [
      'check_empty on, blank value' => [TRUE, NULL, FALSE],
      'check_empty on, populated value' => [TRUE, 3600, TRUE],
      'check_empty off, blank value' => [FALSE, NULL, TRUE],
      // CONTRACT: 0 (midnight) is a real, valid time, not a blank value, so
      // check_empty must not treat it as empty.
      'check_empty on, midnight (0)' => [TRUE, 0, TRUE],
      // CONTRACT: CustomItem::preSave() normalizes the sentinel to NULL
      // before the storage layer's own late empty-item filtering pass runs,
      // so with check_empty on for this column, the item is dropped like any
      // other NULL.
      'check_empty on, 86401 sentinel' => [TRUE, 86401, FALSE],
    ];
  }

}
