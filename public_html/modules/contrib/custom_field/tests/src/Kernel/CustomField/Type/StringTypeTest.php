<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Type;

use Drupal\custom_field\Plugin\CustomField\FieldType\StringType;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldTypeKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// cspell:ignore áéíóúñçüàè
/**
 * Tests the 'string' sub-field type.
 *
 * Modelled on core's field type Kernel tests, closest counterpart
 * core/modules/field/tests/src/Kernel/Email/EmailItemTest.php.
 *
 * Every case builds its own field with explicit settings; nothing here reads
 * custom_field_test's shipped config.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldType\StringType
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(StringType::class)]
class StringTypeTest extends CustomFieldTypeKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static function typeId(): string {
    return 'string';
  }

  /**
   * {@inheritdoc}
   */
  protected function roundTripCases(): array {
    return [
      'plain value' => [
        'value' => ['sub' => 'A plain string value'],
        'expected' => ['sub' => 'A plain string value'],
      ],
      'at the configured length' => [
        'column' => ['length' => 50],
        'value' => ['sub' => str_repeat('a', 50)],
        'expected' => ['sub' => str_repeat('a', 50)],
      ],
      'multibyte characters count as characters, not bytes' => [
        'column' => ['length' => 10],
        'value' => ['sub' => 'áéíóúñçüàè'],
        'expected' => ['sub' => 'áéíóúñçüàè'],
      ],
      'prefix and suffix do not touch the stored value' => [
        'settings' => ['prefix' => '$ ', 'suffix' => ' /mo'],
        'value' => ['sub' => '42'],
        'expected' => ['sub' => '42'],
      ],
      'a value outside allowed_values is stored' => [
        // CONTRACT, by design. allowed_values is optional and supplies widget
        // options only; it is deliberately NOT enforced on save, unlike core's
        // list fields, whose ListItemBase adds an AllowedValues constraint.
        // This test exists to stop that being "fixed" by accident: enforcing
        // the list on save would reject values that Feeds, JSON:API,
        // migrations and programmatic writes have already stored.
        'settings' => ['allowed_values' => [['key' => 'red', 'label' => 'Red']]],
        'value' => ['sub' => 'not-in-the-list'],
        'expected' => ['sub' => 'not-in-the-list'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function validationCases(): array {
    return [
      'one character over the configured length' => [
        'column' => ['length' => 50],
        'value' => ['sub' => str_repeat('a', 51)],
        'property' => 'sub',
        'message' => 'sub: may not be longer than 50 characters.',
      ],
    ];
  }

  /**
   * The schema is a varchar sized from the column settings.
   *
   * @param array<string, mixed> $column
   *   Column settings to merge over the name.
   * @param int $expected
   *   The expected varchar length.
   *
   * @dataProvider providerSchemaLength
   */
  #[DataProvider('providerSchemaLength')]
  public function testSchema(array $column, int $expected): void {
    $schema = StringType::schema(['name' => 'sub'] + $column);

    $this->assertSame(['sub' => ['type' => 'varchar', 'length' => $expected]], $schema);
  }

  /**
   * Data provider for ::testSchema().
   *
   * @return array<string, array{array<string, mixed>, int}>
   *   Test cases.
   */
  public static function providerSchemaLength(): array {
    return [
      'no length falls back to the base MAX_LENGTH' => [[], 255],
      'explicit length wins' => [['length' => 50], 50],
      // CHARACTERIZATION, not contract. `max_length` is the LEGACY name for
      // `length`; the fallback exists so installs whose config predates the
      // normalization in custom_field_update_10003() keep working, and it takes
      // precedence over `length` here. Once existing config has been migrated
      // the fallback should go, and these two rows are then expected to fail —
      // that failure is the intended signal, not a regression.
      'legacy max_length is honoured' => [['max_length' => 60], 60],
      'legacy max_length takes precedence over length' => [['max_length' => 60, 'length' => 50], 60],
    ];
  }

  /**
   * The property definition is a single, optional string.
   */
  public function testPropertyDefinitions(): void {
    $properties = StringType::propertyDefinitions(['name' => 'sub']);

    $this->assertSame(['sub'], array_keys($properties));
    $this->assertSame('string', $properties['sub']->getDataType());
    $this->assertFalse($properties['sub']->isRequired());
  }

  /**
   * The declared default field settings.
   */
  public function testDefaultFieldSettings(): void {
    $this->assertSame([
      'prefix' => '',
      'suffix' => '',
      'allowed_values' => [],
      'label' => '',
      'required' => FALSE,
      'translatable' => FALSE,
      'description' => '',
      'description_display' => 'after',
      'check_empty' => FALSE,
    ], StringType::defaultFieldSettings());
  }

  /**
   * A Length constraint is added only when `length` is set.
   *
   * The second row is CHARACTERIZATION, not contract: schema() honours the
   * legacy `max_length` key (see ::providerSchemaLength) but getConstraints()
   * reads only `length`, so a column carrying only `max_length` is sized by it
   * and validated by nothing — the column accepts values longer than its own
   * varchar width until the database truncates or rejects them. Retiring the
   * legacy key closes that gap and will change this expectation.
   *
   * @param array<string, mixed> $column
   *   Column settings to merge over the name.
   * @param bool $expected
   *   Whether a Length constraint is expected.
   *
   * @dataProvider providerConstraints
   */
  #[DataProvider('providerConstraints')]
  public function testConstraints(array $column, bool $expected): void {
    $instance = $this->typeManager->createInstance('string', [
      'settings' => ['name' => 'sub'] + $column,
    ]);
    $this->assertInstanceOf(CustomFieldTypeInterface::class, $instance);

    $this->assertSame($expected, array_key_exists('Length', $instance->getConstraints()));
  }

  /**
   * Data provider for ::testConstraints().
   *
   * @return array<string, array{array<string, mixed>, bool}>
   *   Test cases.
   */
  public static function providerConstraints(): array {
    return [
      'length set' => [['length' => 50], TRUE],
      'no length at all' => [[], FALSE],
      'legacy max_length only is NOT constrained' => [['max_length' => 60], FALSE],
    ];
  }

  /**
   * The check_empty setting drops an item when a sibling column is populated.
   *
   * A populated sibling column is essential here: without one, every column is
   * empty and CustomItem::isEmpty()'s all-columns-empty fallback drops the item
   * regardless of check_empty, so the setting under test would have no
   * observable effect. See ::testItemWithEveryColumnEmptyIsDropped().
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
        'sub' => ['name' => 'sub', 'type' => 'string'],
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
   * @return array<string, array{bool, string, bool}>
   *   Test cases.
   */
  public static function providerCheckEmpty(): array {
    return [
      'check_empty on, blank value' => [TRUE, '', FALSE],
      'check_empty on, populated value' => [TRUE, 'kept', TRUE],
      'check_empty off, blank value' => [FALSE, '', TRUE],
    ];
  }

  /**
   * An item whose every column is empty is dropped, whatever check_empty says.
   *
   * CONTRACT, by design. This is CustomItem::isEmpty()'s second path — every
   * sub-field empty — which is independent of check_empty: an item carrying no
   * populated column at all is nothing worth storing, whether or not any
   * column opted into the emptiness check. It is also why a single-column
   * field cannot be used to test check_empty, since this path fires first and
   * would hide the setting's effect.
   */
  public function testItemWithEveryColumnEmptyIsDropped(): void {
    $field_name = 'field_all_empty';
    $this->createCustomField(
      $field_name,
      [
        'sub' => ['name' => 'sub', 'type' => 'string'],
        'other' => ['name' => 'other', 'type' => 'string'],
      ],
      [
        'sub' => ['label' => 'One', 'check_empty' => FALSE],
        'other' => ['label' => 'Two', 'check_empty' => FALSE],
      ],
    );

    $node = Node::create(['type' => 'page', 'title' => 'all empty']);
    $node->set($field_name, [['sub' => '', 'other' => '']]);
    $node->save();

    $this->assertNull(
      $this->storedItem($node, $field_name),
      'An item with no populated column is dropped even with check_empty off everywhere.',
    );
  }

  /**
   * Sample generation draws from allowed_values when they are configured.
   */
  public function testSampleValueUsesAllowedValues(): void {
    $field_name = $this->createCaseField([
      'settings' => [
        'allowed_values' => [
          ['key' => 'red', 'label' => 'Red'],
          ['key' => 'green', 'label' => 'Green'],
        ],
      ],
    ], 'allowed');

    $field = $this->container->get('entity_field.manager')
      ->getFieldDefinitions('node', 'page')[$field_name];
    $instance = $this->getCustomFieldItems($field)['sub'];

    for ($i = 0; $i < 10; $i++) {
      $this->assertContains(
        StringType::generateSampleValue($instance, 'node'),
        ['red', 'green'],
        'The generated sample is one of the allowed values.',
      );
    }
  }

}
