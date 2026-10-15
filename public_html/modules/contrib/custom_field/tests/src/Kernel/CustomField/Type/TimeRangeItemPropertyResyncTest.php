<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Type;

use Drupal\custom_field\Plugin\CustomField\FieldType\TimeRangeType;
use Drupal\custom_field\Time;
use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that time_range sibling properties survive repeated setValue() calls.
 *
 * CustomFieldTimeRange::setValue() writes the end value onto a SIBLING
 * property of the field item. Core's Map::setValue() re-syncs every ALREADY
 * INSTANTIATED property object from the incoming values array, so a sibling
 * missing from that array is reset to NULL on the second pass.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldType\TimeRangeType
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(TimeRangeType::class)]
class TimeRangeItemPropertyResyncTest extends CustomFieldKernelTestBase {

  /**
   * A start time: 09:00, in seconds past midnight.
   */
  protected const START = 32400;

  /**
   * An end time: 17:00, in seconds past midnight.
   */
  protected const END = 61200;

  /**
   * The number of seconds between ::START and ::END.
   */
  protected const DURATION = 28800;

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
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createCustomField(
      'field_test',
      ['tr' => ['name' => 'tr', 'type' => 'time_range']],
      ['tr' => ['label' => 'Time range', 'check_empty' => FALSE]],
    );
  }

  /**
   * Returns the stored siblings of a saved node, read back from storage.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The saved node.
   *
   * @return array<string, mixed>
   *   The reloaded value/end values, cast to int where present.
   */
  protected function storedValues(Node $node): array {
    $item = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadUnchanged($node->id())
      ->get('field_test')[0] ?? NULL;

    // An item whose every subfield is empty is dropped on save, so there is
    // nothing to read back.
    if ($item === NULL) {
      return ['value' => NULL, 'end' => NULL];
    }

    return [
      'value' => $item->tr === NULL ? NULL : (int) $item->tr,
      'end' => $item->tr__end === NULL ? NULL : (int) $item->tr__end,
    ];
  }

  /**
   * A repeated programmatic setValue() must keep the end time.
   *
   * This is the JSON:API / REST / migrate / custom-code shape: the nested
   * value array with no widget promotion involved. Without the extraction
   * override the second pass resets the sibling to NULL.
   */
  public function testProgrammaticNestedValueSurvivesRepeatedSetValue(): void {
    $values = ['tr' => ['value' => self::START, 'end' => self::END]];

    $node = Node::create(['type' => 'page', 'title' => 'Programmatic']);
    $items = $node->get('field_test');

    $items->setValue([$values]);
    $node->validate();
    $this->assertSame(self::END, (int) $items[0]->tr__end, 'Set on first pass.');

    $items->setValue([$values]);
    $this->assertSame(self::END, (int) $items[0]->tr__end, 'Survives second pass.');

    $node->save();

    $this->assertSame(
      ['value' => self::START, 'end' => self::END],
      $this->storedValues($node)
    );
  }

  /**
   * The derived duration is still correct after a repeated setValue().
   *
   * The duration column is deliberately excluded from the extraction because
   * CustomItem::preSave() computes it from the start and end times. That
   * exclusion is only safe if the computation actually runs on this path, so
   * assert the stored duration rather than assuming it.
   */
  public function testDerivedDurationSurvivesRepeatedSetValue(): void {
    $values = ['tr' => ['value' => self::START, 'end' => self::END]];

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

    $this->assertSame(self::END, (int) $item->tr__end, 'The end value survived.');
    $this->assertSame(self::DURATION, (int) $item->tr__duration, 'The duration was computed from start and end.');
  }

  /**
   * The node path stores exactly the same values as before the override.
   *
   * Run this with the override reverted to prove it changed no stored value.
   *
   * @param array<string, mixed> $value
   *   The nested subfield value.
   * @param array<string, mixed> $expected
   *   The expected reloaded value/end values.
   *
   * @dataProvider providerStoredValues
   */
  #[DataProvider('providerStoredValues')]
  public function testStoredValues(array $value, array $expected): void {
    $node = Node::create(['type' => 'page', 'title' => 'Matrix ' . md5(serialize($value))]);
    $node->get('field_test')->setValue([['tr' => $value]]);
    $node->save();

    $this->assertSame($expected, $this->storedValues($node));
  }

  /**
   * Data provider for testStoredValues().
   *
   * @return array<string, array{array<string, mixed>, array<string, mixed>}>
   *   Test cases.
   *
   * @todo The 'start sentinel keeps the item without an end' case is a
   *   CHARACTERIZATION case - see the comment on it. Revisit if the sentinel
   *   stops reaching storage.
   */
  public static function providerStoredValues(): array {
    $expect = static function (mixed $value, mixed $end): array {
      return ['value' => $value, 'end' => $end];
    };
    $dropped = $expect(NULL, NULL);

    return [
      'both set' => [
        ['value' => self::START, 'end' => self::END],
        $expect(self::START, self::END),
      ],
      // Time::isEmpty() treats 0 - midnight - as a real time, not as empty.
      'start midnight is not empty' => [
        ['value' => 0, 'end' => self::END],
        $expect(0, self::END),
      ],
      'end midnight is not empty' => [
        ['value' => 0, 'end' => 0],
        $expect(0, 0),
      ],
      // Numeric strings are accepted and stored as integers.
      'numeric strings' => [
        ['value' => (string) self::START, 'end' => (string) self::END],
        $expect(self::START, self::END),
      ],
      'end missing' => [
        ['value' => self::START],
        $expect(self::START, NULL),
      ],
      'end NULL' => [
        ['value' => self::START, 'end' => NULL],
        $expect(self::START, NULL),
      ],
      'end empty string' => [
        ['value' => self::START, 'end' => ''],
        $expect(self::START, NULL),
      ],
      // Time::EMPTY_VALUE is the sentinel the widget submits for "no time".
      'end sentinel' => [
        ['value' => self::START, 'end' => Time::EMPTY_VALUE],
        $expect(self::START, NULL),
      ],
      // The start time gates the end: with no start, the end is never
      // written, and an item with no other subfield is dropped on save.
      'start missing drops the item' => [
        ['end' => self::END],
        $dropped,
      ],
      'start NULL drops the item' => [
        ['value' => NULL, 'end' => self::END],
        $dropped,
      ],
      'start empty string drops the item' => [
        ['value' => '', 'end' => self::END],
        $dropped,
      ],
      // CHARACTERIZATION, not contract: unlike the 'time' type, preSave()
      // does not null the sentinel for a time range, so 86401 - the "no time"
      // marker - is written to storage as if it were a real time. The end is
      // still correctly gated off, which is the part this case exists to pin.
      // Storing the sentinel is arguably a bug in its own right; out of scope
      // here, since changing it would change stored values.
      // @todo A future fix may legitimately make this expectation wrong;
      //   update it with the guard rather than treating it as a regression.
      'start sentinel keeps the item without an end' => [
        ['value' => Time::EMPTY_VALUE, 'end' => self::END],
        $expect(Time::EMPTY_VALUE, NULL),
      ],
      'both empty drops the item' => [
        ['value' => NULL, 'end' => NULL],
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
    $class = $this->typeManager->getDefinitions()['time_range']['class'];

    $this->assertSame($expected, $class::extractExtraPropertyValues(['name' => 'tr'], $value));
  }

  /**
   * Data provider for testExtraPropertyMapping().
   *
   * @return array<string, array{mixed, array<string, mixed>}>
   *   Test cases.
   */
  public static function providerMapping(): array {
    return [
      'both set' => [
        ['value' => self::START, 'end' => self::END],
        ['tr__end' => self::END],
      ],
      'start midnight is not empty' => [
        ['value' => 0, 'end' => self::END],
        ['tr__end' => self::END],
      ],
      'end midnight is not empty' => [
        ['value' => 0, 'end' => 0],
        ['tr__end' => 0],
      ],
      // The value is passed through unchanged; the data type casts on store.
      'numeric strings pass through' => [
        ['value' => (string) self::START, 'end' => (string) self::END],
        ['tr__end' => (string) self::END],
      ],
      'end missing' => [['value' => self::START], []],
      'end NULL' => [['value' => self::START, 'end' => NULL], []],
      'end empty string' => [['value' => self::START, 'end' => ''], []],
      'end sentinel' => [['value' => self::START, 'end' => Time::EMPTY_VALUE], []],
      'start missing' => [['end' => self::END], []],
      'start NULL' => [['value' => NULL, 'end' => self::END], []],
      'start empty string' => [['value' => '', 'end' => self::END], []],
      'start sentinel' => [['value' => Time::EMPTY_VALUE, 'end' => self::END], []],
      // The duration column is never extracted, even if a caller sends one.
      'duration is never extracted' => [
        ['value' => self::START, 'end' => self::END, 'duration' => 99],
        ['tr__end' => self::END],
      ],
      'not an array' => [self::START, []],
      'empty array' => [[], []],
    ];
  }

}
