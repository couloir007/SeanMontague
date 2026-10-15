<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Type;

use Drupal\custom_field\Plugin\CustomField\FieldType\DateTimeType;
use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that datetime sibling properties survive repeated setValue() calls.
 *
 * CustomFieldDatetime::setValue() writes the timezone value onto a SIBLING
 * property of the field item. Core's Map::setValue() re-syncs every ALREADY
 * INSTANTIATED property object from the incoming values array, so a sibling
 * missing from that array is reset to NULL on the second pass.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldType\DateTimeType
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(DateTimeType::class)]
class DateTimeItemPropertyResyncTest extends CustomFieldKernelTestBase {

  /**
   * A storage-format datetime value.
   */
  protected const DATE = '2024-01-15T10:30:00';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'node',
    'text',
    'custom_field',
    'image',
    'datetime',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createCustomField(
      'field_test',
      [
        'dt' => [
          'name' => 'dt',
          'type' => 'datetime',
          'datetime_type' => 'datetime',
        ],
      ],
      ['dt' => ['label' => 'Date', 'check_empty' => FALSE]],
    );
  }

  /**
   * Returns the stored siblings of a saved node, read back from storage.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The saved node.
   *
   * @return array<string, mixed>
   *   The reloaded value/timezone values.
   */
  protected function storedValues(Node $node): array {
    $item = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadUnchanged($node->id())
      ->get('field_test')[0] ?? NULL;

    // An item whose every subfield is empty is dropped on save, so there is
    // nothing to read back.
    if ($item === NULL) {
      return ['value' => NULL, 'timezone' => NULL];
    }

    return ['value' => $item->dt, 'timezone' => $item->dt__timezone];
  }

  /**
   * A repeated programmatic setValue() must keep the timezone.
   *
   * This is the JSON:API / REST / migrate / custom-code shape: the nested
   * value array with no widget promotion involved. Without the extraction
   * override the second pass resets the sibling to NULL.
   */
  public function testProgrammaticNestedValueSurvivesRepeatedSetValue(): void {
    $values = [
      'dt' => [
        'value' => self::DATE,
        'timezone' => 'America/New_York',
      ],
    ];

    $node = Node::create(['type' => 'page', 'title' => 'Programmatic']);
    $items = $node->get('field_test');

    $items->setValue([$values]);
    $node->validate();
    $this->assertSame('America/New_York', $items[0]->dt__timezone, 'Set on first pass.');

    $items->setValue([$values]);
    $this->assertSame('America/New_York', $items[0]->dt__timezone, 'Survives second pass.');

    $node->save();

    $stored = $this->storedValues($node);
    $this->assertSame(self::DATE, $stored['value']);
    $this->assertSame('America/New_York', $stored['timezone']);
  }

  /**
   * The node path stores exactly the same values as before the override.
   *
   * Run this with the override reverted to prove it changed no stored value.
   *
   * @param array<string, mixed> $value
   *   The nested subfield value.
   * @param array<string, mixed> $expected
   *   The expected reloaded value/timezone values.
   *
   * @dataProvider providerStoredValues
   */
  #[DataProvider('providerStoredValues')]
  public function testStoredValues(array $value, array $expected): void {
    $node = Node::create(['type' => 'page', 'title' => 'Matrix ' . md5(serialize($value))]);
    $node->get('field_test')->setValue([['dt' => $value]]);
    $node->save();

    $this->assertSame($expected, $this->storedValues($node));
  }

  /**
   * Data provider for testStoredValues().
   *
   * @return array<string, array{array<string, mixed>, array<string, mixed>}>
   *   Test cases.
   */
  public static function providerStoredValues(): array {
    $expect = static function (mixed $value, mixed $timezone): array {
      return ['value' => $value, 'timezone' => $timezone];
    };

    return [
      'timezone missing' => [
        ['value' => self::DATE],
        $expect(self::DATE, NULL),
      ],
      'timezone NULL' => [
        ['value' => self::DATE, 'timezone' => NULL],
        $expect(self::DATE, NULL),
      ],
      'timezone empty string' => [
        ['value' => self::DATE, 'timezone' => ''],
        $expect(self::DATE, ''),
      ],
      'timezone valid' => [
        ['value' => self::DATE, 'timezone' => 'America/New_York'],
        $expect(self::DATE, 'America/New_York'),
      ],
      'timezone UTC' => [
        ['value' => self::DATE, 'timezone' => 'UTC'],
        $expect(self::DATE, 'UTC'),
      ],
      // CustomItem::preSave() clears the timezone when there is no date, and
      // an item with no other subfield is then dropped entirely. These lock
      // that clearing behavior rather than the extraction guard.
      'value empty string drops the item' => [
        ['value' => '', 'timezone' => 'America/New_York'],
        $expect(NULL, NULL),
      ],
      'value NULL drops the item' => [
        ['value' => NULL, 'timezone' => 'America/New_York'],
        $expect(NULL, NULL),
      ],
      // The main value must be a string; a non-string is discarded by
      // CustomFieldDatetime::setValue() and takes the item with it.
      'value non-string drops the item' => [
        ['value' => 12345, 'timezone' => 'America/New_York'],
        $expect(NULL, NULL),
      ],
    ];
  }

  /**
   * The type plugin maps a nested value onto its extra property names.
   *
   * Mirrors the input cases of ::providerStoredValues() at the level of the
   * mapping itself, so a change in either is visible in both.
   *
   * @param mixed $value
   *   The nested subfield value.
   * @param array<string, mixed> $expected
   *   The expected extra property values.
   *
   * @dataProvider providerMapping
   */
  #[DataProvider('providerMapping')]
  public function testExtraPropertyMapping(mixed $value, array $expected): void {
    $class = $this->typeManager->getDefinitions()['datetime']['class'];

    $this->assertSame($expected, $class::extractExtraPropertyValues(['name' => 'dt'], $value));
  }

  /**
   * Data provider for testExtraPropertyMapping().
   *
   * @return array<string, array{mixed, array<string, mixed>}>
   *   Test cases.
   */
  public static function providerMapping(): array {
    return [
      'timezone missing' => [['value' => self::DATE], []],
      'timezone NULL' => [['value' => self::DATE, 'timezone' => NULL], []],
      'timezone empty string' => [
        ['value' => self::DATE, 'timezone' => ''],
        ['dt__timezone' => ''],
      ],
      'timezone valid' => [
        ['value' => self::DATE, 'timezone' => 'America/New_York'],
        ['dt__timezone' => 'America/New_York'],
      ],
      // The guard is isset() on the timezone alone, so it does not care what
      // the main value looks like. preSave() is what clears an orphaned one.
      'value empty string still extracts' => [
        ['value' => '', 'timezone' => 'UTC'],
        ['dt__timezone' => 'UTC'],
      ],
      'value missing still extracts' => [
        ['timezone' => 'UTC'],
        ['dt__timezone' => 'UTC'],
      ],
      'not an array' => [self::DATE, []],
      'empty array' => [[], []],
    ];
  }

}
