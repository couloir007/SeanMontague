<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Type;

use Drupal\custom_field\Plugin\CustomField\FieldType\DurationType;
use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldTypeKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the 'duration' sub-field type.
 *
 * Modelled loosely on core's field type Kernel tests, closest counterpart
 * core/modules/field/tests/src/Kernel/Timestamp/TimestampItemTest.php.
 *
 * DurationType stores a plain number of seconds in an unsigned int column.
 * It does not override getConstraints() and its single property is a bare
 * DataDefinition::create('integer'), so it carries no validation constraint
 * of its own; validationCases() is therefore empty and testValidation()
 * routes through the base class's assertNoConstraintsOfItsOwn() guard.
 * duration_options is a UI preset list for the widget only, not a validation
 * allowlist: any integer value round-trips regardless of whether it appears
 * in the configured options.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldType\DurationType
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(DurationType::class)]
class DurationTypeTest extends CustomFieldTypeKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static function typeId(): string {
    return 'duration';
  }

  /**
   * {@inheritdoc}
   */
  protected function roundTripCases(): array {
    return [
      'zero seconds' => [
        'value' => ['sub' => 0],
        'expected' => ['sub' => '0'],
      ],
      'one day in seconds' => [
        'value' => ['sub' => 86400],
        'expected' => ['sub' => '86400'],
      ],
      'one week in seconds' => [
        'value' => ['sub' => 604800],
        'expected' => ['sub' => '604800'],
      ],
      'one month in seconds' => [
        'value' => ['sub' => 2592000],
        'expected' => ['sub' => '2592000'],
      ],
      // CONTRACT, not characterization: duration_options is a UI preset
      // list, not a validation allowlist, so a value absent from it still
      // round-trips untouched.
      'a value outside the configured duration_options presets still round-trips' => [
        'value' => ['sub' => 42],
        'expected' => ['sub' => '42'],
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
   * The schema is a single unsigned int column with a fixed description.
   */
  public function testSchema(): void {
    $this->assertSame(
      ['sub' => ['type' => 'int', 'unsigned' => TRUE, 'description' => 'The number of seconds.']],
      DurationType::schema(['name' => 'sub']),
    );
  }

  /**
   * The property definition is a single, optional integer.
   */
  public function testPropertyDefinitions(): void {
    $properties = DurationType::propertyDefinitions(['name' => 'sub']);

    $this->assertSame(['sub'], array_keys($properties));
    $this->assertSame('integer', $properties['sub']->getDataType());
    $this->assertFalse($properties['sub']->isRequired());
  }

  /**
   * The declared default field settings, including the duration_options preset.
   */
  public function testDefaultFieldSettings(): void {
    $settings = DurationType::defaultFieldSettings();

    // Labels are TranslatableMarkup objects; cast to string to avoid a
    // brittle object-identity comparison.
    $settings['duration_options'] = array_map(
      static fn (array $option): array => ['key' => $option['key'], 'label' => (string) $option['label']],
      $settings['duration_options'],
    );

    $this->assertSame([
      'duration_options' => [
        ['key' => 86400, 'label' => '1 day'],
        ['key' => 604800, 'label' => '1 week'],
        ['key' => 2592000, 'label' => '1 month'],
      ],
      'label' => '',
      'required' => FALSE,
      'translatable' => FALSE,
      'description' => '',
      'description_display' => 'after',
      'check_empty' => FALSE,
    ], $settings);
  }

  /**
   * Sample generation draws from the configured duration_options.
   */
  public function testSampleValueUsesConfiguredDurationOptions(): void {
    $field_name = $this->createCaseField([
      'settings' => [
        'duration_options' => [
          ['key' => 120, 'label' => 'Two minutes'],
          ['key' => 3600, 'label' => 'One hour'],
        ],
      ],
    ], 'options');

    $field = $this->container->get('entity_field.manager')
      ->getFieldDefinitions('node', 'page')[$field_name];
    $instance = $this->getCustomFieldItems($field)['sub'];

    for ($i = 0; $i < 10; $i++) {
      $this->assertContains(
        DurationType::generateSampleValue($instance, 'node'),
        [120, 3600],
        'The generated sample is one of the configured duration_options.',
      );
    }
  }

  /**
   * CHARACTERIZATION, not contract: a negative duration raises no violation.
   *
   * Duration declares no validation constraint at all, so a negative value
   * validates cleanly even though the schema column is unsigned. This pins
   * the current, surprising behavior without endorsing it as correct; it
   * deliberately stops at validate() and never saves, since unsigned-column
   * handling for an out-of-range negative value differs across database
   * drivers and a save-level assertion here would be fragile in CI.
   */
  public function testNegativeDurationProducesNoViolation(): void {
    $field_name = $this->createCaseField([], 'negative');

    $node = Node::create(['type' => 'page', 'title' => 'negative duration']);
    $node->set($field_name, [['sub' => -1]]);

    $this->assertNoViolations($node);
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
        'sub' => ['name' => 'sub', 'type' => 'duration'],
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
      'check_empty on, populated value' => [TRUE, 86400, TRUE],
      'check_empty off, blank value' => [FALSE, NULL, TRUE],
      // CONTRACT: 0 is neither '' nor NULL, and CustomItem::isEmpty() only
      // ever checks for those two literal states, so a zero duration must
      // not be treated as empty even with check_empty on.
      'check_empty on, zero duration (0)' => [TRUE, 0, TRUE],
    ];
  }

}
