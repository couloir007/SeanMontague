<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Type;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\Type\DateTimeInterface;
use Drupal\custom_field\Plugin\CustomField\FieldType\DateTimeType;
use Drupal\custom_field\Plugin\CustomField\FieldType\DateTimeTypeInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldTypeKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the 'datetime' custom field sub-field type.
 *
 * The core counterpart is
 * \Drupal\Tests\datetime\Kernel\DateTimeItemTest, and this class follows its
 * shape: a storage round trip, the date-only mode, the computed date object,
 * and sample generation. Three structural differences drive everything that
 * looks different here.
 *
 * 1. 'datetime_type' is a per-COLUMN setting on this module, not a field
 *    storage setting as in core, so it is varied through the case rows'
 *    'column' key rather than by building a second field by hand.
 * 2. This type carries a second stored column, '__timezone', which core has
 *    no equivalent of. It is the only part of the type with live validation:
 *    an AllowedValues constraint declared on the PROPERTY
 *    (DateTimeType::propertyDefinitions()), not in the plugin attribute.
 * 3. DateTimeType declares NO 'constraints' key in its attribute, and
 *    CustomFieldTypeBase carries no attribute to inherit one from, so there
 *    is no equivalent of core's "DateTimeFormat" constraint. Core rejects 40
 *    malformed inputs that this module accepts; a representative subset is
 *    pinned as characterization in ::testMalformedValuesAreNotRejected().
 *
 * What is deliberately NOT covered here, to avoid duplicating coverage that
 * already exists: the widget surface (the three Functional widget classes
 * cover 26 methods of rendering, timezone selects, seconds and required
 * validation), the formatters, the extra-property API invariants
 * (ExtraPropertyApiTest), and the default widget/formatter wiring
 * (TypeRegistryTest). The onChange() dispatch surface is out of scope by
 * decision: it is left to tests of CustomItem::onChange()'s dispatch itself.
 * What IS pinned here is the observable outcome -- whether the computed
 * '__date' reflects a changed stored value -- which turns out to depend on
 * which write path was used.
 *
 * Divergences from core's behavior are called out explicitly where they
 * occur, and are never "fixed" toward core in this characterization-first
 * test class.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldType\DateTimeType
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(DateTimeType::class)]
class DateTimeTypeTest extends CustomFieldTypeKernelTestBase {

  /**
   * A storage-format datetime value.
   */
  private const DATE = '2024-01-15T10:30:00';

  /**
   * A second storage-format datetime value, on a DST transition date.
   *
   * Carried over from the retired TestDatetime plugin's second case.
   */
  private const DATE_DST = '2016-11-04T00:21:00';

  /**
   * A storage-format date-only value.
   */
  private const DATE_ONLY = '2014-01-01';

  /**
   * {@inheritdoc}
   *
   * 'datetime' supplies the DateTime element and the date formats the
   * computed property parses with.
   */
  protected static $modules = [
    'datetime',
  ];

  /**
   * {@inheritdoc}
   */
  protected static function typeId(): string {
    return 'datetime';
  }

