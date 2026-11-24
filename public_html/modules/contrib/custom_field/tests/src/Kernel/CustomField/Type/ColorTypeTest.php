<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Type;

use Drupal\custom_field\Plugin\CustomField\FieldType\ColorType;
use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldTypeKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// cspell:ignore bcefa
/**
 * Tests the 'color' sub-field type.
 *
 * Modelled on core's field type Kernel tests, closest counterpart
 * core/modules/field/tests/src/Kernel/Color/ColorItemTest.php.
 *
 * ColorType does not override getConstraints(), and its single property uses
 * a plain DataDefinition::create('string'), whose "string" primitive carries
 * no automatic constraints of its own — so the 'required' field setting has
 * no validation effect at this level (it only affects the widget), and no
 * test is added for it. All of ColorType's actual behavior lives in
 * CustomItem::preSave()'s normalization step, which this class's round-trip
 * cases exercise directly.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldType\ColorType
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(ColorType::class)]
class ColorTypeTest extends CustomFieldTypeKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static function typeId(): string {
    return 'color';
  }

  /**
   * {@inheritdoc}
   */
  protected function roundTripCases(): array {
    return [
      'an already hashed uppercase value round-trips unchanged' => [
        'value' => ['sub' => '#000000'],
        'expected' => ['sub' => '#000000'],
      ],
      'a lowercase value without a leading # gets one and is converted to uppercase' => [
        'value' => ['sub' => '5bcefa'],
        'expected' => ['sub' => '#5BCEFA'],
      ],
      'surrounding whitespace is trimmed before normalizing' => [
        'value' => ['sub' => ' #abcdef '],
        'expected' => ['sub' => '#ABCDEF'],
      ],
      'mixed-case input is converted to uppercase' => [
        'value' => ['sub' => 'aAbBcC'],
        'expected' => ['sub' => '#AABBCC'],
      ],
      // CHARACTERIZATION, not contract. Normalization only checks the
      // resulting length, not whether the characters are valid hexadecimal
      // digits, so a six-character value that is not a real hex color is
      // accepted and normalized exactly like a valid one.
      'a non-hex six-character value is accepted and normalized like a real color' => [
        'value' => ['sub' => 'zzzzzz'],
        'expected' => ['sub' => '#ZZZZZZ'],
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
   * The schema is a fixed 7-character varchar column.
   */
  public function testSchema(): void {
    $this->assertSame(
      ['sub' => ['type' => 'varchar', 'length' => 7]],
      ColorType::schema(['name' => 'sub']),
    );
  }

  /**
   * The property definition is a single, optional string.
   */
  public function testPropertyDefinitions(): void {
    $properties = ColorType::propertyDefinitions(['name' => 'sub']);

    $this->assertSame(['sub'], array_keys($properties));
    $this->assertSame('string', $properties['sub']->getDataType());
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
    ], ColorType::defaultFieldSettings());
  }

  /**
   * The check_empty setting drops an item when a sibling column is populated.
   *
   * A populated sibling column is essential here: without one, every column is
   * empty and CustomItem::isEmpty()'s all-columns-empty fallback drops the
   * item regardless of check_empty.
   *
   * check_empty's own blank-value check runs once, early, via
   * CustomItem::isEmpty() during FieldItemList::filterEmptyItems() — before
   * CustomItem::preSave() normalizes the column. But that early check is not
   * the one that decides what actually gets persisted: the SQL storage's
   * dedicated-table save path re-runs filterEmptyItems() a second time,
   * immediately before insert, against the already-normalized values. So a
   * value that only becomes blank (NULL) as a result of preSave() — such as an
   * invalid-length color — is still correctly caught by the time it matters,
   * exactly like an ordinarily blank value would be. See
   * ::testInvalidLengthValueSilentlyBecomesNullWithoutViolation() for what
   * happens to the same kind of value when check_empty is off instead.
   *
   * @param bool $check_empty
   *   The check_empty setting on the column under test.
   * @param string $value
   *   The value to store in that column.
   * @param bool $expected
   *   Whether the item is expected to survive the save.
   *
   * @dataProvider providerCheckEmpty
   */
  #[DataProvider('providerCheckEmpty')]
  public function testCheckEmpty(bool $check_empty, string $value, bool $expected): void {
    $field_name = 'field_ce_' . (int) $check_empty . '_' . strlen($value);
    $this->createCustomField(
      $field_name,
      [
        'sub' => ['name' => 'sub', 'type' => 'color'],
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
   * Check_empty's own blank-value check runs before preSave() normalizes an
   * invalid-length value to NULL, but the save path re-checks emptiness again
   * immediately before writing to storage, after that normalization has
   * already happened — and that later check is what actually decides whether
   * the item survives. So an invalid-length value that ends up NULL is
   * correctly treated as blank by the time it matters, the same as any other
   * blank value.
   *
   * @return array<string, array{bool, string, bool}>
   *   Test cases.
   */
  public static function providerCheckEmpty(): array {
    return [
      'check_empty on, blank value' => [TRUE, '', FALSE],
      'check_empty on, populated value' => [TRUE, '#abcdef', TRUE],
      'check_empty off, blank value' => [FALSE, '', TRUE],
      'check_empty on, invalid-length value' => [TRUE, '#12345', FALSE],
    ];
  }

  /**
   * CHARACTERIZATION, not contract: invalid input silently becomes NULL.
   *
   * With a populated sibling column and check_empty OFF on the color column
   * itself, an invalid-length value neither triggers check_empty's drop logic
   * nor the all-columns-empty fallback (the sibling keeps the item alive), so
   * the item survives. The color column then silently reads back NULL with no
   * validation violation raised — no format constraint exists to reject the
   * input, only a length check inside normalization that discards anything
   * that doesn't fit. A future fix adding a format constraint would turn this
   * into a validation failure instead of a silent NULL write.
   *
   * Both conditions are required to observe this: a populated sibling alone
   * is not enough if check_empty is on for this column (see
   * ::providerCheckEmpty()'s 'check_empty on, invalid-length value' case,
   * which is correctly dropped), and check_empty off alone is not enough
   * without a sibling (a single-column item would be dropped by the
   * all-columns-empty fallback regardless).
   */
  public function testInvalidLengthValueSilentlyBecomesNullWithoutViolation(): void {
    $field_name = 'field_color_invalid_kept';
    $this->createCustomField(
      $field_name,
      [
        'sub' => ['name' => 'sub', 'type' => 'color'],
        'keep' => ['name' => 'keep', 'type' => 'string'],
      ],
      [
        'sub' => ['label' => 'Under test', 'check_empty' => FALSE],
        'keep' => ['label' => 'Populated sibling', 'check_empty' => FALSE],
      ],
    );

    $node = Node::create(['type' => 'page', 'title' => 'invalid color kept']);
    $node->set($field_name, [['sub' => '#12345', 'keep' => 'populated']]);

    $this->assertNoViolations($node);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item, 'The item survives: the sibling column keeps it alive.');
    $this->assertNull(
      $item->get('sub')->getValue(),
      'The invalid value is silently discarded to NULL instead of being rejected.',
    );
  }

}
