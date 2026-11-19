<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Type;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\Type\DateTimeInterface;
use Drupal\custom_field\Plugin\CustomField\FieldType\DateRangeType;
use Drupal\custom_field\Plugin\CustomField\FieldType\DateTimeType;
use Drupal\custom_field\Plugin\CustomField\FieldType\DateTimeTypeInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\Plugin\DataType\CustomFieldDateRange;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldTypeKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the 'daterange' custom field sub-field type.
 *
 * The core counterpart is
 * \Drupal\Tests\datetime_range\Kernel\DateRangeItemTest, which has a single
 * method (the date-only computed start and end at noon); that shape is
 * reproduced in ::testDateOnlyRangeParsesAtNoon(). The structure of this
 * class follows DateTimeTypeTest, because DateRangeType extends DateTimeType,
 * but what a range adds is covered as its own behavior rather than as
 * renamed datetime cases:
 *
 * 1. A start/end RELATIONSHIP that nothing outside the widget enforces.
 *    CustomItem::preSave() derives the '__duration' column from it, and that
 *    derivation is the only place the relationship is acted on at all.
 * 2. An OPTIONAL end. Core's DateRangeItem makes its end value required and
 *    decides emptiness from both columns; this type decides emptiness from the
 *    start alone, so an end without a start never survives a save.
 * 3. Two COMPUTED dates, '__start_date' and '__end_date', read from two columns
 *    whose data types differ: the start is the module's
 *    'custom_field_daterange', which implements no typed-data date interface,
 *    while the end is core's 'datetime_iso8601', which does. Typed data's
 *    automatic PrimitiveType check therefore applies to the end and never to
 *    the start -- see ::testStartAndEndCarryDifferentDataTypeContracts().
 * 4. ONE stored timezone shared by both dates. It is a display preference:
 *    both computed dates are always in the storage timezone.
 *
 * Neither DateRangeType's attribute nor DateTimeType's declares a
 * 'constraints' key. A child class's PHP attribute does not inherit its
 * parent's 'constraints' key -- which is how time_range lost time's check --
 * but here there is nothing to drop. Core's DateRangeItem, by contrast,
 * extends a class that declares "DateTimeFormat" and does not re-declare it,
 * so core's range field loses the format check the same way. Every
 * "core-consistent" claim in this class rests on that comparison having been
 * run, not read.
 *
 * Out of scope by decision, as for datetime: how CustomItem::onChange()
 * dispatches to the type, and what it does with $notify, are left to tests of
 * that dispatch itself. The start-side outcome is pinned (whether
 * '__start_date' follows a changed start); whether '__end_date' follows a
 * changed end is the exact-match dispatch question itself and is deliberately
 * NOT asserted here. Also not covered, because it is covered elsewhere: the
 * widget surface (including the duration settings and the start/end form
 * validation), the formatters, the extra-property API invariants
 * (ExtraPropertyApiTest, which already pins this type's derived names) and
 * the default widget/formatter wiring (TypeRegistryTest).
 *
 * @see https://www.drupal.org/project/custom_field/issues/3623453
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldType\DateRangeType
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(DateRangeType::class)]
class DateRangeTypeTest extends CustomFieldTypeKernelTestBase {

  /**
   * A storage-format start value.
   */
  private const START = '2024-01-15T10:00:00';

  /**
   * A storage-format end value, two hours after ::START.
   */
  private const END = '2024-01-15T12:00:00';

  /**
   * The stored duration between ::START and ::END, as read back.
   *
   * The column is an unsigned int, but typed data hands the stored value back
   * as the string the database returned.
   */
  private const DURATION = '7200';

  /**
   * A storage-format date-only start value.
   */
  private const DATE_ONLY_START = '2016-09-21';

  /**
   * A storage-format date-only end value, the day after the start.
   */
  private const DATE_ONLY_END = '2016-09-22';

