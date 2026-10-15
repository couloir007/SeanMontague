<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Type;

use Drupal\custom_field\Plugin\CustomField\FieldType\DateRangeType;
use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that daterange sibling properties survive repeated setValue() calls.
 *
 * CustomFieldDateRange::setValue() writes the end and timezone values onto
 * SIBLING properties of the field item. Core's Map::setValue() re-syncs every
 * ALREADY INSTANTIATED property object from the incoming values array, so any
 * sibling missing from that array is reset to NULL on the second pass.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldType\DateRangeType
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(DateRangeType::class)]
class DateRangeItemPropertyResyncTest extends CustomFieldKernelTestBase {

  /**
   * A storage-format start value.
   */
  protected const START = '2024-01-15T10:00:00';

  /**
   * A storage-format end value, two hours after ::START.
   */
  protected const END = '2024-01-15T12:00:00';

  /**
   * The number of seconds between ::START and ::END.
   */
  protected const DURATION = 7200;

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
        'dr' => [
          'name' => 'dr',
          'type' => 'daterange',
          'datetime_type' => 'datetime',
        ],
      ],
      ['dr' => ['label' => 'Date range', 'check_empty' => FALSE]],
    );
  }

  /**
   * Returns the stored siblings of a saved node, read back from storage.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The saved node.
   *
   * @return array<string, mixed>
   *   The reloaded value/end/timezone values.
   */
  protected function storedValues(Node $node): array {
    $item = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadUnchanged($node->id())
      ->get('field_test')[0] ?? NULL;

    // An item whose every subfield is empty is dropped on save, so there is
    // nothing to read back.
    if ($item === NULL) {
      return ['value' => NULL, 'end' => NULL, 'timezone' => NULL];
    }

    return [
      'value' => $item->dr,
      'end' => $item->dr__end,
      'timezone' => $item->dr__timezone,
    ];
  }

  /**
   * A repeated programmatic setValue() must keep the end and timezone.
   *
   * This is the JSON:API / REST / migrate / custom-code shape: the nested
   * value array with no widget promotion involved. Without the extraction
   * override the second pass resets both siblings to NULL.
   */
  public function testProgrammaticNestedValueSurvivesRepeatedSetValue(): void {
    $values = [
      'dr' => [
        'value' => self::START,
        'end' => self::END,
        'timezone' => 'America/New_York',
      ],
    ];

    $node = Node::create(['type' => 'page', 'title' => 'Programmatic']);
    $items = $node->get('field_test');

    $items->setValue([$values]);
    $node->validate();
    $this->assertSame(self::END, $items[0]->dr__end, 'End set on first pass.');
    $this->assertSame('America/New_York', $items[0]->dr__timezone, 'Timezone set on first pass.');

    $items->setValue([$values]);
    $this->assertSame(self::END, $items[0]->dr__end, 'End survives second pass.');
    $this->assertSame('America/New_York', $items[0]->dr__timezone, 'Timezone survives second pass.');

    $node->save();

    $stored = $this->storedValues($node);
    $this->assertSame(self::START, $stored['value']);
    $this->assertSame(self::END, $stored['end']);
    $this->assertSame('America/New_York', $stored['timezone']);
  }

  /**
   * The derived duration is still correct after a repeated setValue().
   *
   * The duration column is deliberately excluded from the extraction because
   * CustomItem::preSave() computes it from the start and end dates. That
   * exclusion is only safe if the computation actually runs on this path, so
   * assert the stored duration rather than assuming it.
   */
  public function testDerivedDurationSurvivesRepeatedSetValue(): void {
    $values = [
      'dr' => [
        'value' => self::START,
        'end' => self::END,
        'timezone' => 'UTC',
      ],
    ];

    $node = Node::create(['type' => 'page', 'title' => 'Duration']);
    $items = $node->get('field_test');

    $items->setValue([$values]);
    $node->validate();
    $items->setValue([$values]);
    $node->save();

    $item = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadUnchanged($node->id())
      ->get('field_test')[0];

    $this->assertSame(self::END, $item->dr__end, 'The end value survived.');
    $this->assertSame(self::DURATION, (int) $item->dr__duration, 'The duration was computed from start and end.');
  }

  /**
   * The node path stores exactly the same values as before the override.
   *
   * Run this with the override reverted to prove it changed no stored value.
   *
   * @param array<string, mixed> $value
   *   The nested subfield value.
   * @param array<string, mixed> $expected
   *   The expected reloaded value/end/timezone values.
   *
   * @dataProvider providerStoredValues
   */
  #[DataProvider('providerStoredValues')]
  public function testStoredValues(array $value, array $expected): void {
    $node = Node::create(['type' => 'page', 'title' => 'Matrix ' . md5(serialize($value))]);
    $node->get('field_test')->setValue([['dr' => $value]]);
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
    $expect = static function (mixed $value, mixed $end, mixed $timezone): array {
      return ['value' => $value, 'end' => $end, 'timezone' => $timezone];
    };
    $dropped = $expect(NULL, NULL, NULL);

    return [
      'end and timezone present' => [
        ['value' => self::START, 'end' => self::END, 'timezone' => 'UTC'],
        $expect(self::START, self::END, 'UTC'),
      ],
      'end missing' => [
        ['value' => self::START, 'timezone' => 'UTC'],
        $expect(self::START, NULL, 'UTC'),
      ],
      'end NULL' => [
        ['value' => self::START, 'end' => NULL, 'timezone' => 'UTC'],
        $expect(self::START, NULL, 'UTC'),
      ],
      'end empty string' => [
        ['value' => self::START, 'end' => '', 'timezone' => 'UTC'],
        $expect(self::START, NULL, 'UTC'),
      ],
      // The end value must be a string, exactly like the start value.
      'end non-string' => [
        ['value' => self::START, 'end' => 12345, 'timezone' => 'UTC'],
        $expect(self::START, NULL, 'UTC'),
      ],
      'timezone missing' => [
        ['value' => self::START, 'end' => self::END],
        $expect(self::START, self::END, NULL),
      ],
      'timezone NULL' => [
        ['value' => self::START, 'end' => self::END, 'timezone' => NULL],
        $expect(self::START, self::END, NULL),
      ],
      // isset() lets NULL through as "not set", but '' is set and is cast.
      'timezone empty string' => [
        ['value' => self::START, 'end' => self::END, 'timezone' => ''],
        $expect(self::START, self::END, ''),
      ],
      // The start value gates BOTH siblings: with no start, neither is
      // written, and an item with no other subfield is dropped on save.
      'start missing drops the item' => [
        ['end' => self::END, 'timezone' => 'UTC'],
        $dropped,
      ],
      'start empty string drops the item' => [
        ['value' => '', 'end' => self::END, 'timezone' => 'UTC'],
        $dropped,
      ],
      'start NULL drops the item' => [
        ['value' => NULL, 'end' => self::END, 'timezone' => 'UTC'],
        $dropped,
      ],
      'start non-string drops the item' => [
        ['value' => 12345, 'end' => self::END, 'timezone' => 'UTC'],
        $dropped,
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
    $class = $this->typeManager->getDefinitions()['daterange']['class'];

    $this->assertSame($expected, $class::extractExtraPropertyValues(['name' => 'dr'], $value));
  }

  /**
   * Data provider for testExtraPropertyMapping().
   *
   * @return array<string, array{mixed, array<string, mixed>}>
   *   Test cases.
   *
   * @todo The 'timezone non-string is cast' case is a CHARACTERIZATION case -
   *   see the comment on it. Revisit if the cast is ever tightened.
   */
  public static function providerMapping(): array {
    return [
      'end and timezone present' => [
        ['value' => self::START, 'end' => self::END, 'timezone' => 'UTC'],
        ['dr__end' => self::END, 'dr__timezone' => 'UTC'],
      ],
      'end missing' => [
        ['value' => self::START, 'timezone' => 'UTC'],
        ['dr__timezone' => 'UTC'],
      ],
      'end NULL' => [
        ['value' => self::START, 'end' => NULL, 'timezone' => 'UTC'],
        ['dr__timezone' => 'UTC'],
      ],
      'end empty string' => [
        ['value' => self::START, 'end' => '', 'timezone' => 'UTC'],
        ['dr__timezone' => 'UTC'],
      ],
      'end non-string' => [
        ['value' => self::START, 'end' => 12345, 'timezone' => 'UTC'],
        ['dr__timezone' => 'UTC'],
      ],
      'timezone missing' => [
        ['value' => self::START, 'end' => self::END],
        ['dr__end' => self::END],
      ],
      'timezone NULL' => [
        ['value' => self::START, 'end' => self::END, 'timezone' => NULL],
        ['dr__end' => self::END],
      ],
      'timezone empty string' => [
        ['value' => self::START, 'end' => self::END, 'timezone' => ''],
        ['dr__end' => self::END, 'dr__timezone' => ''],
      ],
      // CHARACTERIZATION, not contract: this pins the (string) cast that
      // CustomFieldDateRange::setValue() already applies, so the override
      // provably matches it. Storing '0' as a timezone is not a meaningful
      // value and arguably the cast should reject a non-string instead. Out
      // of scope here - changing it would change stored values.
      // @todo A future fix may legitimately make this expectation wrong;
      //   update it with the guard rather than treating it as a regression.
      'timezone non-string is cast' => [
        ['value' => self::START, 'end' => self::END, 'timezone' => 0],
        ['dr__end' => self::END, 'dr__timezone' => '0'],
      ],
      // The start value gates BOTH siblings.
      'start missing extracts nothing' => [
        ['end' => self::END, 'timezone' => 'UTC'],
        [],
      ],
      'start empty string extracts nothing' => [
        ['value' => '', 'end' => self::END, 'timezone' => 'UTC'],
        [],
      ],
      'start NULL extracts nothing' => [
        ['value' => NULL, 'end' => self::END, 'timezone' => 'UTC'],
        [],
      ],
      'start non-string extracts nothing' => [
        ['value' => 12345, 'end' => self::END, 'timezone' => 'UTC'],
        [],
      ],
      // The duration column is never extracted, even if a caller sends one.
      'duration is never extracted' => [
        ['value' => self::START, 'end' => self::END, 'duration' => 99],
        ['dr__end' => self::END],
      ],
      'not an array' => [self::START, []],
      'empty array' => [[], []],
    ];
  }

}
