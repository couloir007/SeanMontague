<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Type;

use Drupal\custom_field\Plugin\CustomField\FieldType\TimeRangeType;
use Drupal\custom_field\Time;
use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldTypeKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the 'time_range' sub-field type.
 *
 * Modelled loosely on core's field type Kernel tests, closest counterpart
 * core/modules/field/tests/src/Kernel/DateRange/DateRangeItemTest.php.
 *
 * TimeRangeType extends TimeType and adds two sibling properties: a plain
 * '{name}__end' integer and a computed, never-caller-settable
 * '{name}__duration' integer. It carries no validation constraint of its own
 * -- PHP attributes on a subclass do not merge with the parent's, so
 * TimeRangeType::getConstraints() is always empty even though TimeType
 * declares a CustomFieldTime constraint -- so validationCases() is empty and
 * testValidation() routes through the base class's
 * assertNoConstraintsOfItsOwn() guard.
 *
 * There are two independent, deliberately different mechanisms for
 * populating the end column, both triggered only when the incoming value for
 * the main property is itself a NESTED array (['value' => ..., 'end' =>
 * ...]):
 * - CustomFieldTimeRange::setValue() writes 'end' directly onto the sibling
 *   property when the property object itself is set.
 * - TimeRangeType::extractExtraPropertyValues() pre-fills the same sibling
 *   into CustomItem::setValue()'s incoming values array, so a REPEATED
 *   setValue() call (the JSON:API / REST / migrate shape) doesn't wipe an
 *   already-set sibling that Core's Map::setValue() would otherwise reset to
 *   NULL, because Map::setValue() only re-syncs properties that are already
 *   instantiated. Both gate on Time::isEmpty() for start AND end.
 *
 * roundTripCases() below uses the FLAT property-keyed shape ('sub',
 * 'sub__end', 'sub__duration' as independent top-level keys), matching every
 * other converted type's convention. This is NOT equivalent to the nested
 * shape for one specific scenario: a flat 'sub__end' key is written directly
 * onto the sibling property with no Time::isEmpty() gating at all (that
 * gating lives only inside the two mechanisms above, both of which require
 * the NESTED shape to trigger). CustomItem::preSave()'s 'time_range' case
 * then only nulls the end column when it can compare it against a valid
 * start ($end_time truthy); when Time::createFromTimestamp() treats the
 * supplied end as empty (e.g. the 86401 sentinel), that branch is skipped
 * and whatever was already on the column -- including a raw sentinel --
 * survives to storage. See the 'end sentinel' case below, its
 * 'end sentinel via the nested value shape' counterpart (same input,
 * opposite outcome, because only the nested shape is gated), and
 * ::testCheckEmpty()'s sentinel row, all pinned accordingly.
 *
 * The repeated-setValue() regression, and a unit-level pin of
 * extractExtraPropertyValues() in isolation, are exactly the coverage the
 * nested shape exists to protect and do not fit the generic
 * roundTripCases()/validationCases() contract (that runner calls setValue()
 * exactly once); they get their own dedicated methods instead.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldType\TimeRangeType
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(TimeRangeType::class)]
class TimeRangeTypeTest extends CustomFieldTypeKernelTestBase {

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
  protected static function typeId(): string {
    return 'time_range';
  }