  /**
   * {@inheritdoc}
   *
   * 'datetime_type' is a required column key for this type: without it the
   * computed property's constructor throws, which
   * ::testColumnWithoutDatetimeTypeSavesThenThrowsOnWrite() pins. Cases that
   * need the date-only mode override it through their own 'column' key.
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
   * CustomFieldTypeBase::getFieldSettings(), so the cases below do not depend
   * on it. The three widget-only settings are included because they ARE this
   * type's declared defaults and omitting them would make the saved field
   * disagree with ::testDefaultFieldSettings(); nothing in this class varies
   * them, because nothing outside DateTimeWidgetBase reads them.
   */
  protected function defaultFieldSettings(): array {
    return [
      'timezone_enabled' => FALSE,
      'timezone_options' => [],
      'seconds_enabled' => TRUE,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   *
   * Every expectation reads the stored item through
   * FieldItemInterface::get(), which instantiates the property object. For
   * this type that matters in one direction only: an absent timezone and an
   * explicitly NULL one are indistinguishable here because both store SQL
   * NULL, while an empty-string timezone is distinct because
   * DateTimeType::extractExtraPropertyValues() guards on isset() and
   * therefore DOES extract ''. The rows are kept separate because the INPUTS
   * differ and each must be accepted without error.
   *
   * The date-only row is the settings variation that replaces
   * CustomFieldItemTest::testDateOnly()'s datetime half.
   */
  protected function roundTripCases(): array {
    return [
      'a timezone that was never supplied stays NULL' => [
        'value' => ['sub' => ['value' => self::DATE]],
        'expected' => ['sub' => self::DATE, 'sub__timezone' => NULL],
      ],
      'an explicitly NULL timezone stays NULL' => [
        'value' => ['sub' => ['value' => self::DATE, 'timezone' => NULL]],
        'expected' => ['sub' => self::DATE, 'sub__timezone' => NULL],
      ],
      'an empty-string timezone is stored as an empty string' => [
        'value' => ['sub' => ['value' => self::DATE, 'timezone' => '']],
        'expected' => ['sub' => self::DATE, 'sub__timezone' => ''],
      ],
      'a named timezone round-trips' => [
        'value' => ['sub' => ['value' => self::DATE, 'timezone' => 'America/New_York']],
        'expected' => ['sub' => self::DATE, 'sub__timezone' => 'America/New_York'],
      ],
      'UTC round-trips like any other named timezone' => [
        'value' => ['sub' => ['value' => self::DATE, 'timezone' => 'UTC']],
        'expected' => ['sub' => self::DATE, 'sub__timezone' => 'UTC'],
      ],
      'a DST transition date round-trips' => [
        'value' => ['sub' => ['value' => self::DATE_DST, 'timezone' => 'America/New_York']],
        'expected' => ['sub' => self::DATE_DST, 'sub__timezone' => 'America/New_York'],
      ],
      'a scalar assignment sets the date and leaves the timezone NULL' => [
        'value' => ['sub' => self::DATE],
        'expected' => ['sub' => self::DATE, 'sub__timezone' => NULL],
      ],
      'a date-only column stores the bare date' => [
        'column' => ['datetime_type' => DateTimeType::DATETIME_TYPE_DATE],
        'value' => ['sub' => self::DATE_ONLY],
        'expected' => ['sub' => self::DATE_ONLY, 'sub__timezone' => NULL],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * The only live validation this type has. It is declared on the timezone
   * PROPERTY rather than in the plugin attribute, so
   * ::testConstraintsLiveOnThePropertyNotThePlugin() asserts that split
   * directly -- an empty getConstraints() does not mean "no validation" for
   * this type, which is the trap the shared guard in the base class exists to
   * catch from the other direction.
   */
  protected function validationCases(): array {
    return [
      'an unknown timezone identifier is rejected' => [
        'value' => ['sub' => ['value' => self::DATE, 'timezone' => 'invalid']],
        'property' => 'sub__timezone',
        'message' => 'The value you selected is not a valid choice.',
        'count' => 1,
      ],
      // A value wider than the varchar(32) timezone column. There is no
      // Length constraint anywhere for this type, so what rejects it is the
      // AllowedValues list -- incidentally, because no real identifier is
      // that long. The date column has no such accidental guard, which
      // ::testOverLengthValueIsRejectedByTheDatabaseNotByValidation() pins.
      'a timezone wider than its column is rejected as a bad choice' => [
        'value' => ['sub' => ['value' => self::DATE, 'timezone' => str_repeat('z', 33)]],
        'property' => 'sub__timezone',
        'message' => 'The value you selected is not a valid choice.',
        'count' => 1,
      ],
    ];
  }

  /**
   * The schema is the date column plus a timezone column.
   *
   * Two divergences from core, both deliberate here and neither "fixed":
   * core's DateTimeItem::schema() declares a description on its value column
   * and an index on it, and has no timezone column at all. The missing index
   * matters because this module ships Views sort/filter/argument handlers
   * that order by this column.
   */
  public function testSchema(): void {
    $schema = DateTimeType::schema(['name' => 'sub']);

    // The timezone description is built with a translation object.
    $schema['sub__timezone']['description'] = (string) $schema['sub__timezone']['description'];

    $this->assertSame([
      'sub' => [
        'type' => 'varchar',
        'length' => 20,
      ],
      'sub__timezone' => [
        'description' => 'The preferred timezone.',
        'type' => 'varchar',
        'length' => 32,
      ],
    ], $schema);
  }

  /**
   * The property definitions: the date, the computed object, the timezone.
   */
  public function testPropertyDefinitions(): void {
    $properties = DateTimeType::propertyDefinitions([
      'name' => 'sub',
      'datetime_type' => DateTimeType::DATETIME_TYPE_DATETIME,
    ]);

    $this->assertSame(['sub', 'sub__date', 'sub__timezone'], array_keys($properties));

    $this->assertSame('custom_field_datetime', $properties['sub']->getDataType());
    // Core's DateTimeItem marks its value property required; this one does
    // not, because a custom field row may legitimately carry no date.
    $this->assertFalse($properties['sub']->isRequired());
    $this->assertSame(
      DateTimeType::DATETIME_TYPE_DATETIME,
      $properties['sub']->getSetting('datetime_type'),
    );

    $this->assertSame('any', $properties['sub__date']->getDataType());
    $this->assertTrue($properties['sub__date']->isComputed());
    $this->assertSame(
      'Drupal\custom_field\Plugin\CustomField\DateTimeComputed',
      ltrim($properties['sub__date']->getClass(), '\\'),
    );
    $this->assertSame('sub', $properties['sub__date']->getSetting('date source'));

    $this->assertSame('string', $properties['sub__timezone']->getDataType());
    $this->assertTrue($properties['sub__timezone']->isInternal());
    $this->assertFalse($properties['sub__timezone']->isRequired());
    $this->assertSame(32, $properties['sub__timezone']->getSetting('max_length'));
  }

  /**
   * The one extra property name, derived from the schema.
   *
   * The computed '__date' property is NOT an extra property name: the names
   * are derived from schema() and the computed property has no column.
   */
  public function testExtraPropertyNamesAreExact(): void {
    $this->assertSame(
      ['sub__timezone'],
      DateTimeType::getExtraPropertyNames(['name' => 'sub']),
    );
  }

  /**
   * The declared default field settings, including datetime's own three.
   */
  public function testDefaultFieldSettings(): void {
    $this->assertSame([
      'timezone_enabled' => FALSE,
      'timezone_options' => [],
      'seconds_enabled' => TRUE,
      'label' => '',
      'required' => FALSE,
      'translatable' => FALSE,
      'description' => '',
      'description_display' => 'after',
      'check_empty' => FALSE,
    ], DateTimeType::defaultFieldSettings());
  }

  /**
   * CONTRACT: the type's validation lives on a property, not on the plugin.
   *
   * The getConstraints() method is empty and the plugin attribute declares no
   * 'constraints' key, yet the type is still validated: the timezone
   * property carries an AllowedValues constraint, which validationCases()
   * exercises. This asserts the split directly rather than inferring it,
   * because the inverse mistake -- assuming a child class inherits a
   * parent's attribute 'constraints' key -- is how time_range lost time's
   * check, and DateRangeType extends this class with its own attribute that
   * likewise omits the key.
   *
   * @see https://www.drupal.org/project/custom_field/issues/3623453
   */
  public function testConstraintsLiveOnThePropertyNotThePlugin(): void {
    $this->assertSame([], $this->typeInstance()->getConstraints());

    $properties = DateTimeType::propertyDefinitions([
      'name' => 'sub',
      'datetime_type' => DateTimeType::DATETIME_TYPE_DATETIME,
    ]);
    $constraints = $properties['sub__timezone']->getConstraints();
    $this->assertArrayHasKey('AllowedValues', $constraints);
    $this->assertContains('UTC', $constraints['AllowedValues']);
    // The empty string is deliberately an allowed value, which is what makes
    // the empty-string round-trip row above validate.
    $this->assertContains('', $constraints['AllowedValues']);
  }

  /**
   * CONTRACT: a stored date is read back in the storage timezone.
   *
   * Both readers of the column agree on this, and both are pinned because
   * CustomFieldItemTest asserted both: the main property's own getDateTime()
   * and the computed '__date' object. The stored '__timezone' is a display
   * preference and does not shift either of them.
   */
  public function testStoredDateIsReadBackInStorageTimezone(): void {
    $field_name = $this->createCaseField([], 'tz');
    $node = Node::create(['type' => 'page', 'title' => 'storage timezone']);
    $node->set($field_name, [['sub' => ['value' => self::DATE, 'timezone' => 'America/New_York']]]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);

    $main = $this->mainDateTime($item);
    $this->assertSame(DateTimeTypeInterface::STORAGE_TIMEZONE, $main->getTimezone()->getName());
    $this->assertSame(self::DATE, $main->format(DateTimeTypeInterface::DATETIME_STORAGE_FORMAT));

    $computed = $this->computedDate($item);
    $this->assertSame(DateTimeTypeInterface::STORAGE_TIMEZONE, $computed->getTimezone()->getName());
    $this->assertSame(self::DATE, $computed->format(DateTimeTypeInterface::DATETIME_STORAGE_FORMAT));

    $this->assertSame('America/New_York', $item->get('sub__timezone')->getValue());
  }

  /**
   * CONTRACT: a date-only column parses at midnight, and noons on demand.
   *
   * Ported from the datetime half of the removed
   * CustomFieldItemTest::testDateOnly(). The assertions are kept as they
   * were -- and extended with the one thing that
   * class did not look at: the computed '__date' is ALREADY noon, because
   * DateTimeComputed::getValue() calls setDefaultDateTime() itself for a
   * date-only column. So the two readers of one stored value disagree about
   * the time of day until the caller normalizes the main one, which is
   * exactly why both are pinned here.
   */
  public function testDateOnlyModeParsesAtMidnight(): void {
    $field_name = $this->createCaseField(
      ['column' => ['datetime_type' => DateTimeType::DATETIME_TYPE_DATE]],
      'date_only',
    );
    $node = Node::create(['type' => 'page', 'title' => 'date only']);
    $node->set($field_name, [['sub' => self::DATE_ONLY]]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertSame(self::DATE_ONLY, $item->get('sub')->getValue());

    $main = $this->mainDateTime($item);
    $this->assertSame(DateTimeTypeInterface::STORAGE_TIMEZONE, $main->getTimezone()->getName());
    $this->assertSame('00:00:00', $main->format('H:i:s'));
    $main->setDefaultDateTime();
    $this->assertSame('12:00:00', $main->format('H:i:s'));

    $computed = $this->computedDate($item);
    $this->assertSame('12:00:00', $computed->format('H:i:s'), 'The computed date is noon without being asked.');
    $this->assertSame(DateTimeTypeInterface::STORAGE_TIMEZONE, $computed->getTimezone()->getName());
  }

  /**
   * CONTRACT: the computed date follows the stored value on the magic setter.
   *
   * The paired characterization is
   * ::testComputedDateGoesStaleAfterPropertyLevelSetValue(). This is the
   * path core's own DateTimeItemTest::testDateTime() exercises, and on this
   * module it behaves the same way: assigning through the item's magic
   * setter reaches CustomItem::onChange(), which clears the computed
   * property's memo.
   */
  public function testComputedDateFollowsTheMagicSetter(): void {
    $field_name = $this->createCaseField([], 'recompute');
    $node = Node::create(['type' => 'page', 'title' => 'recompute']);
    $node->set($field_name, [['sub' => self::DATE]]);
    $node->save();

    $item = $this->storedItem($this->reloadedNode($node), $field_name);
    $this->assertNotNull($item);
    $this->assertSame(self::DATE, $this->computedDate($item)->format(DateTimeTypeInterface::DATETIME_STORAGE_FORMAT));

    $this->setRawValue($item, 'sub', self::DATE_DST);

    $this->assertSame(self::DATE_DST, $item->get('sub')->getValue());
    $this->assertSame(
      self::DATE_DST,
      $this->computedDate($item)->format(DateTimeTypeInterface::DATETIME_STORAGE_FORMAT),
      'The computed date was recomputed from the new stored value.',
    );
  }

  /**
   * CHARACTERIZATION: a property-level setValue() leaves __date stale.
   *
   * CustomFieldDatetime::setValue() overrides TypedData::setValue() and never
   * notifies its parent, so CustomItem::onChange() is not reached and
   * DateTimeComputed keeps the value it memoized on the previous read. The
   * stored value and the computed object then disagree, for the lifetime of
   * the item, with no error anywhere.
   *
   * Confirmed by running, both directions: the magic setter recomputes
   * (::testComputedDateFollowsTheMagicSetter()) and this path does not. Core
   * has no equivalent gap -- core's DateTimeItem has no custom data-type
   * setValue() to drop the notification.
   *
   * A fix that made CustomFieldDatetime::setValue() notify its parent would
   * flip this test's expectation.
   */
  public function testComputedDateGoesStaleAfterPropertyLevelSetValue(): void {
    $field_name = $this->createCaseField([], 'stale');
    $node = Node::create(['type' => 'page', 'title' => 'stale']);
    $node->set($field_name, [['sub' => self::DATE]]);
    $node->save();

    $item = $this->storedItem($this->reloadedNode($node), $field_name);
    $this->assertNotNull($item);
    // Reading it first is what creates the memo that goes stale.
    $this->assertSame(self::DATE, $this->computedDate($item)->format(DateTimeTypeInterface::DATETIME_STORAGE_FORMAT));

    $item->get('sub')->setValue(self::DATE_DST);

    $this->assertSame(self::DATE_DST, $item->get('sub')->getValue(), 'The stored value did change.');
    $this->assertSame(
      self::DATE,
      $this->computedDate($item)->format(DateTimeTypeInterface::DATETIME_STORAGE_FORMAT),
      'CHARACTERIZATION: the computed date still reports the OLD value.',
    );
  }

  /**
   * CONTRACT: a first read after a mutation is computed from the new value.
   *
   * The companion to the stale case: nothing is wrong with the computation
   * itself, only with the memo's invalidation, so an item whose computed
   * property was never read before the mutation reports the new value even
   * on the un-notified path.
   */
  public function testComputedDateIsCorrectWhenFirstReadAfterMutation(): void {
    $field_name = $this->createCaseField([], 'fresh');
    $node = Node::create(['type' => 'page', 'title' => 'fresh']);
    $node->set($field_name, [['sub' => self::DATE]]);
    $node->save();

    $item = $this->storedItem($this->reloadedNode($node), $field_name);
    $this->assertNotNull($item);
    $item->get('sub')->setValue(self::DATE_DST);

    $this->assertSame(
      self::DATE_DST,
      $this->computedDate($item)->format(DateTimeTypeInterface::DATETIME_STORAGE_FORMAT),
      'With no memo to invalidate, the computed date is correct.',
    );
  }

  /**
   * CHARACTERIZATION: setDateTime() writes a value nothing can read back.
   *
   * CustomFieldDatetime::setDateTime() stores $dateTime->format('c'), an
   * offset-bearing ISO-8601 string, where the column's storage format is
   * 'Y-m-d\TH:i:s'. Two consequences, both confirmed by reading the value
   * back rather than from the shape of the code:
   *
   * 1. The computed '__date' becomes NULL, because
   *    DrupalDateTime::createFromFormat() cannot parse the offset with the
   *    storage format.
   * 2. The value is 25 characters, so saving it hits the varchar(20) column
   *    and the save throws. The method is therefore unusable on every path:
   *    the value cannot be read back and cannot be persisted.
   *
   * No caller exists in this module, which is why this has gone unnoticed.
   * A fix that formatted with DATETIME_STORAGE_FORMAT would flip both
   * expectations.
   */
  public function testSetDateTimeWritesAnUnreadableValue(): void {
    $field_name = $this->createCaseField([], 'set_date_time');
    $node = Node::create(['type' => 'page', 'title' => 'setDateTime']);
    $node->set($field_name, [['sub' => self::DATE]]);
    $node->save();

    $item = $this->storedItem($this->reloadedNode($node), $field_name);
    $this->assertNotNull($item);

    $this->mainProperty($item)->setDateTime(
      new DrupalDateTime('2035-11-06 04:00:00', DateTimeTypeInterface::STORAGE_TIMEZONE),
    );

    $written = $item->get('sub')->getValue();
    $this->assertSame('2035-11-06T04:00:00+00:00', $written);
    $this->assertSame(25, strlen($written), 'Five characters past the varchar(20) column.');
    $this->assertNull(
      $this->rawValue($item, 'sub__date'),
      'CHARACTERIZATION: the computed date cannot parse what setDateTime() wrote.',
    );

    $this->expectException(EntityStorageException::class);
    $node->save();
  }

  /**
   * CHARACTERIZATION: an over-length value passes validation, then fails.
   *
   * No Length constraint exists for this type -- getConstraints() is empty
   * and the only property-level constraint is the timezone's AllowedValues --
   * so a value longer than the varchar(20) column validates cleanly and is
   * only stopped by the database. Same shape as uri's and link's missing
   * Length constraint.
   *
   * What the database does is recorded as a MEASURED LOCAL fact, not a
   * portable expectation: on this project's MariaDB in strict mode the insert
   * raises EntityStorageException wrapping SQLSTATE[22001]. A non-strict
   * MySQL would truncate and SQLite would store the value whole, which is why
   * the validation half above is the portable half of this pin.
   */
  public function testOverLengthValueIsRejectedByTheDatabaseNotByValidation(): void {
    $field_name = $this->createCaseField([], 'over_length');
    // 25 characters: the offset form, five past the column width.
    $value = '2024-01-15T10:30:00+01:00';
    $this->assertSame(25, strlen($value));

    $node = Node::create(['type' => 'page', 'title' => 'over length']);
    $node->set($field_name, [['sub' => $value]]);
    $this->assertNoViolations($node, 'no Length constraint exists for datetime');

    $this->expectException(EntityStorageException::class);
    $node->save();
  }

  /**
   * CHARACTERIZATION: malformed values are stored, and read back as NULL.
   *
   * Core's DateTimeItem declares a "DateTimeFormat" constraint and its
   * kernel test rejects 40 malformed inputs across two providers. This
   * module declares no equivalent constraint, so the only thing standing in
   * the way is the PrimitiveType constraint typed data attaches
   * automatically -- and that only checks that the value is PARSEABLE, not
   * that it matches the storage format. Anything PHP's date parser accepts
   * is therefore stored verbatim, and the computed '__date' then reads back
   * as NULL because createFromFormat() with the storage format fails on it.
   *
   * The third column is the decisive one: DateTimeType::getTimestamp() uses
   * new \DateTime(), the same loose parser, so it happily returns a
   * timestamp for exactly the values the computed property rejects. Two
   * readers of one stored column disagree about whether it holds a date.
   *
   * Over-length values are covered separately in
   * ::testOverLengthValueIsRejectedByTheDatabaseNotByValidation(), because
   * they never reach storage at all.
   *
   * @param string $value
   *   The malformed value to store.
   * @param bool $expect_violation
   *   Whether the loose PrimitiveType check rejects it.
   * @param bool $expect_computed_date
   *   Whether the computed '__date' can be built from the stored value.
   * @param string $suffix
   *   A short, unique field-name suffix for this case.
   *
   * @dataProvider providerMalformedValues
   */
  #[DataProvider('providerMalformedValues')]
  public function testMalformedValuesAreNotRejected(
    string $value,
    bool $expect_violation,
    bool $expect_computed_date,
    string $suffix,
  ): void {
    $field_name = $this->createCaseField([], $suffix);
    $node = Node::create(['type' => 'page', 'title' => 'malformed ' . $suffix]);
    $node->set($field_name, [['sub' => $value]]);

    if ($expect_violation) {
      $this->assertViolation($node, 'sub', 'This value should be of the correct primitive type.');
      return;
    }

    $this->assertNoViolations($node, 'malformed value "' . $value . '"');
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertSame($value, $item->get('sub')->getValue(), 'The malformed value was stored verbatim.');

    $expect_computed_date
      ? $this->assertInstanceOf(DrupalDateTime::class, $this->rawValue($item, 'sub__date'))
      : $this->assertNull($this->rawValue($item, 'sub__date'), 'The computed date cannot be built from this value.');

    // getTimestamp() parses loosely, so it answers where __date could not.
    $this->assertIsInt(
      $this->dateTimeTypeInstance()->getTimestamp($item),
      'getTimestamp() returns a timestamp for a value the computed date rejected.',
    );
  }

  /**
   * Data provider for ::testMalformedValuesAreNotRejected().
   *
   * A representative subset of core's two rejection providers, one row per
   * distinct shape rather than all 40 rows.
   *
   * @return array<string, array{string, bool, bool, string}>
   *   Test cases.
   */
  public static function providerMalformedValues(): array {
    return [
      'the storage format itself, as a control' => [self::DATE, FALSE, TRUE, 'ok'],
      'a trailing Z' => ['2024-01-15T10:30:00Z', FALSE, FALSE, 'zulu'],
      'a space instead of the T separator' => ['2016-11-03 20:52:00', FALSE, FALSE, 'space'],
      'a human-readable date' => ['11/03/2016', FALSE, FALSE, 'human'],
      'a date-only value in a datetime column' => [self::DATE_ONLY, FALSE, FALSE, 'date_only_in_datetime'],
      'a value PHP cannot parse at all' => ['Not a date at all', TRUE, FALSE, 'garbage'],
    ];
  }

  /**
   * CHARACTERIZATION: a datetime value in a date-only column is stored.
   *
   * The mirror of the date-only row in
   * ::providerMalformedValues(): the column's declared type is what decides
   * the storage format, and neither direction is enforced on the value. Here
   * the stored string carries trailing time data that the date-only storage
   * format cannot consume, so the computed date is NULL -- confirmed by
   * reading it, not inferred from createFromFormat()'s documented
   * strictness.
   */
  public function testDatetimeValueInDateOnlyColumnIsStored(): void {
    $field_name = $this->createCaseField(
      ['column' => ['datetime_type' => DateTimeType::DATETIME_TYPE_DATE]],
      'dt_in_date_col',
    );
    $node = Node::create(['type' => 'page', 'title' => 'datetime in a date column']);
    $node->set($field_name, [['sub' => '2014-01-01T20:00:00']]);
    $this->assertNoViolations($node);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertSame('2014-01-01T20:00:00', $item->get('sub')->getValue());
    $this->assertNull($this->rawValue($item, 'sub__date'), 'The trailing time data defeats the date-only storage format.');
  }

  /**
   * CHARACTERIZATION: getTimestamp() reads the stored value in PHP's zone.
   *
   * The value is stored in UTC and every other reader treats it as UTC, but
   * getTimestamp() builds new \DateTime($value) with no timezone argument,
   * so PHP's default timezone decides the instant. The method has no callers
   * anywhere in the module, so this is latent -- the same shape as
   * TelephoneType::MAX_LENGTH, a constant nothing reads.
   *
   * Asserted as a relationship rather than against a hardcoded integer, so
   * the test does not depend on the container's configured timezone: the
   * returned timestamp equals the one PHP's loose parser produces, which
   * equals the true UTC instant only when the default timezone IS UTC.
   */
  public function testGetTimestampIgnoresTheStorageTimezone(): void {
    $field_name = $this->createCaseField([], 'timestamp');
    $node = Node::create(['type' => 'page', 'title' => 'timestamp']);
    $node->set($field_name, [['sub' => self::DATE]]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);

    $utc = new \DateTime(self::DATE, new \DateTimeZone(DateTimeTypeInterface::STORAGE_TIMEZONE));
    $local = new \DateTime(self::DATE);
    $offset = $utc->getTimestamp() - $local->getTimestamp();

    // What it returns is the LOCAL reading of a value stored as UTC.
    $this->assertSame($local->getTimestamp(), $this->dateTimeTypeInstance()->getTimestamp($item));

    // And that is the true instant only when the two readings coincide, i.e.
    // when PHP's default timezone has no offset from UTC on this date. The
    // computed '__date' property, which passes the storage timezone
    // explicitly, is the one that is always right.
    $this->assertSame(
      $utc->getTimestamp(),
      $this->computedDate($item)->getTimestamp(),
      'The computed date resolves the stored value in the storage timezone.',
    );
    if ($offset !== 0) {
      $this->assertNotSame(
        $utc->getTimestamp(),
        $this->dateTimeTypeInstance()->getTimestamp($item),
        'CHARACTERIZATION: the two readers disagree by the local UTC offset.',
      );
    }
  }

  /**
   * CHARACTERIZATION: getTimestamp() returns NULL for an empty stored value.
   */
  public function testGetTimestampIsNullWithoutStoredValue(): void {
    $field_name = 'field_ts_empty';
    $this->createCustomField(
      $field_name,
      [
        'sub' => ['name' => 'sub', 'type' => 'datetime', 'datetime_type' => DateTimeType::DATETIME_TYPE_DATETIME],
        'keep' => ['name' => 'keep', 'type' => 'string'],
      ],
      [
        'sub' => ['label' => 'Under test', 'check_empty' => FALSE],
        'keep' => ['label' => 'Populated sibling', 'check_empty' => FALSE],
      ],
    );

    $node = Node::create(['type' => 'page', 'title' => 'no date']);
    $node->set($field_name, [['keep' => 'populated']]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertNull($this->dateTimeTypeInstance()->getTimestamp($item));
  }

  /**
   * CONTRACT: a timezone with no date is wiped when a sibling keeps the item.
   *
   * CustomItem::preSave()'s 'datetime' case nulls the timezone whenever the
   * date is empty, and that branch is only observable when something else in
   * the row keeps the item alive -- a datetime column on its own takes the
   * item down with it, which ::testItemDroppedWhenDateIsAbsent() covers.
   * This is the shape the retired TestDatetime plugin's fourth case tested
   * only incidentally, because the shared fixture happened to populate
   * twenty-two sibling columns.
   */
  public function testTimezoneWithoutDateIsWipedWhenSiblingKeepsTheItem(): void {
    $field_name = 'field_dt_tz_wiped';
    $this->createCustomField(
      $field_name,
      [
        'sub' => ['name' => 'sub', 'type' => 'datetime', 'datetime_type' => DateTimeType::DATETIME_TYPE_DATETIME],
        'keep' => ['name' => 'keep', 'type' => 'string'],
      ],
      [
        'sub' => ['label' => 'Under test', 'check_empty' => FALSE],
        'keep' => ['label' => 'Populated sibling', 'check_empty' => FALSE],
      ],
    );

    $node = Node::create(['type' => 'page', 'title' => 'timezone wiped']);
    $node->set($field_name, [[
      'sub' => ['value' => '', 'timezone' => 'America/New_York'],
      'keep' => 'populated',
    ],
    ]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item, 'A populated sibling column keeps the item.');
    $this->assertNull($item->get('sub')->getValue());
    $this->assertNull($item->get('sub__timezone')->getValue(), 'The orphaned timezone is wiped at save time.');
    $this->assertNull($this->rawValue($item, 'sub__date'));
  }

  /**
   * CONTRACT: with no date, a sole datetime column drops the whole item.
   *
   * The timezone is not consulted by CustomItem::isEmpty(), so a row
   * carrying only a timezone is empty and the item is discarded. The shared
   * testRoundTrip() runner asserts the item is NOT NULL, so these cannot be
   * case rows.
   *
   * @param array<string, mixed>|string|null $value
   *   The sub-field value to assign.
   * @param string $suffix
   *   A short, unique field-name suffix for this case.
   *
   * @dataProvider providerItemDroppedWhenDateIsAbsent
   */
  #[DataProvider('providerItemDroppedWhenDateIsAbsent')]
  public function testItemDroppedWhenDateIsAbsent(array|string|null $value, string $suffix): void {
    $field_name = $this->createCaseField([], $suffix);
    $node = Node::create(['type' => 'page', 'title' => 'dropped ' . $suffix]);
    $node->set($field_name, [['sub' => $value]]);
    $node->save();

    $this->assertNull(
      $this->storedItem($node, $field_name),
      'The item was dropped because the date is empty.',
    );
  }

  /**
   * Data provider for ::testItemDroppedWhenDateIsAbsent().
   *
   * @return array<string, array{array<string, mixed>|string|null, string}>
   *   Test cases.
   */
  public static function providerItemDroppedWhenDateIsAbsent(): array {
    return [
      'an empty-string date' => [
        ['value' => '', 'timezone' => 'America/New_York'],
        'drop_empty',
      ],
      'a NULL date' => [
        ['value' => NULL, 'timezone' => 'America/New_York'],
        'drop_null',
      ],
      // CustomFieldDatetime::setValue() keeps the value only when it is a
      // non-empty string, so a non-string is discarded and takes the item.
      'a non-string date' => [
        ['value' => 12345, 'timezone' => 'America/New_York'],
        'drop_non_string',
      ],
      'a timezone with no date key at all' => [
        ['timezone' => 'America/New_York'],
        'drop_tz_only',
      ],
    ];
  }

  /**
   * A repeated programmatic setValue() must keep the timezone.
   *
   * This is the JSON:API / REST / migrate / custom-code shape: the nested
   * value array with no widget promotion involved. Without
   * CustomItem::setValue()'s extra-property promotion, core's
   * Map::setValue() re-syncs every already-instantiated property object from
   * the incoming values array and the sibling, absent from that array in its
   * flat form, is reset to NULL on the second pass.
   *
   * Carried over from the retired class, which called validate() without
   * asserting the result; this version asserts no violations.
   *
   * @see https://www.drupal.org/project/custom_field/issues/3622588
   */
  public function testProgrammaticNestedValueSurvivesRepeatedSetValue(): void {
    $field_name = $this->createCaseField([], 'repeated');
    $values = ['sub' => ['value' => self::DATE, 'timezone' => 'America/New_York']];

    $node = Node::create(['type' => 'page', 'title' => 'Programmatic']);
    $items = $node->get($field_name);

    $items->setValue([$values]);
    $this->assertNoViolations($node, 'first pass');
    $this->assertSame(
      'America/New_York',
      $this->itemAt($items)->get('sub__timezone')->getValue(),
      'Set on first pass.',
    );

    $items->setValue([$values]);
    $this->assertSame(
      'America/New_York',
      $this->itemAt($items)->get('sub__timezone')->getValue(),
      'Survives second pass.',
    );

    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertSame(self::DATE, $item->get('sub')->getValue());
    $this->assertSame('America/New_York', $item->get('sub__timezone')->getValue());
  }

  /**
   * CHARACTERIZATION: a nested value left un-instantiated breaks __date.
   *
   * CustomItem::setValue() promotes the nested 'timezone' key to the flat
   * sibling column, but the main property keeps the nested ARRAY as its raw
   * value until something instantiates it. The computed property reads the
   * raw value through the magic getter -- before versus after instantiation
   * is the distinction, exactly as for link's options map -- and passes it
   * straight into DrupalDateTime::createFromFormat(), which raises a
   * \TypeError on an array.
   *
   * \TypeError extends \Error, not \Exception, so DateTimeComputed's own
   * catch (\Exception) does not catch it: the same mechanism as link's
   * malformed options blob reaching Url::fromUri() through
   * LinkType::getUrl(), in a different file. Reading the main property
   * through get() first normalizes the raw value and the computed date then
   * works, which is why the assertion order below is deliberate.
   */
  public function testNestedValueRawOnTheItemBreaksTheComputedDate(): void {
    $field_name = $this->createCaseField([], 'raw_nested');
    $node = Node::create(['type' => 'page', 'title' => 'raw nested']);
    $node->set($field_name, [['sub' => ['value' => self::DATE, 'timezone' => 'UTC']]]);
    $node->save();

    $items = $this->reloadedNode($node)->get($field_name);
    $items->setValue([['sub' => ['value' => self::DATE_DST, 'timezone' => 'UTC']]]);
    $item = $this->itemAt($items);

    // Raw first: the main property still holds the nested array.
    $this->assertSame(
      ['value' => self::DATE_DST, 'timezone' => 'UTC'],
      $this->rawValue($item, 'sub'),
    );
    // The flat sibling WAS promoted, so the two layers disagree.
    $this->assertSame('UTC', $this->rawValue($item, 'sub__timezone'));

    try {
      $date = $this->rawValue($item, 'sub__date');
      $this->fail('Expected a \TypeError, got ' . get_debug_type($date) . '.');
    }
    catch (\TypeError $e) {
      $this->assertStringContainsString('must be of type string, array given', $e->getMessage());
    }

    // Instantiating the main property normalizes it, and __date recovers.
    $this->assertSame(self::DATE_DST, $item->get('sub')->getValue());
    $this->assertSame(
      self::DATE_DST,
      $this->computedDate($item)->format(DateTimeTypeInterface::DATETIME_STORAGE_FORMAT),
    );
  }

  /**
   * CHARACTERIZATION: a column with no datetime_type saves, then throws.
   *
   * 'datetime_type' has no default at the column level and the config schema
   * does not require it, so a column definition omitting it saves cleanly --
   * and then every read of the type's property definitions emits a PHP
   * warning, and the first write of a value throws, because the computed
   * property's constructor rejects the NULL setting it was handed.
   * getDatetimeType() meanwhile answers 'datetime' quite happily, so the two
   * readers of the same missing key disagree: one has a default, the other is
   * fatal.
   *
   * The warning is captured with a local error handler rather than left to
   * leak: core's phpunit.xml.dist sets failOnWarning, so an uncaptured
   * warning would turn this test into an error and hide the exception it is
   * really about. Capturing it makes the warning itself an assertion.
   *
   * Latent through supported paths -- the storage settings form writes the
   * key with a default and so does the drush updater -- in the same sense as
   * a missing 'field_settings' key reaching getFieldSettings(), or a falsy
   * link_type silencing a link column's validation: reachable only through
   * hand-built configuration.
   */
  public function testColumnWithoutDatetimeTypeSavesThenThrowsOnWrite(): void {
    $field_name = 'field_dt_no_type';
    $column = ['name' => 'sub', 'type' => 'datetime'];

    // The handler covers the whole window, including the field creation:
    // saving the field config builds the property definitions too, so a
    // handler installed any later would let that warning escape.
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

      // getDatetimeType() copes with the omission, and adds no warning.
      $before = count($warnings);
      $options = $this->typeManager->createOptionsForInstance(['label' => 'Under test'], $column);
      $instance = $this->typeManager->createInstance('datetime', $options['configuration']);
      $this->assertInstanceOf(CustomFieldTypeInterface::class, $instance);
      $this->assertSame(DateTimeType::DATETIME_TYPE_DATETIME, $instance->getDatetimeType());
      $this->assertCount($before, $warnings, 'getDatetimeType() reads the key through a default.');

      // propertyDefinitions() does not: it reads the key unguarded, and still
      // returns all three properties, with a NULL setting on the main one.
      $properties = DateTimeType::propertyDefinitions($column);
      $this->assertSame(['sub', 'sub__date', 'sub__timezone'], array_keys($properties));
      $this->assertNull($properties['sub']->getSetting('datetime_type'));
      $this->assertGreaterThan($before, count($warnings), 'propertyDefinitions() warns.');

      // And merely instantiating the field item is fatal, from the computed
      // property's constructor -- Node::create() initializes field values, so
      // no explicit write is needed to reach it.
      try {
        $node = Node::create(['type' => 'page', 'title' => 'no datetime_type']);
        $node->set($field_name, [['sub' => self::DATE]]);
      }
      catch (\InvalidArgumentException $e) {
        $thrown = $e;
      }
    }
    finally {
      restore_error_handler();
    }

    // Every warning in the window is the same unguarded read; how many times
    // the definitions get rebuilt is core's business, not this type's.
    $this->assertNotEmpty($warnings);
    $this->assertSame(
      ['Undefined array key "datetime_type"'],
      array_values(array_unique($warnings)),
    );

    $this->assertInstanceOf(\InvalidArgumentException::class, $thrown);
    $this->assertSame("The definition's 'datetime_type' key is required.", $thrown->getMessage());
  }

  /**
   * The sample value is a bare storage-format string, per datetime_type.
   *
   * Core's DateTimeItem::generateSampleValue() returns ['value' => ...]; this
   * type returns the string itself. The inherited
   * ::testSampleValueGeneration() proves a generated sample validates and
   * saves; this pins the shape and the format.
   *
   * @param string $datetime_type
   *   The column's datetime_type.
   * @param string $format
   *   The storage format the sample must match.
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

    $sample = DateTimeType::generateSampleValue($instance, 'node');

    $this->assertIsString($sample);

    $parsed = \DateTime::createFromFormat(
      $format,
      $sample,
      new \DateTimeZone(DateTimeTypeInterface::STORAGE_TIMEZONE),
    );
    $this->assertInstanceOf(\DateTime::class, $parsed, 'The sample parses in the declared storage format.');
    $this->assertSame($sample, $parsed->format($format));
  }

  /**
   * Data provider for ::testSampleValueShapeFollowsDatetimeType().
   *
   * @return array<string, array{string, string}>
   *   Test cases.
   */
  public static function providerSampleValueShape(): array {
    return [
      'datetime' => [
        DateTimeType::DATETIME_TYPE_DATETIME,
        DateTimeTypeInterface::DATETIME_STORAGE_FORMAT,
      ],
      'date only' => [
        DateTimeType::DATETIME_TYPE_DATE,
        DateTimeTypeInterface::DATE_STORAGE_FORMAT,
      ],
    ];
  }

  /**
   * The check_empty matrix, with a sibling column to keep the row alive.
   *
   * @param bool $check_empty
   *   The sub-field's check_empty setting.
   * @param string|null $value
   *   The date value to assign.
   * @param bool $expected
   *   Whether the item survives the save.
   *
   * @dataProvider providerCheckEmpty
   */
  #[DataProvider('providerCheckEmpty')]
  public function testCheckEmpty(bool $check_empty, ?string $value, bool $expected): void {
    $field_name = 'field_dt_ce_' . (int) $check_empty . '_' . ($value === NULL ? 'null' : ($value === '' ? 'empty' : 'set'));
    $this->createCustomField(
      $field_name,
      [
        'sub' => ['name' => 'sub', 'type' => 'datetime', 'datetime_type' => DateTimeType::DATETIME_TYPE_DATETIME],
        'keep' => ['name' => 'keep', 'type' => 'string'],
      ],
      [
        'sub' => ['label' => 'Under test', 'check_empty' => $check_empty],
        'keep' => ['label' => 'Populated sibling', 'check_empty' => FALSE],
      ],
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
      'check_empty on, populated value' => [TRUE, self::DATE, TRUE],
      'check_empty off, blank value' => [FALSE, NULL, TRUE],
      'check_empty off, empty string' => [FALSE, '', TRUE],
    ];
  }

  /**
   * The type plugin maps a nested value onto its extra property names.
   *
   * Mirrors the input cases of ::roundTripCases() at the level of the
   * mapping itself, so a change in either is visible in both. This is the
   * only place DateTimeType::extractExtraPropertyValues()'s is_array() and
   * isset() guards are pinned at the mapping level: ExtraPropertyApiTest
   * pins only that the override exists and that its derived names are real
   * properties.
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
      'timezone missing' => [['value' => self::DATE], []],
      'timezone NULL' => [['value' => self::DATE, 'timezone' => NULL], []],
      'timezone empty string' => [
        ['value' => self::DATE, 'timezone' => ''],
        ['sub__timezone' => ''],
      ],
      'timezone valid' => [
        ['value' => self::DATE, 'timezone' => 'America/New_York'],
        ['sub__timezone' => 'America/New_York'],
      ],
      // The guard is isset() on the timezone alone, so it does not care what
      // the main value looks like. preSave() is what clears an orphaned one.
      'value empty string still extracts' => [
        ['value' => '', 'timezone' => 'UTC'],
        ['sub__timezone' => 'UTC'],
      ],
      'value missing still extracts' => [
        ['timezone' => 'UTC'],
        ['sub__timezone' => 'UTC'],
      ],
      'not an array' => [self::DATE, []],
      'empty array' => [[], []],
    ];
  }

  /**
   * Returns a sibling property's raw value, without instantiating it.
   *
   * Reading the magic property, rather than get()->getValue(), is the point
   * wherever this is used: FieldItemBase::__get() returns the property
   * object's value as long as that object already exists, and the raw stored
   * value otherwise, so the two differ until something instantiates the
   * property. The property name is passed as a variable so this stays a
   * single, documented place where that read happens.
   */
  private function rawValue(FieldItemInterface $item, string $property): mixed {
    return $item->{$property};
  }

  /**
   * Assigns a property through the item's magic setter.
   *
   * The counterpart to ::rawValue(), and used for the same reason: the magic
   * setter is a different code path from get()->setValue() -- it reaches
   * CustomItem::onChange() where the property-level call does not -- so the
   * tests that contrast the two must each use the real thing. Passing the
   * property name as a variable keeps both confined to one documented place.
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
   * Reloads a saved node from storage.
   *
   * Wraps the trait's reloadNode() so the id is asserted once rather than
   * cast at every call site: EntityInterface::id() is nullable and a node
   * that has just been saved must have one.
   */
  private function reloadedNode(Node $node): NodeInterface {
    $id = $node->id();
    $this->assertNotNull($id);

    return $this->reloadNode($id);
  }

  /**
   * Returns the instance narrowed to the concrete datetime type class.
   *
   * Narrowed to the CLASS, not to an interface, because getTimestamp() is
   * declared only on DateTimeType: DateTimeTypeInterface carries the three
   * storage constants and no methods at all. That is itself part of why the
   * method has no callers -- see
   * ::testGetTimestampIgnoresTheStorageTimezone().
   */
  private function dateTimeTypeInstance(): DateTimeType {
    $instance = $this->typeInstance();
    $this->assertInstanceOf(DateTimeType::class, $instance);

    return $instance;
  }

  /**
   * Returns the main property's own DrupalDateTime for a stored item.
   *
   * CustomFieldDatetime::getDateTime() builds this independently of the
   * computed '__date' property, and the two do not always agree -- see
   * ::testDateOnlyModeParsesAtMidnight().
   */
  private function mainDateTime(FieldItemInterface $item): DrupalDateTime {
    $date = $this->mainProperty($item)->getDateTime();
    $this->assertInstanceOf(DrupalDateTime::class, $date);

    return $date;
  }

  /**
   * Returns the main property narrowed to the typed-data datetime contract.
   *
   * FieldItemInterface::get() is typed to return TypedDataInterface, which
   * declares neither getDateTime() nor setDateTime(), so this narrowing is a
   * real assertion about the data type the property definition names -- not
   * a tautology.
   */
  private function mainProperty(FieldItemInterface $item): DateTimeInterface {
    $property = $item->get('sub');
    $this->assertInstanceOf(DateTimeInterface::class, $property);

    return $property;
  }

  /**
   * Returns the computed '__date' property for a stored item.
   */
  private function computedDate(FieldItemInterface $item): DrupalDateTime {
    $date = $this->rawValue($item, 'sub__date');
    $this->assertInstanceOf(DrupalDateTime::class, $date);

    return $date;
  }

  /**
   * Builds a configured plugin instance the way production code does.
   *
   * @param array<string, mixed> $settings
   *   Field settings to merge over the defaults.
   *
   * @return \Drupal\custom_field\Plugin\CustomFieldTypeInterface
   *   The configured instance.
   */
  private function typeInstance(array $settings = []): CustomFieldTypeInterface {
    $column = $this->defaultColumn($this->columnName());
    $options = $this->typeManager->createOptionsForInstance(
      [...$this->defaultFieldSettings(), ...$settings],
      $column,
    );
    $instance = $this->typeManager->createInstance(static::typeId(), $options['configuration']);
    $this->assertInstanceOf(CustomFieldTypeInterface::class, $instance);

    return $instance;
  }

}