  /**
   * {@inheritdoc}
   *
   * 'datetime' supplies the date formats the computed properties parse with.
   * 'file' is needed only by ::testEndSetDateTimeWritesAnUnreadableValue(),
   * which re-saves a node: CustomItemList::postSave() reaches the file usage
   * bookkeeping on every update, whatever the field's columns are.
   */
  protected static $modules = [
    'datetime',
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  protected static function typeId(): string {
    return 'daterange';
  }

  /**
   * {@inheritdoc}
   *
   * 'datetime_type' is a required column key for this type, exactly as for
   * datetime: ::testColumnWithoutDatetimeTypeSavesThenThrowsOnWrite() pins
   * what happens without it.
   */
  protected function defaultColumn(string $name): array {
    return parent::defaultColumn($name) + [
      'datetime_type' => DateTimeType::DATETIME_TYPE_DATETIME,
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Stated explicitly rather than relying on the backfill in
   * CustomFieldTypeBase::getFieldSettings(). The five widget-only settings
   * are included because they are this type's declared defaults; nothing in
   * this class varies them, because nothing outside the widgets reads them.
   */
  protected function defaultFieldSettings(): array {
    return [
      'duration_enabled' => FALSE,
      'duration_options' => [],
      'timezone_enabled' => FALSE,
      'timezone_options' => [],
      'seconds_enabled' => TRUE,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   *
   * Every row asserts the derived '__duration' too, because that column is
   * written by CustomItem::preSave() on every save and is part of what a
   * round trip produces. It reads back as a string; an absent end yields '0',
   * not NULL.
   *
   * The first eight rows are the retired class's stored-value matrix. The
   * missing/NULL pairs are kept distinct although they expect the same
   * result, because the INPUTS differ and each must be accepted.
   */
  protected function roundTripCases(): array {
    return [
      'an end and a timezone round-trip' => [
        'value' => ['sub' => ['value' => self::START, 'end' => self::END, 'timezone' => 'UTC']],
        'expected' => self::stored(self::START, self::END, 'UTC', self::DURATION),
      ],
      'a missing end leaves an open-ended range' => [
        'value' => ['sub' => ['value' => self::START, 'timezone' => 'UTC']],
        'expected' => self::stored(self::START, NULL, 'UTC', '0'),
      ],
      'an explicitly NULL end is stored as NULL' => [
        'value' => ['sub' => ['value' => self::START, 'end' => NULL, 'timezone' => 'UTC']],
        'expected' => self::stored(self::START, NULL, 'UTC', '0'),
      ],
      'an empty-string end is stored as NULL' => [
        'value' => ['sub' => ['value' => self::START, 'end' => '', 'timezone' => 'UTC']],
        'expected' => self::stored(self::START, NULL, 'UTC', '0'),
      ],
      // The end value must be a string, exactly like the start value.
      'a non-string end is discarded' => [
        'value' => ['sub' => ['value' => self::START, 'end' => 12345, 'timezone' => 'UTC']],
        'expected' => self::stored(self::START, NULL, 'UTC', '0'),
      ],
      'a timezone that was never supplied stays NULL' => [
        'value' => ['sub' => ['value' => self::START, 'end' => self::END]],
        'expected' => self::stored(self::START, self::END, NULL, self::DURATION),
      ],
      'an explicitly NULL timezone stays NULL' => [
        'value' => ['sub' => ['value' => self::START, 'end' => self::END, 'timezone' => NULL]],
        'expected' => self::stored(self::START, self::END, NULL, self::DURATION),
      ],
      // isset() lets NULL through as "not set", but '' is set and is kept.
      'an empty-string timezone is stored as an empty string' => [
        'value' => ['sub' => ['value' => self::START, 'end' => self::END, 'timezone' => '']],
        'expected' => self::stored(self::START, self::END, '', self::DURATION),
      ],
      // The retired TestDaterange plugin's first case, with a fixed timezone
      // in place of a random one.
      'a year-long range round-trips' => [
        'value' => [
          'sub' => [
            'value' => '2014-01-01T20:00:00',
            'end' => '2015-01-01T20:00:00',
            'timezone' => 'Europe/Madrid',
          ],
        ],
        'expected' => self::stored('2014-01-01T20:00:00', '2015-01-01T20:00:00', 'Europe/Madrid', '31536000'),
      ],
      // The retired plugin's second case assigned its start key twice, so the
      // DST start was overwritten and what it actually tested was this: a
      // start and a timezone, no end.
      'a start with a timezone and no end round-trips' => [
        'value' => ['sub' => ['value' => '2017-01-01T20:00:00', 'timezone' => 'America/New_York']],
        'expected' => self::stored('2017-01-01T20:00:00', NULL, 'America/New_York', '0'),
      ],
      // And this is what that case evidently meant to test: a range starting
      // on a DST transition date.
      'a range starting on a DST transition date round-trips' => [
        'value' => [
          'sub' => [
            'value' => '2016-11-04T00:21:00',
            'end' => '2017-01-01T20:00:00',
            'timezone' => 'America/New_York',
          ],
        ],
        'expected' => self::stored('2016-11-04T00:21:00', '2017-01-01T20:00:00', 'America/New_York', '5081940'),
      ],
      'a scalar assignment sets the start only' => [
        'value' => ['sub' => self::START],
        'expected' => self::stored(self::START, NULL, NULL, '0'),
      ],
      // The flat shape writes the end column directly, with no nested
      // start-value gate in the way; preSave() still derives the duration.
      'a flat end assignment round-trips' => [
        'value' => ['sub' => self::START, 'sub__end' => self::END],
        'expected' => self::stored(self::START, self::END, NULL, self::DURATION),
      ],
      'a date-only column stores bare dates' => [
        'column' => ['datetime_type' => DateTimeType::DATETIME_TYPE_DATE],
        'value' => ['sub' => ['value' => self::DATE_ONLY_START, 'end' => self::DATE_ONLY_END]],
        'expected' => self::stored(self::DATE_ONLY_START, self::DATE_ONLY_END, NULL, '86400'),
      ],
      // 'allday' is the third datetime_type, offered for daterange only. It
      // stores the full datetime format: the start/end-of-day expansion is
      // the widget's job, not the type's.
      'an all-day column stores full date and time values' => [
        'column' => ['datetime_type' => DateRangeType::DATETIME_TYPE_ALLDAY],
        'value' => ['sub' => ['value' => '2016-09-21T00:00:00', 'end' => '2016-09-21T23:59:59']],
        'expected' => self::stored('2016-09-21T00:00:00', '2016-09-21T23:59:59', NULL, '86399'),
      ],
    ];
  }

  /**
   * Builds a round-trip expectation for the four stored columns.
   *
   * @param string|null $start
   *   The expected start.
   * @param string|null $end
   *   The expected end.
   * @param string|null $timezone
   *   The expected timezone.
   * @param string|null $duration
   *   The expected duration, as the string the database returns.
   *
   * @return array<string, string|null>
   *   The expectation, keyed by property.
   */
  private static function stored(?string $start, ?string $end, ?string $timezone, ?string $duration): array {
    return [
      'sub' => $start,
      'sub__end' => $end,
      'sub__timezone' => $timezone,
      'sub__duration' => $duration,
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Two live mechanisms, both on PROPERTIES rather than on the plugin: the
   * timezone's AllowedValues list (inherited in shape from datetime, declared
   * again in DateRangeType::propertyDefinitions()), and the PrimitiveType check
   * typed data attaches automatically to the end because its data type
   * implements DateTimeInterface. The same unparsable value on the START
   * produces no violation at all -- see ::testMalformedValuesAreStored().
   */
  protected function validationCases(): array {
    return [
      'an unknown timezone identifier is rejected' => [
        'value' => ['sub' => ['value' => self::START, 'end' => self::END, 'timezone' => 'invalid']],
        'property' => 'sub__timezone',
        'message' => 'The value you selected is not a valid choice.',
        'count' => 1,
      ],
      // No Length constraint exists for the varchar(32) timezone column; the
      // AllowedValues list rejects this incidentally, as for datetime.
      'a timezone wider than its column is rejected as a bad choice' => [
        'value' => ['sub' => ['value' => self::START, 'end' => self::END, 'timezone' => str_repeat('z', 33)]],
        'property' => 'sub__timezone',
        'message' => 'The value you selected is not a valid choice.',
        'count' => 1,
      ],
      'an unparsable end is rejected as the wrong primitive type' => [
        'value' => ['sub' => ['value' => self::START, 'end' => 'Not a date at all']],
        'property' => 'sub__end',
        'message' => 'This value should be of the correct primitive type.',
        'count' => 1,
      ],
    ];
  }

  /**
   * The schema: start, end, timezone and the derived duration.
   *
   * Core's DateRangeItem::schema() indexes both its value and end_value
   * columns and has neither a timezone nor a duration column. This one
   * declares no index at all, although the module ships Views sort, filter
   * and argument handlers that order by these columns.
   */
  public function testSchema(): void {
    $schema = DateRangeType::schema(['name' => 'sub']);

    // The timezone description is built with a translation object.
    $schema['sub__timezone']['description'] = (string) $schema['sub__timezone']['description'];

    $this->assertSame([
      'sub' => [
        'type' => 'varchar',
        'length' => 20,
        'description' => 'The start date value',
      ],
      'sub__timezone' => [
        'description' => 'The preferred timezone.',
        'type' => 'varchar',
        'length' => 32,
      ],
      'sub__end' => [
        'type' => 'varchar',
        'length' => 20,
        'description' => 'The end date value.',
      ],
      'sub__duration' => [
        'type' => 'int',
        'unsigned' => TRUE,
        'description' => 'The difference between start and end times in seconds.',
      ],
    ], $schema);
  }

  /**
   * The six property definitions, in declaration order.
   *
   * Core's DateRangeItem marks both its value and its end value required;
   * neither is required here.
   */
  public function testPropertyDefinitions(): void {
    $properties = DateRangeType::propertyDefinitions([
      'name' => 'sub',
      'datetime_type' => DateTimeType::DATETIME_TYPE_DATETIME,
    ]);

    $this->assertSame(
      ['sub', 'sub__start_date', 'sub__end', 'sub__end_date', 'sub__timezone', 'sub__duration'],
      array_keys($properties),
    );

    $this->assertSame('custom_field_daterange', $properties['sub']->getDataType());
    $this->assertFalse($properties['sub']->isRequired());
    $this->assertSame(DateTimeType::DATETIME_TYPE_DATETIME, $properties['sub']->getSetting('datetime_type'));

    $this->assertSame('datetime_iso8601', $properties['sub__end']->getDataType());
    $this->assertFalse($properties['sub__end']->isRequired());
    $this->assertTrue($properties['sub__end']->isInternal());

    foreach (['sub__start_date' => 'sub', 'sub__end_date' => 'sub__end'] as $computed => $source) {
      $this->assertSame('any', $properties[$computed]->getDataType());
      $this->assertTrue($properties[$computed]->isComputed());
      $this->assertSame(
        'Drupal\custom_field\Plugin\CustomField\DateTimeComputed',
        ltrim((string) $properties[$computed]->getClass(), '\\'),
      );
      $this->assertSame($source, $properties[$computed]->getSetting('date source'));
      $this->assertSame(DateTimeType::DATETIME_TYPE_DATETIME, $properties[$computed]->getSetting('datetime_type'));
    }

    $this->assertSame('string', $properties['sub__timezone']->getDataType());
    $this->assertTrue($properties['sub__timezone']->isInternal());
    $this->assertSame(32, $properties['sub__timezone']->getSetting('max_length'));

    $this->assertSame('integer', $properties['sub__duration']->getDataType());
    $this->assertTrue($properties['sub__duration']->isInternal());
    $this->assertFalse($properties['sub__duration']->isRequired());
  }

  /**
   * The declared default field settings: range's two, datetime's three.
   */
  public function testDefaultFieldSettings(): void {
    $this->assertSame([
      'duration_enabled' => FALSE,
      'duration_options' => [],
      'timezone_enabled' => FALSE,
      'timezone_options' => [],
      'seconds_enabled' => TRUE,
      'label' => '',
      'required' => FALSE,
      'translatable' => FALSE,
      'description' => '',
      'description_display' => 'after',
      'check_empty' => FALSE,
    ], DateRangeType::defaultFieldSettings());
  }

  /**
   * CONTRACT: no constraint on the plugin, one declared on a property.
   *
   * Asserted on both the configured instance and the plugin definition built
   * from the attribute, because PHP attributes are not inherited: there is
   * nothing on DateTimeType's attribute for this class to lose, and
   * nothing on its own. The only constraint declared anywhere is the
   * timezone's AllowedValues list, re-declared in this class's own
   * propertyDefinitions() override rather than inherited from datetime's.
   */
  public function testConstraintsLiveOnThePropertyNotThePlugin(): void {
    $this->assertSame([], $this->typeInstance()->getConstraints());
    $definition = $this->typeManager->getDefinitions()['daterange'];
    $this->assertSame([], $definition['constraints']);

    $properties = DateRangeType::propertyDefinitions([
      'name' => 'sub',
      'datetime_type' => DateTimeType::DATETIME_TYPE_DATETIME,
    ]);
    $constraints = $properties['sub__timezone']->getConstraints();
    $this->assertArrayHasKey('AllowedValues', $constraints);
    $this->assertContains('UTC', $constraints['AllowedValues']);
    $this->assertContains('', $constraints['AllowedValues']);
  }

  /**
   * CONTRACT: the start and end columns have different data type contracts.
   *
   * The start is 'custom_field_daterange', which implements none of the
   * typed-data interfaces PrimitiveTypeConstraintValidator branches on; the
   * end is core's 'datetime_iso8601', which implements DateTimeInterface. That
   * one difference is why an unparsable end is rejected and an unparsable
   * start is not, and why the end has getDateTime()/setDateTime() and the
   * start has neither. Datetime's own main property is the third variant: a
   * module data type that DOES implement DateTimeInterface.
   */
  public function testStartAndEndCarryDifferentDataTypeContracts(): void {
    $field_name = $this->createCaseField([], 'contracts');
    $node = Node::create(['type' => 'page', 'title' => 'contracts']);
    $node->set($field_name, [['sub' => ['value' => self::START, 'end' => self::END]]]);
    $item = $this->itemAt($node->get($field_name));

    $this->assertNotInstanceOf(DateTimeInterface::class, $item->get('sub'));
    $this->assertInstanceOf(CustomFieldDateRange::class, $item->get('sub'));
    $this->assertInstanceOf(DateTimeInterface::class, $item->get('sub__end'));
  }

  /**
   * CONTRACT: both dates are read back in the storage timezone.
   *
   * The one stored timezone belongs to the range as a whole and is a display
   * preference only: neither computed date is shifted by it. Ported from
   * CustomFieldItemTest's daterange block, which asserted both.
   */
  public function testStoredRangeIsReadBackInStorageTimezone(): void {
    $field_name = $this->createCaseField([], 'tz');
    $node = Node::create(['type' => 'page', 'title' => 'storage timezone']);
    $node->set($field_name, [['sub' => ['value' => self::START, 'end' => self::END, 'timezone' => 'America/New_York']]]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);

    foreach ([self::START => $this->computedStart($item), self::END => $this->computedEnd($item)] as $stored => $computed) {
      $this->assertSame(DateTimeTypeInterface::STORAGE_TIMEZONE, $computed->getTimezone()->getName());
      $this->assertSame($stored, $computed->format(DateTimeTypeInterface::DATETIME_STORAGE_FORMAT));
    }
    $this->assertSame('America/New_York', $item->get('sub__timezone')->getValue());
  }

  /**
   * CONTRACT: a date-only range stores bare dates and computes noon.
   *
   * Two sources, both reproduced. The daterange half of the removed
   * CustomFieldItemTest::testDateOnly() -- the start and the flat end written
   * through the magic setter, saved with validation, read back as bare dates.
   * And core's DateRangeItemTest::testDateOnly(): both computed dates are noon
   * UTC.
   */
  public function testDateOnlyRangeParsesAtNoon(): void {
    $field_name = $this->createCaseField(
      ['column' => ['datetime_type' => DateTimeType::DATETIME_TYPE_DATE]],
      'date_only',
    );
    $node = Node::create(['type' => 'page', 'title' => 'date only']);
    $item = $node->get($field_name)->appendItem();
    $this->setRawValue($item, 'sub', '2014-01-01');
    $this->setRawValue($item, 'sub__end', '2015-02-02');
    $this->entityValidateAndSave($node);

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertSame('2014-01-01', $item->get('sub')->getValue());
    $this->assertSame('2015-02-02', $item->get('sub__end')->getValue());

    foreach (['2014-01-01' => $this->computedStart($item), '2015-02-02' => $this->computedEnd($item)] as $date => $computed) {
      $this->assertSame($date . ' 12:00:00', $computed->format('Y-m-d H:i:s'));
      $this->assertSame(DateTimeTypeInterface::STORAGE_TIMEZONE, $computed->getTimezone()->getName());
    }
    $this->assertSame('34300800', $item->get('sub__duration')->getValue(), 'Noon to noon.');
  }

  /**
   * The derived duration follows the start/end relationship.
   *
   * CustomItem::preSave() writes the positive difference in seconds, and 0
   * for anything else -- no end, an unparsable end, an end equal to the
   * start or before it. A caller-supplied duration is overwritten.
   *
   * CHARACTERIZATION for two of the rows: an end before the start, and an end
   * equal to it, are accepted by programmatic saves with no violation, and
   * stored as given. The only start/end check is
   * DateRangeWidgetBase::validateStartEnd(), a form callback, and it is
   * STRICTER than core's: it rejects equal dates (start >= end), where core's
   * widget allows them. Core itself also accepts both shapes outside the form
   * (confirmed by running), so the API-level behavior is core-consistent; the
   * plugin's own description nonetheless says the system validates the
   * order.
   *
   * The stored zero is pinned at both layers, because they disagree:
   * the column holds '0', while CustomFieldDateRange::getDuration() maps every
   * falsy value to NULL.
   *
   * @param string|null $end
   *   The end value, or NULL for none.
   * @param int|null $caller_duration
   *   A flat duration supplied by the caller, or NULL for none.
   * @param string $expected
   *   The stored duration, as read back.
   * @param int|null $expected_accessor
   *   What getDuration() reports.
   * @param string $suffix
   *   A short, unique field-name suffix for this case.
   *
   * @dataProvider providerDuration
   */
  #[DataProvider('providerDuration')]
  public function testDurationFollowsTheStartEndRelationship(?string $end, ?int $caller_duration, string $expected, ?int $expected_accessor, string $suffix): void {
    $field_name = $this->createCaseField([], $suffix);
    $value = ['sub' => ['value' => self::START, 'end' => $end]];
    if ($caller_duration !== NULL) {
      $value['sub__duration'] = $caller_duration;
    }
    $node = Node::create(['type' => 'page', 'title' => 'duration ' . $suffix]);
    $node->set($field_name, [$value]);
    $this->assertNoViolations($node, $suffix);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertSame(self::START, $item->get('sub')->getValue());
    $this->assertSame($end, $item->get('sub__end')->getValue());
    $this->assertSame($expected, $item->get('sub__duration')->getValue());
    $this->assertSame($expected_accessor, $this->startProperty($item)->getDuration());
  }

  /**
   * Data provider for ::testDurationFollowsTheStartEndRelationship().
   *
   * @return array<string, array{string|null, int|null, string, int|null, string}>
   *   Test cases.
   */
  public static function providerDuration(): array {
    return [
      'an end after the start' => [self::END, NULL, self::DURATION, 7200, 'd_after'],
      'an end equal to the start' => [self::START, NULL, '0', NULL, 'd_equal'],
      'an end before the start' => ['2024-01-15T09:00:00', NULL, '0', NULL, 'd_before'],
      'no end' => [NULL, NULL, '0', NULL, 'd_none'],
      // Parseable, so it validates; not in the storage format, so the computed
      // end is NULL and the range has no measurable length.
      'an end not in the storage format' => ['11/03/2016', NULL, '0', NULL, 'd_bad_end'],
      'a caller-supplied duration is overwritten' => [self::END, 99, self::DURATION, 7200, 'd_caller'],
    ];
  }

  /**
   * CHARACTERIZATION: an unparsable start keeps a caller-supplied duration.
   *
   * CustomItem::preSave() writes the duration only when '__start_date' is a
   * date object. When the start cannot be parsed, the branch is skipped and
   * whatever the caller put in '__duration' is stored -- here a duration for a
   * range whose start is not a date at all. The start itself is accepted
   * because its data type has no PrimitiveType check; see
   * ::testMalformedValuesAreStored().
   */
  public function testUnparsableStartKeepsCallerSuppliedDuration(): void {
    $field_name = $this->createCaseField([], 'caller_dur');
    $node = Node::create(['type' => 'page', 'title' => 'caller duration']);
    $node->set($field_name, [['sub' => 'Not a date at all', 'sub__end' => self::END, 'sub__duration' => 99]]);
    $this->assertNoViolations($node);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertNull($this->rawValue($item, 'sub__start_date'));
    $this->assertSame('99', $item->get('sub__duration')->getValue());
  }

  /**
   * The accessor maps an empty-string timezone to NULL.
   *
   * The column keeps '' (an allowed value), while
   * CustomFieldDateRange::getTimezone() answers NULL for anything falsy.
   */
  public function testGetTimezoneMapsEmptyStringToNull(): void {
    $field_name = $this->createCaseField([], 'tz_accessor');
    $node = Node::create(['type' => 'page', 'title' => 'timezone accessor']);
    $node->set($field_name, [['sub' => ['value' => self::START, 'end' => self::END, 'timezone' => '']]]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertSame('', $item->get('sub__timezone')->getValue());
    $this->assertNull($this->startProperty($item)->getTimezone());
  }

  /**
   * CHARACTERIZATION: malformed values are stored, and read back as NULL.
   *
   * Core's range field has no format constraint either, so the shape of this
   * is core-consistent: core and this module both accept, store verbatim and
   * then compute NULL for every row below except one (confirmed by running
   * the same inputs against a core daterange field). The exception is the
   * unparsable START: core rejects it with the PrimitiveType check its
   * 'datetime_iso8601' value carries, while this module's start data type has
   * no such check and accepts it -- the end of this module's range rejects the
   * same value (see validationCases()).
   *
   * The duration follows the column that failed: a malformed start skips the
   * derivation and leaves it NULL, a malformed end yields '0'.
   *
   * @param string $column
   *   Which column carries the malformed value: 'start' or 'end'.
   * @param string $value
   *   The malformed value.
   * @param string|null $expected_duration
   *   The stored duration, as read back.
   * @param string $suffix
   *   A short, unique field-name suffix for this case.
   *
   * @dataProvider providerMalformedValues
   */
  #[DataProvider('providerMalformedValues')]
  public function testMalformedValuesAreStored(string $column, string $value, ?string $expected_duration, string $suffix): void {
    $field_name = $this->createCaseField([], $suffix);
    $range = $column === 'start'
      ? ['value' => $value, 'end' => self::END]
      : ['value' => self::START, 'end' => $value];
    $node = Node::create(['type' => 'page', 'title' => 'malformed ' . $suffix]);
    $node->set($field_name, [['sub' => $range]]);
    $this->assertNoViolations($node, 'malformed ' . $column . ' "' . $value . '"');
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertSame($range['value'], $item->get('sub')->getValue());
    $this->assertSame($range['end'], $item->get('sub__end')->getValue());
    $this->assertSame($expected_duration, $item->get('sub__duration')->getValue());

    if ($column === 'start') {
      $this->assertNull($this->rawValue($item, 'sub__start_date'));
      $this->assertSame(self::END, $this->computedEnd($item)->format(DateTimeTypeInterface::DATETIME_STORAGE_FORMAT));
    }
    else {
      $this->assertNull($this->rawValue($item, 'sub__end_date'));
      $this->assertSame(self::START, $this->computedStart($item)->format(DateTimeTypeInterface::DATETIME_STORAGE_FORMAT));
    }
  }

  /**
   * Data provider for ::testMalformedValuesAreStored().
   *
   * @return array<string, array{string, string, string|null, string}>
   *   Test cases.
   */
  public static function providerMalformedValues(): array {
    return [
      // The one row core rejects.
      'an unparsable start' => ['start', 'Not a date at all', NULL, 'm_s_garbage'],
      'a space instead of the T separator, on the start' => ['start', '2016-11-03 20:52:00', NULL, 'm_s_space'],
      'a human-readable start' => ['start', '11/03/2016', NULL, 'm_s_human'],
      'a trailing Z on the start' => ['start', '2024-01-15T10:30:00Z', NULL, 'm_s_zulu'],
      'a space instead of the T separator, on the end' => ['end', '2016-11-03 20:52:00', '0', 'm_e_space'],
      'a human-readable end' => ['end', '11/03/2016', '0', 'm_e_human'],
      'a trailing Z on the end' => ['end', '2024-01-15T10:30:00Z', '0', 'm_e_zulu'],
    ];
  }

  /**
   * CHARACTERIZATION: an over-length value passes validation, then fails.
   *
   * Same shape as datetime's, uri's and link's: no Length
   * constraint exists for either varchar(20) column, so a 25-character value
   * validates cleanly and only the database stops it. Core's range field
   * behaves identically on both columns (confirmed by running). The
   * database's behavior is a MEASURED LOCAL fact -- MariaDB in strict mode
   * raises SQLSTATE[22001] -- not a portable expectation.
   *
   * @param string $column
   *   Which column carries the value: 'start' or 'end'.
   *
   * @dataProvider providerColumns
   */
  #[DataProvider('providerColumns')]
  public function testOverLengthValueIsRejectedByTheDatabaseNotByValidation(string $column): void {
    $field_name = $this->createCaseField([], 'over_' . $column);
    $value = '2024-01-15T10:30:00+01:00';
    $this->assertSame(25, strlen($value));
    $range = $column === 'start'
      ? ['value' => $value, 'end' => self::END]
      : ['value' => self::START, 'end' => $value];

    $node = Node::create(['type' => 'page', 'title' => 'over length']);
    $node->set($field_name, [['sub' => $range]]);
    $this->assertNoViolations($node, 'no Length constraint exists for daterange');

    $this->expectException(EntityStorageException::class);
    $node->save();
  }

  /**
   * Data provider naming the two stored date columns.
   *
   * @return array<string, array{string}>
   *   Test cases.
   */
  public static function providerColumns(): array {
    return [
      'start' => ['start'],
      'end' => ['end'],
    ];
  }

  /**
   * CHARACTERIZATION: the end's setDateTime() writes an unreadable value.
   *
   * The start has no setDateTime() at all (its data type implements no date
   * interface), so the same defect in CustomFieldDatetime::setDateTime() does
   * not reach it. The end does have one -- core's
   * DateTimeIso8601::setDateTime() -- and it writes $dateTime->format('c')
   * exactly as CustomFieldDatetime's does: 25 characters,
   * which the computed end cannot parse and the varchar(20) column cannot
   * hold. So fixing CustomFieldDatetime would not fix this half; the
   * behavior is core's, met here because the column is 20 wide.
   */
  public function testEndSetDateTimeWritesAnUnreadableValue(): void {
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('file');

    $field_name = $this->createCaseField([], 'end_set_date_time');
    $node = Node::create(['type' => 'page', 'title' => 'setDateTime']);
    $node->set($field_name, [['sub' => ['value' => self::START, 'end' => self::END]]]);
    $node->save();

    $reloaded = $this->reloadedNode($node);
    $item = $this->itemAt($reloaded->get($field_name));
    $this->endProperty($item)->setDateTime(
      new DrupalDateTime('2035-11-06 04:00:00', DateTimeTypeInterface::STORAGE_TIMEZONE),
    );

    $written = $item->get('sub__end')->getValue();
    $this->assertSame('2035-11-06T04:00:00+00:00', $written);
    $this->assertSame(25, strlen($written));
    $this->assertNull($this->rawValue($item, 'sub__end_date'));

    $thrown = NULL;
    try {
      $reloaded->save();
    }
    catch (EntityStorageException $e) {
      $thrown = $e;
    }
    $this->assertInstanceOf(EntityStorageException::class, $thrown);

    $stored = $this->storedItem($node, $field_name);
    $this->assertNotNull($stored);
    $this->assertSame(self::END, $stored->get('sub__end')->getValue(), 'The previous end survived the failed save.');
  }

  /**
   * CHARACTERIZATION: getTimestamp() reads the start only, in PHP's zone.
   *
   * Inherited unchanged from DateTimeType, so the skew DateTimeTypeTest pins
   * applies to the start of every range: the stored UTC value is resolved
   * in the request's default timezone. It never looks at the end; there is
   * no end counterpart. Asserted as a relationship, so it holds on a UTC
   * server too.
   */
  public function testGetTimestampReadsTheStartInTheDefaultTimezone(): void {
    $field_name = $this->createCaseField([], 'timestamp');
    $node = Node::create(['type' => 'page', 'title' => 'timestamp']);
    $node->set($field_name, [['sub' => ['value' => self::START, 'end' => self::END]]]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);

    $utc = new \DateTime(self::START, new \DateTimeZone(DateTimeTypeInterface::STORAGE_TIMEZONE));
    $local = new \DateTime(self::START);

    $this->assertSame($local->getTimestamp(), $this->dateRangeTypeInstance()->getTimestamp($item));
    $this->assertSame($utc->getTimestamp(), $this->computedStart($item)->getTimestamp());
    if ($utc->getTimestamp() !== $local->getTimestamp()) {
      $this->assertNotSame(
        $utc->getTimestamp(),
        $this->dateRangeTypeInstance()->getTimestamp($item),
        'CHARACTERIZATION: the two readers disagree by the local UTC offset.',
      );
    }
  }

  /**
   * CHARACTERIZATION: getMaxlength() matches none of the column widths.
   *
   * The inherited CustomFieldTypeBase::getMaxlength() returns 255,
   * against two varchar(20) date columns, a varchar(32) timezone and an int.
   * Its callers -- TextWidget, which also backs the telephone widget, and
   * StringType's sample generator -- never reach a date column.
   */
  public function testGetMaxlengthDisagreesWithTheSchemaWidths(): void {
    $this->assertSame(255, $this->typeInstance()->getMaxlength());

    $schema = DateRangeType::schema(['name' => 'sub']);
    $this->assertSame(20, $schema['sub']['length']);
    $this->assertSame(20, $schema['sub__end']['length']);
    $this->assertSame(32, $schema['sub__timezone']['length']);
  }

  /**
   * CHARACTERIZATION: a column with no datetime_type saves, then throws.
   *
   * DateRangeType overrides propertyDefinitions() and unpacks the key
   * unguarded in its OWN copy -- it does not inherit DateTimeType's -- so
   * both classes carry the defect separately. Both computed
   * properties are then built with a NULL setting, and the first one
   * instantiated throws. The warning is captured, as in DateTimeTypeTest,
   * because core's phpunit.xml.dist sets failOnWarning.
   */
  public function testColumnWithoutDatetimeTypeSavesThenThrowsOnWrite(): void {
    $field_name = 'field_dr_no_type';
    $column = ['name' => 'sub', 'type' => 'daterange'];

    $warnings = [];
    set_error_handler(
      static function (int $severity, string $message) use (&$warnings): bool {
        $warnings[] = $message;
        return TRUE;
      },
      E_WARNING,
    );

    $thrown = NULL;
    try {
      $this->createCustomField(
        $field_name,
        ['sub' => $column],
        ['sub' => ['label' => 'Under test', 'check_empty' => FALSE]],
      );

      $before = count($warnings);
      $options = $this->typeManager->createOptionsForInstance(['label' => 'Under test'], $column);
      $instance = $this->typeManager->createInstance('daterange', $options['configuration']);
      $this->assertInstanceOf(CustomFieldTypeInterface::class, $instance);
      $this->assertSame(DateTimeType::DATETIME_TYPE_DATETIME, $instance->getDatetimeType());
      $this->assertCount($before, $warnings, 'getDatetimeType() reads the key through a default.');

      $properties = DateRangeType::propertyDefinitions($column);
      $this->assertCount(6, $properties);
      $this->assertNull($properties['sub__start_date']->getSetting('datetime_type'));
      $this->assertNull($properties['sub__end_date']->getSetting('datetime_type'));
      $this->assertGreaterThan($before, count($warnings), 'propertyDefinitions() warns.');

      try {
        $node = Node::create(['type' => 'page', 'title' => 'no datetime_type']);
        $node->set($field_name, [['sub' => ['value' => self::START, 'end' => self::END]]]);
      }
      catch (\InvalidArgumentException $e) {
        $thrown = $e;
      }
    }
    finally {
      restore_error_handler();
    }

    $this->assertSame(
      ['Undefined array key "datetime_type"'],
      array_values(array_unique($warnings)),
    );
    $this->assertInstanceOf(\InvalidArgumentException::class, $thrown);
    $this->assertSame("The definition's 'datetime_type' key is required.", $thrown->getMessage());
  }

  /**
   * CONTRACT: the computed start follows a start set via the magic setter.
   *
   * The magic setter reaches CustomItem::onChange(), and
   * DateRangeType::onChange() clears both computed dates. The end is
   * untouched, so the computed end still reports it.
   */
  public function testComputedStartFollowsTheMagicSetter(): void {
    $item = $this->loadedItem('recompute');
    $this->assertSame(self::START, $this->computedStart($item)->format(DateTimeTypeInterface::DATETIME_STORAGE_FORMAT));

    $this->setRawValue($item, 'sub', '2016-11-04T00:21:00');

    $this->assertSame('2016-11-04T00:21:00', $item->get('sub')->getValue());
    $this->assertSame('2016-11-04T00:21:00', $this->computedStart($item)->format(DateTimeTypeInterface::DATETIME_STORAGE_FORMAT));
    $this->assertSame(self::END, $this->computedEnd($item)->format(DateTimeTypeInterface::DATETIME_STORAGE_FORMAT));
  }

  /**
   * CHARACTERIZATION: a property-level setValue() leaves the start stale.
   *
   * CustomFieldDateRange::setValue() never notifies its parent -- the same gap
   * as CustomFieldDatetime::setValue() -- so the memo in '__start_date'
   * survives a change to the stored start. A fix that notified the parent
   * would flip the last assertion.
   */
  public function testComputedStartGoesStaleAfterPropertyLevelSetValue(): void {
    $item = $this->loadedItem('stale');
    // Reading it first is what creates the memo that goes stale.
    $this->assertSame(self::START, $this->computedStart($item)->format(DateTimeTypeInterface::DATETIME_STORAGE_FORMAT));

    $item->get('sub')->setValue('2016-11-04T00:21:00');

    $this->assertSame('2016-11-04T00:21:00', $item->get('sub')->getValue(), 'The stored value did change.');
    $this->assertSame(
      self::START,
      $this->computedStart($item)->format(DateTimeTypeInterface::DATETIME_STORAGE_FORMAT),
      'CHARACTERIZATION: the computed start still reports the OLD value.',
    );
  }

  /**
   * CONTRACT: a first read after a mutation is computed from the new value.
   */
  public function testComputedStartIsCorrectWhenFirstReadAfterMutation(): void {
    $item = $this->loadedItem('fresh');
    $item->get('sub')->setValue('2016-11-04T00:21:00');

    $this->assertSame('2016-11-04T00:21:00', $this->computedStart($item)->format(DateTimeTypeInterface::DATETIME_STORAGE_FORMAT));
  }

  /**
   * CHARACTERIZATION: a nested value left un-instantiated breaks the start.
   *
   * CustomItem::setValue() promotes the nested 'end' and 'timezone' keys to
   * their flat columns, but the start keeps the nested ARRAY as its raw value
   * until something instantiates it. DateTimeComputed passes that array to
   * createFromFormat(), and the \TypeError escapes its catch (\Exception),
   * because \TypeError is an \Error, not an \Exception -- the same escape as
   * for datetime's main property. The promoted end is a plain string, so
   * the computed end is unaffected. Reading the start through get() first
   * normalizes it, which is why the assertion order is deliberate.
   */
  public function testNestedValueRawOnTheItemBreaksTheComputedStart(): void {
    $field_name = $this->createCaseField([], 'raw_nested');
    $node = Node::create(['type' => 'page', 'title' => 'raw nested']);
    $node->set($field_name, [['sub' => ['value' => self::START, 'end' => self::END, 'timezone' => 'UTC']]]);
    $node->save();

    $items = $this->reloadedNode($node)->get($field_name);
    $nested = ['value' => '2016-11-04T00:21:00', 'end' => '2016-11-05T00:21:00', 'timezone' => 'UTC'];
    $items->setValue([['sub' => $nested]]);
    $item = $this->itemAt($items);

    $this->assertSame($nested, $this->rawValue($item, 'sub'));
    $this->assertSame('2016-11-05T00:21:00', $this->rawValue($item, 'sub__end'));
    $this->assertSame('2016-11-05T00:21:00', $this->computedEnd($item)->format(DateTimeTypeInterface::DATETIME_STORAGE_FORMAT));

    try {
      $date = $this->rawValue($item, 'sub__start_date');
      $this->fail('Expected a \TypeError, got ' . get_debug_type($date) . '.');
    }
    catch (\TypeError $e) {
      $this->assertStringContainsString('must be of type string, array given', $e->getMessage());
    }

    $this->assertSame('2016-11-04T00:21:00', $item->get('sub')->getValue());
    $this->assertSame('2016-11-04T00:21:00', $this->computedStart($item)->format(DateTimeTypeInterface::DATETIME_STORAGE_FORMAT));
  }

  /**
   * CONTRACT: without a start, a sole daterange column drops the whole item.
   *
   * CustomItem::isEmpty() consults the start only, so an end, a timezone or a
   * duration on their own do not keep the item -- where core's
   * DateRangeItem::isEmpty() keeps an item that has either date. The shared
   * runner asserts the item is NOT NULL, so these cannot be case rows.
   *
   * @param array<string, mixed> $value
   *   The item values to assign.
   * @param string $suffix
   *   A short, unique field-name suffix for this case.
   *
   * @dataProvider providerItemDroppedWhenStartIsAbsent
   */
  #[DataProvider('providerItemDroppedWhenStartIsAbsent')]
  public function testItemDroppedWhenStartIsAbsent(array $value, string $suffix): void {
    $field_name = $this->createCaseField([], $suffix);
    $node = Node::create(['type' => 'page', 'title' => 'dropped ' . $suffix]);
    $node->set($field_name, [$value]);
    $node->save();

    $this->assertNull($this->storedItem($node, $field_name), 'The item was dropped because the start is empty.');
  }

  /**
   * Data provider for ::testItemDroppedWhenStartIsAbsent().
   *
   * @return array<string, array{array<string, mixed>, string}>
   *   Test cases.
   */
  public static function providerItemDroppedWhenStartIsAbsent(): array {
    return [
      'a nested end and timezone with no start key' => [
        ['sub' => ['end' => self::END, 'timezone' => 'UTC']],
        'drop_missing',
      ],
      'an empty-string start' => [
        ['sub' => ['value' => '', 'end' => self::END, 'timezone' => 'UTC']],
        'drop_empty',
      ],
      'a NULL start' => [
        ['sub' => ['value' => NULL, 'end' => self::END, 'timezone' => 'UTC']],
        'drop_null',
      ],
      'a non-string start' => [
        ['sub' => ['value' => 12345, 'end' => self::END, 'timezone' => 'UTC']],
        'drop_non_string',
      ],
      'a flat end with no start' => [
        ['sub__end' => self::END],
        'drop_flat_end',
      ],
      // The retired TestDaterange plugin's fourth case.
      'a timezone and a duration with no dates' => [
        ['sub__timezone' => 'UTC', 'sub__duration' => 3600],
        'drop_tz_duration',
      ],
    ];
  }

  /**
   * CONTRACT: the range's extras are wiped when a sibling keeps the item.
   *
   * CustomItem::preSave()'s 'daterange' case nulls the end, the timezone and
   * the duration whenever the start is empty. That branch is reachable only
   * when another column keeps the item alive, which is how the retired
   * plugin's fourth case exercised it incidentally.
   */
  public function testExtrasWithoutStartAreWipedWhenSiblingKeepsTheItem(): void {
    $field_name = $this->createFieldWithSibling('field_dr_wiped');
    $node = Node::create(['type' => 'page', 'title' => 'extras wiped']);
    $node->set($field_name, [[
      'sub__end' => self::END,
      'sub__timezone' => 'UTC',
      'sub__duration' => 3600,
      'keep' => 'populated',
    ],
    ]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item, 'A populated sibling column keeps the item.');
    foreach (['sub', 'sub__end', 'sub__timezone', 'sub__duration'] as $property) {
      $this->assertNull($item->get($property)->getValue(), sprintf('"%s" was wiped at save time.', $property));
    }
  }

  /**
   * The sample value is a start/end pair one day apart, per datetime_type.
   *
   * The same shape as core's DateRangeItem::generateSampleValue(); unlike
   * datetime, whose sample is a bare string.
   *
   * @param string $datetime_type
   *   The column's datetime_type.
   * @param string $format
   *   The storage format both dates must match.
   *
   * @dataProvider providerSampleValueShape
   */
  #[DataProvider('providerSampleValueShape')]
  public function testSampleValueShapeFollowsDatetimeType(string $datetime_type, string $format): void {
    $column = $this->defaultColumn($this->columnName());
    $column['datetime_type'] = $datetime_type;
    $options = $this->typeManager->createOptionsForInstance($this->defaultFieldSettings(), $column);
    $instance = $this->typeManager->createInstance(static::typeId(), $options['configuration']);
    $this->assertInstanceOf(CustomFieldTypeInterface::class, $instance);

    $sample = DateRangeType::generateSampleValue($instance, 'node');

    $this->assertSame(['value', 'end'], array_keys($sample));
    $utc = new \DateTimeZone(DateTimeTypeInterface::STORAGE_TIMEZONE);
    $start = \DateTime::createFromFormat($format, $sample['value'], $utc);
    $end = \DateTime::createFromFormat($format, $sample['end'], $utc);
    $this->assertInstanceOf(\DateTime::class, $start);
    $this->assertInstanceOf(\DateTime::class, $end);
    $this->assertSame($sample['value'], $start->format($format));
    $this->assertSame($sample['end'], $end->format($format));
    $this->assertSame('+1', $start->diff($end)->format('%R%a'), 'The end is one day after the start.');
  }

  /**
   * Data provider for ::testSampleValueShapeFollowsDatetimeType().
   *
   * @return array<string, array{string, string}>
   *   Test cases.
   */
  public static function providerSampleValueShape(): array {
    return [
      'datetime' => [DateTimeType::DATETIME_TYPE_DATETIME, DateTimeTypeInterface::DATETIME_STORAGE_FORMAT],
      'date only' => [DateTimeType::DATETIME_TYPE_DATE, DateTimeTypeInterface::DATE_STORAGE_FORMAT],
      'all day' => [DateRangeType::DATETIME_TYPE_ALLDAY, DateTimeTypeInterface::DATETIME_STORAGE_FORMAT],
    ];
  }

  /**
   * The check_empty matrix, with a sibling column to keep the row alive.
   *
   * @param bool $check_empty
   *   The sub-field's check_empty setting.
   * @param string|null $value
   *   The start value to assign.
   * @param bool $expected
   *   Whether the item survives the save.
   *
   * @dataProvider providerCheckEmpty
   */
  #[DataProvider('providerCheckEmpty')]
  public function testCheckEmpty(bool $check_empty, ?string $value, bool $expected): void {
    $field_name = $this->createFieldWithSibling(
      'field_dr_ce_' . (int) $check_empty . '_' . ($value === NULL ? 'null' : ($value === '' ? 'empty' : 'set')),
      $check_empty,
    );

    $node = Node::create(['type' => 'page', 'title' => 'check_empty ' . $field_name]);
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
   * @return array<string, array{bool, string|null, bool}>
   *   Test cases.
   */
  public static function providerCheckEmpty(): array {
    return [
      'check_empty on, blank value' => [TRUE, NULL, FALSE],
      'check_empty on, empty string' => [TRUE, '', FALSE],
      'check_empty on, populated value' => [TRUE, self::START, TRUE],
      'check_empty off, blank value' => [FALSE, NULL, TRUE],
      'check_empty off, empty string' => [FALSE, '', TRUE],
    ];
  }

  /**
   * A repeated programmatic setValue() must keep the end and timezone.
   *
   * This is the JSON:API / REST / migrate / custom-code shape: the nested
   * value array with no widget promotion involved. Without the extraction
   * override the second pass resets both siblings to NULL. Carried over from
   * the retired class, which called validate() without asserting the result.
   *
   * @see https://www.drupal.org/project/custom_field/issues/3622588
   */
  public function testProgrammaticNestedValueSurvivesRepeatedSetValue(): void {
    $field_name = $this->createCaseField([], 'repeated');
    $values = ['sub' => ['value' => self::START, 'end' => self::END, 'timezone' => 'America/New_York']];

    $node = Node::create(['type' => 'page', 'title' => 'Programmatic']);
    $items = $node->get($field_name);

    $items->setValue([$values]);
    $this->assertNoViolations($node, 'first pass');
    $this->assertSame(self::END, $this->itemAt($items)->get('sub__end')->getValue(), 'End set on first pass.');
    $this->assertSame('America/New_York', $this->itemAt($items)->get('sub__timezone')->getValue(), 'Timezone set on first pass.');

    $items->setValue([$values]);
    $this->assertSame(self::END, $this->itemAt($items)->get('sub__end')->getValue(), 'End survives second pass.');
    $this->assertSame('America/New_York', $this->itemAt($items)->get('sub__timezone')->getValue(), 'Timezone survives second pass.');

    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertSame(self::START, $item->get('sub')->getValue());
    $this->assertSame(self::END, $item->get('sub__end')->getValue());
    $this->assertSame('America/New_York', $item->get('sub__timezone')->getValue());
  }

  /**
   * The derived duration is still correct after a repeated setValue().
   *
   * The duration column is deliberately excluded from the extraction because
   * CustomItem::preSave() computes it from the start and end dates. That
   * exclusion is only safe if the computation actually runs on this path.
   */
  public function testDerivedDurationSurvivesRepeatedSetValue(): void {
    $field_name = $this->createCaseField([], 'repeated_dur');
    $values = ['sub' => ['value' => self::START, 'end' => self::END, 'timezone' => 'UTC']];

    $node = Node::create(['type' => 'page', 'title' => 'Duration']);
    $items = $node->get($field_name);
    $items->setValue([$values]);
    $this->assertNoViolations($node);
    $items->setValue([$values]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertSame(self::END, $item->get('sub__end')->getValue(), 'The end value survived.');
    $this->assertSame(self::DURATION, $item->get('sub__duration')->getValue(), 'The duration was computed from start and end.');
  }

  /**
   * The type plugin maps a nested value onto its extra property names.
   *
   * Carried over from the retired class with keys and inputs unchanged; only
   * the column name moves from 'dr' to the base's neutral 'sub'. The only
   * place DateRangeType::extractExtraPropertyValues()'s start-value gate and
   * string guards are pinned at the mapping level.
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

    $this->assertSame($expected, $class::extractExtraPropertyValues(['name' => 'sub'], $value));
  }

  /**
   * Data provider for ::testExtraPropertyMapping().
   *
   * @return array<string, array{mixed, array<string, mixed>}>
   *   Test cases.
   */
  public static function providerMapping(): array {
    return [
      'end and timezone present' => [
        ['value' => self::START, 'end' => self::END, 'timezone' => 'UTC'],
        ['sub__end' => self::END, 'sub__timezone' => 'UTC'],
      ],
      'end missing' => [
        ['value' => self::START, 'timezone' => 'UTC'],
        ['sub__timezone' => 'UTC'],
      ],
      'end NULL' => [
        ['value' => self::START, 'end' => NULL, 'timezone' => 'UTC'],
        ['sub__timezone' => 'UTC'],
      ],
      'end empty string' => [
        ['value' => self::START, 'end' => '', 'timezone' => 'UTC'],
        ['sub__timezone' => 'UTC'],
      ],
      'end non-string' => [
        ['value' => self::START, 'end' => 12345, 'timezone' => 'UTC'],
        ['sub__timezone' => 'UTC'],
      ],
      'timezone missing' => [
        ['value' => self::START, 'end' => self::END],
        ['sub__end' => self::END],
      ],
      'timezone NULL' => [
        ['value' => self::START, 'end' => self::END, 'timezone' => NULL],
        ['sub__end' => self::END],
      ],
      'timezone empty string' => [
        ['value' => self::START, 'end' => self::END, 'timezone' => ''],
        ['sub__end' => self::END, 'sub__timezone' => ''],
      ],
      // CHARACTERIZATION, not contract: this pins the (string) cast that
      // CustomFieldDateRange::setValue() already applies, so the override
      // provably matches it. Storing '0' as a timezone is not a meaningful
      // value; a future guard may legitimately make this expectation wrong.
      'timezone non-string is cast' => [
        ['value' => self::START, 'end' => self::END, 'timezone' => 0],
        ['sub__end' => self::END, 'sub__timezone' => '0'],
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
        ['sub__end' => self::END],
      ],
      'not an array' => [self::START, []],
      'empty array' => [[], []],
    ];
  }

  /**
   * Creates a field with this type's column plus a populated string sibling.
   *
   * Some behavior -- preSave()'s wipe of a range with no start, the check_empty
   * matrix -- is reachable only when something other than the range keeps
   * the item alive.
   */
  private function createFieldWithSibling(string $field_name, bool $check_empty = FALSE): string {
    $this->createCustomField(
      $field_name,
      [
        'sub' => $this->defaultColumn('sub'),
        'keep' => ['name' => 'keep', 'type' => 'string'],
      ],
      [
        'sub' => ['label' => 'Under test', 'check_empty' => $check_empty],
        'keep' => ['label' => 'Populated sibling', 'check_empty' => FALSE],
      ],
    );

    return $field_name;
  }

  /**
   * Saves a ::START/::END range and returns its item from a reloaded node.
   */
  private function loadedItem(string $suffix): FieldItemInterface {
    $field_name = $this->createCaseField([], $suffix);
    $node = Node::create(['type' => 'page', 'title' => $suffix]);
    $node->set($field_name, [['sub' => ['value' => self::START, 'end' => self::END]]]);
    $node->save();

    $item = $this->storedItem($this->reloadedNode($node), $field_name);
    $this->assertNotNull($item);

    return $item;
  }

  /**
   * Returns a property's raw value, without instantiating it.
   *
   * FieldItemBase::__get() returns the property object's value as long as
   * that object already exists, and the raw stored value otherwise, so the
   * two differ until something instantiates the property. The property name
   * is passed as a variable so this stays the single documented place where
   * that read happens.
   */
  private function rawValue(FieldItemInterface $item, string $property): mixed {
    return $item->{$property};
  }

  /**
   * Assigns a property through the item's magic setter.
   *
   * A different code path from get()->setValue(): it reaches
   * CustomItem::onChange() where the property-level call does not.
   */
  private function setRawValue(FieldItemInterface $item, string $property, mixed $value): void {
    $item->{$property} = $value;
  }

  /**
   * Returns a delta's item from a list, asserted to exist.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface<\Drupal\Core\Field\FieldItemInterface> $items
   *   The item list.
   * @param int $delta
   *   The delta to read.
   *
   * @return \Drupal\Core\Field\FieldItemInterface
   *   The item at that delta.
   */
  private function itemAt(FieldItemListInterface $items, int $delta = 0): FieldItemInterface {
    $item = $items[$delta];
    $this->assertInstanceOf(FieldItemInterface::class, $item);

    return $item;
  }

  /**
   * Reloads a saved node from storage, asserting it has an id.
   */
  private function reloadedNode(Node $node): NodeInterface {
    $id = $node->id();
    $this->assertNotNull($id);

    return $this->reloadNode($id);
  }

  /**
   * Returns the start property narrowed to its own data type class.
   *
   * Narrowed to the class because getTimezone() and getDuration() are
   * declared on it alone, and because it implements no date interface to
   * narrow to -- see ::testStartAndEndCarryDifferentDataTypeContracts().
   */
  private function startProperty(FieldItemInterface $item): CustomFieldDateRange {
    $property = $item->get('sub');
    $this->assertInstanceOf(CustomFieldDateRange::class, $property);

    return $property;
  }

  /**
   * Returns the end property narrowed to the typed-data date contract.
   */
  private function endProperty(FieldItemInterface $item): DateTimeInterface {
    $property = $item->get('sub__end');
    $this->assertInstanceOf(DateTimeInterface::class, $property);

    return $property;
  }

  /**
   * Returns the computed '__start_date' for an item.
   */
  private function computedStart(FieldItemInterface $item): DrupalDateTime {
    $date = $this->rawValue($item, 'sub__start_date');
    $this->assertInstanceOf(DrupalDateTime::class, $date);

    return $date;
  }

  /**
   * Returns the computed '__end_date' for an item.
   */
  private function computedEnd(FieldItemInterface $item): DrupalDateTime {
    $date = $this->rawValue($item, 'sub__end_date');
    $this->assertInstanceOf(DrupalDateTime::class, $date);

    return $date;
  }

  /**
   * Returns the instance narrowed to the concrete range type class.
   *
   * The getTimestamp() method is declared only on DateTimeType, which this
   * class extends; no interface declares it.
   */
  private function dateRangeTypeInstance(): DateRangeType {
    $instance = $this->typeInstance();
    $this->assertInstanceOf(DateRangeType::class, $instance);

    return $instance;
  }

  /**
   * Builds a configured plugin instance the way production code does.
   */
  private function typeInstance(): CustomFieldTypeInterface {
    $options = $this->typeManager->createOptionsForInstance(
      $this->defaultFieldSettings(),
      $this->defaultColumn($this->columnName()),
    );
    $instance = $this->typeManager->createInstance(static::typeId(), $options['configuration']);
    $this->assertInstanceOf(CustomFieldTypeInterface::class, $instance);

    return $instance;
  }

}