  /**
   * {@inheritdoc}
   */
  protected function roundTripCases(): array {
    return [
      // Time(13, 40, 30)->getTimestamp() to Time(14, 40, 30)->getTimestamp().
      'afternoon range' => [
        'value' => ['sub' => 49230, 'sub__end' => 52830],
        'expected' => ['sub' => '49230', 'sub__end' => '52830', 'sub__duration' => '3600'],
      ],
      // Time(6, 40, 30)->getTimestamp() to Time(8, 40, 30)->getTimestamp().
      'morning range' => [
        'value' => ['sub' => 24030, 'sub__end' => 31230],
        'expected' => ['sub' => '24030', 'sub__end' => '31230', 'sub__duration' => '7200'],
      ],
      // CONTRACT: Time::isEmpty() treats 0 (midnight) as a real time, not as
      // empty, on the start column -- same reason as the 'time' type.
      'start midnight is not empty' => [
        'value' => ['sub' => 0, 'sub__end' => 3600],
        'expected' => ['sub' => '0', 'sub__end' => '3600', 'sub__duration' => '3600'],
      ],
      // Numeric strings are accepted and stored as integers.
      'numeric strings' => [
        'value' => ['sub' => '49230', 'sub__end' => '52830'],
        'expected' => ['sub' => '49230', 'sub__end' => '52830', 'sub__duration' => '3600'],
      ],
      // The start time gates the end: with no end supplied at all, neither
      // the end nor the derived duration are ever written.
      'end missing' => [
        'value' => ['sub' => 49230],
        'expected' => ['sub' => '49230', 'sub__end' => NULL, 'sub__duration' => NULL],
      ],
      // CHARACTERIZATION: unlike the nested shape (see
      // ::testSentinelReachesStorageOnStartColumn(), where the sentinel on
      // the end column is gated off before it is ever written), a flat
      // 'sub__end' key bypasses that gate entirely -- it is written directly
      // onto the plain integer sibling with no Time::isEmpty() check at all.
      // CustomItem::preSave()'s 'time_range' case only nulls the end column
      // inside its "$end_time is truthy" branch (to compare it against
      // start); since Time::createFromTimestamp(86401) returns NULL, that
      // branch is skipped, and the raw sentinel already on the column
      // survives to storage untouched -- the same root cause as the start
      // column's sentinel, reached through a different write path.
      //
      // @see https://www.drupal.org/project/custom_field/issues/3623453
      'end sentinel' => [
        'value' => ['sub' => 49230, 'sub__end' => Time::EMPTY_VALUE],
        'expected' => ['sub' => '49230', 'sub__end' => (string) Time::EMPTY_VALUE, 'sub__duration' => NULL],
      ],
      // The same end sentinel, but through the NESTED shape (the original
      // widget/JSON:API/migrate write path this class was built around,
      // see the class docblock). Here Time::isEmpty() DOES gate the end
      // before it is ever written, so unlike the flat case immediately
      // above, the end is correctly never persisted at all.
      'end sentinel via the nested value shape' => [
        'value' => ['sub' => ['value' => 49230, 'end' => Time::EMPTY_VALUE]],
        'expected' => ['sub' => '49230', 'sub__end' => NULL, 'sub__duration' => NULL],
      ],
      // CONTRACT: __duration is computed by CustomItem::preSave() from the
      // start and end times -- it is never caller-settable. A bogus value
      // supplied alongside a valid start/end pair is always overwritten by
      // the real computed one.
      'a directly supplied duration is ignored and recomputed' => [
        'value' => ['sub' => 49230, 'sub__end' => 52830, 'sub__duration' => 99999],
        'expected' => ['sub' => '49230', 'sub__end' => '52830', 'sub__duration' => '3600'],
      ],
      // CHARACTERIZATION: CustomItem::preSave() uses a strict `>` to decide
      // whether to null the end, so an EQUAL start/end pair is silently
      // accepted with duration left NULL (the difference is 0, which fails
      // the "> 0" check) -- even though the widget's own validation rejects
      // an equal pair with `>=`. Overnight ranges are intentionally
      // unsupported (see the next case); this pins today's actual,
      // asymmetric behavior, not an endorsement of it.
      'equal start and end (zero-length range)' => [
        'value' => ['sub' => 0, 'sub__end' => 0],
        'expected' => ['sub' => '0', 'sub__end' => '0', 'sub__duration' => NULL],
      ],
      // A genuine, meaningful round trip one second short of the upper edge
      // of the valid 0..86400 range -- see the DEDICATED 86400 case below for
      // why 86400 itself is NOT a safe "near the boundary" example.
      'near end-of-day (23:59:59), one second short of the boundary quirk below' => [
        'value' => ['sub' => 72000, 'sub__end' => 86399],
        'expected' => ['sub' => '72000', 'sub__end' => '86399', 'sub__duration' => '14399'],
      ],
      // CHARACTERIZATION, discovered by running this test, not by reading:
      // 86400 -- the documented maximum of the valid range (see TimeType's
      // own 'end of day, the maximum of the valid range' case) -- silently
      // behaves as timestamp 0 inside CustomItem::preSave()'s comparison,
      // because Time::createFromTimestamp(86400) reconstructs a calendar
      // moment exactly 86400 seconds after a base midnight, which IS the
      // *next* midnight; Time::getTimestamp() then reads that object's own
      // hour/minute/second (0, 0, 0) back out, not the original 86400. So a
      // valid end-of-day value one second past ::86399 above is
      // indistinguishable, for this comparison, from an end of literal
      // midnight -- start_timestamp (72000) is judged greater than end (0),
      // and preSave() silently drops it as an invalid ordering, exactly as
      // it would for any other start-after-end pair.
      'end exactly at 86400 is wrongly computed as midnight and silently dropped' => [
        'value' => ['sub' => 72000, 'sub__end' => 86400],
        'expected' => ['sub' => '72000', 'sub__end' => NULL, 'sub__duration' => NULL],
      ],
      // CHARACTERIZATION: a genuinely overnight pair (22:00 to 02:00) takes
      // the same start-after-end branch as any other invalid ordering --
      // preSave() silently drops the end, indistinguishable from "no end was
      // ever given," with no violation raised. Overnight ranges are
      // intentionally not supported: the gap this pins is that nothing
      // enforces the same end-after-start rule the widget enforces on any
      // write path that bypasses the widget (JSON:API, migrate, programmatic
      // saves).
      'start after end (including overnight use) silently drops the end' => [
        'value' => ['sub' => 79200, 'sub__end' => 7200],
        'expected' => ['sub' => '79200', 'sub__end' => NULL, 'sub__duration' => NULL],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function validationCases(): array {
    // No constraint exists on time_range at all -- see the class docblock.
    // This routes through assertNoConstraintsOfItsOwn(), which is expected to
    // pass.
    return [];
  }

  /**
   * The schema is three unsigned int columns: start, end and duration.
   */
  public function testSchema(): void {
    $schema = TimeRangeType::schema(['name' => 'sub']);

    // The 'end' column's description is built with the procedural t(), not
    // $this->t(), producing a TranslatableMarkup object; cast to string for a
    // stable comparison instead of asserting object identity.
    $schema['sub__end']['description'] = (string) $schema['sub__end']['description'];

    $this->assertSame([
      'sub' => ['type' => 'int', 'unsigned' => TRUE, 'description' => 'The start time'],
      'sub__end' => ['description' => 'The end time', 'type' => 'int', 'unsigned' => TRUE],
      'sub__duration' => [
        'type' => 'int',
        'unsigned' => TRUE,
        'description' => 'The difference between start and end times in seconds.',
      ],
    ], $schema);
  }

  /**
   * The property definitions: the custom start type, plus two plain integers.
   */
  public function testPropertyDefinitions(): void {
    $properties = TimeRangeType::propertyDefinitions(['name' => 'sub']);

    $this->assertSame(['sub', 'sub__end', 'sub__duration'], array_keys($properties));

    $this->assertSame('custom_field_time_range', $properties['sub']->getDataType());
    $this->assertFalse($properties['sub']->isRequired());
    $this->assertTrue($properties['sub']->getSetting('unsigned'));

    $this->assertSame('integer', $properties['sub__end']->getDataType());
    $this->assertTrue($properties['sub__end']->getSetting('unsigned'));
    $this->assertTrue($properties['sub__end']->isInternal());

    $this->assertSame('integer', $properties['sub__duration']->getDataType());
    $this->assertTrue($properties['sub__duration']->isInternal());
    $this->assertFalse($properties['sub__duration']->isRequired());
  }

  /**
   * The declared default field settings, inherited from TimeType unchanged.
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
    ], TimeRangeType::defaultFieldSettings());
  }

  /**
   * A repeated programmatic setValue() must keep the end time.
   *
   * This is the JSON:API / REST / migrate / custom-code shape: the nested
   * value array with no widget promotion involved. Without
   * TimeRangeType::extractExtraPropertyValues(), the second setValue() pass
   * resets the sibling 'sub__end' property to NULL, because Core's
   * Map::setValue() only re-syncs properties that are already instantiated,
   * and a sibling missing from the second array is one of them.
   */
  public function testProgrammaticNestedValueSurvivesRepeatedSetValue(): void {
    $this->createCustomField(
      'field_test',
      ['sub' => ['name' => 'sub', 'type' => 'time_range']],
      ['sub' => ['label' => 'Time range', 'check_empty' => FALSE]],
    );

    $values = ['sub' => ['value' => self::START, 'end' => self::END]];

    $node = Node::create(['type' => 'page', 'title' => 'Programmatic']);
    $items = $node->get('field_test');

    $items->setValue([$values]);
    $node->validate();
    $this->assertSame(self::END, (int) $items[0]->sub__end, 'Set on first pass.');

    $items->setValue([$values]);
    $this->assertSame(self::END, (int) $items[0]->sub__end, 'Survives second pass.');

    $node->save();

    $item = $this->storedItem($node, 'field_test');
    $this->assertNotNull($item);
    $this->assertSame(self::START, (int) $item->sub, 'The start value round-tripped.');
    $this->assertSame(self::END, (int) $item->sub__end, 'The end value round-tripped.');
  }

  /**
   * The derived duration is still correct after a repeated setValue().
   *
   * The duration column is deliberately excluded from the extraction because
   * CustomItem::preSave() computes it from the start and end times. That
   * exclusion is only safe if the computation actually runs on this path, so
   * assert the stored duration rather than assuming it. Kept as its own
   * method, separate from
   * ::testProgrammaticNestedValueSurvivesRepeatedSetValue(), so a break in
   * duration computation is not masked by (or wrongly attributed to) the end
   * value's own survival assertion failing first.
   */
  public function testDerivedDurationSurvivesRepeatedSetValue(): void {
    $this->createCustomField(
      'field_test',
      ['sub' => ['name' => 'sub', 'type' => 'time_range']],
      ['sub' => ['label' => 'Time range', 'check_empty' => FALSE]],
    );

    $values = ['sub' => ['value' => self::START, 'end' => self::END]];

    $node = Node::create(['type' => 'page', 'title' => 'Duration']);
    $items = $node->get('field_test');

    $items->setValue([$values]);
    $node->validate();
    $items->setValue([$values]);
    $node->save();

    $item = $this->storedItem($node, 'field_test');
    $this->assertNotNull($item);
    $this->assertSame(self::END, (int) $item->sub__end, 'The end value survived.');
    $this->assertSame(self::DURATION, (int) $item->sub__duration, 'The duration was computed from start and end.');
  }

  /**
   * The type plugin maps a nested value onto its extra property names.
   *
   * A unit-level pin of TimeRangeType::extractExtraPropertyValues() in
   * isolation -- no entity, no save -- so a break here points squarely at
   * the extraction mechanism itself, distinct from a break anywhere else in
   * the save pipeline.
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
    $this->assertSame($expected, TimeRangeType::extractExtraPropertyValues(['name' => 'sub'], $value));
  }

  /**
   * Data provider for ::testExtraPropertyMapping().
   *
   * @return array<string, array{mixed, array<string, mixed>}>
   *   Test cases.
   */
  public static function providerMapping(): array {
    return [
      'both set' => [
        ['value' => self::START, 'end' => self::END],
        ['sub__end' => self::END],
      ],
      'start midnight is not empty' => [
        ['value' => 0, 'end' => self::END],
        ['sub__end' => self::END],
      ],
      'end midnight is not empty' => [
        ['value' => 0, 'end' => 0],
        ['sub__end' => 0],
      ],
      // The value is passed through unchanged; the data type casts on store.
      'numeric strings pass through' => [
        ['value' => (string) self::START, 'end' => (string) self::END],
        ['sub__end' => (string) self::END],
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
        ['sub__end' => self::END],
      ],
      'not an array' => [self::START, []],
      'empty array' => [[], []],
    ];
  }

  /**
   * With the start column absent, the whole item is dropped on save.
   *
   * The start time gates the end (see ::testExtraPropertyMapping()'s 'start
   * missing' / 'start NULL' / 'start empty string' cases): with no usable
   * start, the end is never written either, so an item whose start is
   * missing, NULL or an empty string ends up with every column empty and is
   * dropped by the storage layer's own empty-item filtering.
   *
   * @param array<string, mixed> $value
   *   The nested subfield value to attempt to store.
   * @param string $suffix
   *   A short, unique field-name suffix for this case (field storage names
   *   are capped at 32 characters, too short for a hash of the case value).
   *
   * @dataProvider providerItemDroppedWhenStartIsAbsent
   */
  #[DataProvider('providerItemDroppedWhenStartIsAbsent')]
  public function testItemDroppedWhenStartIsAbsent(array $value, string $suffix): void {
    $field_name = $this->createCaseField([], $suffix);

    $node = Node::create(['type' => 'page', 'title' => 'Dropped']);
    $node->set($field_name, [['sub' => $value]]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNull($item, 'The item was dropped: with the start column absent, nothing else in it survives.');
  }

  /**
   * Data provider for ::testItemDroppedWhenStartIsAbsent().
   *
   * @return array<string, array{array<string, mixed>, string}>
   *   Test cases.
   */
  public static function providerItemDroppedWhenStartIsAbsent(): array {
    return [
      'start missing' => [['end' => self::END], 'start_missing'],
      'start NULL' => [['value' => NULL, 'end' => self::END], 'start_null'],
      'start empty string' => [['value' => '', 'end' => self::END], 'start_empty'],
      'both empty' => [['value' => NULL, 'end' => NULL], 'both_empty'],
    ];
  }

  /**
   * CHARACTERIZATION: the 86401 sentinel reaches storage unguarded.
   *
   * Unlike the 'time' type, whose own CustomItem::preSave() case explicitly
   * nulls a '86401' start value, time_range's case only nulls the end and
   * duration columns when the start itself is empty -- it never touches the
   * start column. The sentinel is still caught by Time::isEmpty() at the
   * nested-value setValue()/extraction gates, so the end this case supplies
   * is correctly never written; it is only the start column's own sentinel
   * that survives unguarded.
   *
   * @see https://www.drupal.org/project/custom_field/issues/3623453
   */
  public function testSentinelReachesStorageOnStartColumn(): void {
    $field_name = $this->createCaseField([], 'sentinel_start');

    $node = Node::create(['type' => 'page', 'title' => 'sentinel start']);
    $node->set($field_name, [['sub' => ['value' => Time::EMPTY_VALUE, 'end' => self::END]]]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item, 'The item survives: the sentinel start column is never NULL.');
    $this->assertSame(
      (string) Time::EMPTY_VALUE,
      $item->get('sub')->getValue(),
      'The sentinel reaches storage unguarded.',
    );
    $this->assertNull(
      $item->get('sub__end')->getValue(),
      'The end is correctly gated off: the start sentinel prevents it from ever being written.',
    );
    $this->assertNull($item->get('sub__duration')->getValue());
  }

  /**
   * CHARACTERIZATION: an out-of-range integer raises no violation at all.
   *
   * TimeRangeType::getConstraints() always returns an empty array (see the
   * class docblock), so unlike the 'time' type's own CustomFieldTime
   * constraint, nothing here rejects a value past the 0..86400 valid range,
   * on either the start or the end column. Stops at validate() and never
   * saves: Time::createFromTimestamp()'s own range assertion would throw
   * once CustomItem::preSave() tried to construct a Time object from this
   * value, and that failure mode is a save-path concern of its own, not part
   * of this characterization.
   *
   * @see https://www.drupal.org/project/custom_field/issues/3623453
   */
  public function testOutOfRangeValueProducesNoViolation(): void {
    $field_name = $this->createCaseField([], 'out_of_range');

    // The max time in range is 86400; 90400 and 90500 are both past it.
    $node = Node::create(['type' => 'page', 'title' => 'out of range']);
    $node->set($field_name, [['sub' => 90400, 'sub__end' => 90500]]);

    $this->assertNoViolations($node);
  }

  /**
   * The check_empty setting drops an item when a sibling column is populated.
   *
   * @param bool $check_empty
   *   The check_empty setting on the column under test.
   * @param int|null $value
   *   The value to store in the start column.
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
        'sub' => ['name' => 'sub', 'type' => 'time_range'],
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
      // check_empty must not treat it as empty -- same reason as the 'time'
      // type.
      'check_empty on, midnight (0)' => [TRUE, 0, TRUE],
      // CHARACTERIZATION, and a DIFFERENT outcome than the 'time' type's own
      // equivalent case: CustomItem::preSave()'s 'time_range' case never
      // nulls its own start column for the sentinel (only the end/duration
      // columns -- see ::testSentinelReachesStorageOnStartColumn()), so
      // 86401 is never counted as an empty value at either empty-item
      // filtering pass, and the item survives with the raw sentinel stored
      // on the start column.
      //
      // @see https://www.drupal.org/project/custom_field/issues/3623453
      'check_empty on, 86401 sentinel' => [TRUE, 86401, TRUE],
    ];
  }

}
