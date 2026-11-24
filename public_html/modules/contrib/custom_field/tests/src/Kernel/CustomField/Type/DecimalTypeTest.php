<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Type;

use Drupal\custom_field\Plugin\CustomField\FieldType\DecimalType;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldTypeKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the 'decimal' sub-field type.
 *
 * Modelled on core's field type Kernel tests, closest counterpart
 * core/modules/field/tests/src/Kernel/Number/NumberDecimalItemTest.php.
 *
 * DecimalType does not use OptionsTrait, so it has no allowed_values, unlike
 * integer and float. CustomItem::preSave() has a decimal-only branch that
 * rounds the value to the column's configured scale before it is stored.
 * getConstraints() (inherited from NumericTypeBase) derives its Range from
 * NumericTrait's int-size map exactly as integer does — ignoring
 * precision/scale entirely, which looks like a bug (a DECIMAL(10,2) column
 * can hold at most 99999999.99, but the Range admits up to 2147483647) and
 * is pinned as a characterization by ::testConstraints() below rather than
 * silently relied upon.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldType\DecimalType
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(DecimalType::class)]
class DecimalTypeTest extends CustomFieldTypeKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static function typeId(): string {
    return 'decimal';
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultColumn(string $name): array {
    return parent::defaultColumn($name) + [
      'precision' => 10,
      'scale' => 2,
      'unsigned' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function roundTripCases(): array {
    return [
      'a float at the configured scale' => [
        'value' => ['sub' => 3.14],
        'expected' => ['sub' => '3.14'],
      ],
      'a numeric string is accepted' => [
        'value' => ['sub' => '18.2'],
        'expected' => ['sub' => '18.20'],
      ],
      'at the precision limit' => [
        'value' => ['sub' => 99999999.99],
        'expected' => ['sub' => '99999999.99'],
      ],
      'rounded up to the configured scale' => [
        // Pins CustomItem::preSave()'s decimal-only rounding branch:
        // round($value, $scale).
        'value' => ['sub' => 3.999],
        'expected' => ['sub' => '4.00'],
      ],
      'rounded away from zero' => [
        'value' => ['sub' => -1.618],
        'expected' => ['sub' => '-1.62'],
      ],
      'at a fractional minimum' => [
        'settings' => ['min' => 0.25, 'max' => 99.99],
        'value' => ['sub' => '0.25'],
        'expected' => ['sub' => '0.25'],
      ],
      'at a fractional maximum' => [
        'settings' => ['min' => 0.25, 'max' => 99.99],
        'value' => ['sub' => '99.99'],
        'expected' => ['sub' => '99.99'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function validationCases(): array {
    return [
      // CHARACTERIZATION, not contract. The bound being enforced here comes
      // from the int-size map, not from precision/scale — see the class
      // docblock. It is asserted because the Range constraint decimal carries
      // is real and worth covering; the specific numbers are expected to change
      // if the bounds are ever derived from the column's actual capacity, and
      // that change is the intended signal.
      // No custom message is configured (neither `min` nor `max` is set in
      // field settings), so Symfony's default notInRangeMessage is used rather
      // than NumericTypeBase's '%name: ...' wording.
      'above the maximum the int-size map imposes' => [
        'value' => ['sub' => '2147483648'],
        'property' => 'sub',
        'message' => 'This value should be between -2147483648 and 2147483647.',
      ],
      'below a fractional minimum' => [
        'settings' => ['min' => 0.25, 'max' => 99.99],
        'value' => ['sub' => '0.24'],
        'property' => 'sub',
        'message' => 'sub: the value must be between 0.25 and 99.99.',
      ],
      'above a fractional maximum' => [
        'settings' => ['min' => 0.25, 'max' => 99.99],
        'value' => ['sub' => '100.00'],
        'property' => 'sub',
        'message' => 'sub: the value must be between 0.25 and 99.99.',
      ],
      // A zero bound is falsy, and the other bound is derived from the
      // int-size map. Choosing the message by truthiness once paired
      // minMessage or maxMessage with both bounds, which the Range constraint
      // refuses to build. An empty bound is saved as NULL whether it was
      // entered as NULL or ''.
      // @see https://www.drupal.org/project/custom_field/issues/3623223
      'below a zero minimum with a NULL maximum' => [
        'settings' => ['min' => 0, 'max' => NULL],
        'value' => ['sub' => '-1'],
        'property' => 'sub',
        'message' => 'sub: the value must be between 0 and 2147483647.',
      ],
      'below a zero minimum with an empty-string maximum' => [
        'settings' => ['min' => 0, 'max' => ''],
        'value' => ['sub' => '-1'],
        'property' => 'sub',
        'message' => 'sub: the value must be between 0 and 2147483647.',
      ],
      'above a zero maximum with a NULL minimum' => [
        'settings' => ['min' => NULL, 'max' => 0],
        'value' => ['sub' => '1'],
        'property' => 'sub',
        'message' => 'sub: the value must be between -2147483648 and 0.',
      ],
      'above a zero maximum with an empty-string minimum' => [
        'settings' => ['min' => '', 'max' => 0],
        'value' => ['sub' => '1'],
        'property' => 'sub',
        'message' => 'sub: the value must be between -2147483648 and 0.',
      ],
    ];
  }

  /**
   * Fractional bounds are stored in the field config as entered.
   *
   * The config schema types min and max as float. With an integer type, the
   * config save cast them to whole numbers, so the field enforced a different
   * range than the one entered.
   *
   * @see https://www.drupal.org/project/custom_field/issues/3628888
   */
  public function testFractionalBoundsAreStoredAsEntered(): void {
    $field_name = $this->createCaseField(['settings' => ['min' => 0.25, 'max' => 99.99]], 'fractional');
    $settings = $this->storedColumnSettings($field_name);

    $this->assertSame(0.25, $settings['min']);
    $this->assertSame(99.99, $settings['max']);
  }

  /**
   * A non-numeric value fails two independent constraints at once.
   *
   * CHARACTERIZATION, not contract. Answers the open
   * `// Why does decimal '20-40' test trigger 2 violations?` question that
   * CustomFieldItemTest carried unanswered: the decimal property always
   * carries both a PrimitiveTypeConstraint (DecimalData's format pattern
   * rejects '20-40') and the Range constraint every NumericTypeBase instance
   * gets (RangeValidator emits its own "not a valid number" message for a
   * non-numeric value). The order of the two violations is NOT VERIFIED, so
   * this asserts the set rather than trusting violations[0] as the deleted
   * test plugin did.
   */
  public function testNonNumericStringProducesTwoViolations(): void {
    $field_name = $this->createCaseField([], 'non_numeric');

    $node = Node::create(['type' => 'page', 'title' => 'two violations']);
    $node->set($field_name, [['sub' => '20-40']]);

    $violations = $node->validate();
    $this->assertCount(2, $violations, (string) $violations);

    $messages = [];
    foreach ($violations as $violation) {
      $messages[] = strip_tags((string) $violation->getMessage());
    }

    $this->assertEqualsCanonicalizing([
      'This value should be of the correct primitive type.',
      'This value should be a valid number.',
    ], $messages);
  }

  /**
   * The schema is a numeric column, precision/scale from the column settings.
   *
   * @param array<string, mixed> $column
   *   Column settings to merge over the name.
   * @param array<string, mixed> $expected
   *   The expected schema.
   *
   * @dataProvider providerSchema
   */
  #[DataProvider('providerSchema')]
  public function testSchema(array $column, array $expected): void {
    $this->assertSame($expected, DecimalType::schema(['name' => 'sub'] + $column));
  }

  /**
   * Data provider for ::testSchema().
   *
   * @return array<string, array{array<string, mixed>, array<string, mixed>}>
   *   Test cases.
   */
  public static function providerSchema(): array {
    return [
      'defaults to precision 10, scale 2' => [
        [],
        ['sub' => ['type' => 'numeric', 'precision' => 10, 'scale' => 2, 'unsigned' => FALSE]],
      ],
      'explicit precision and scale, unsigned' => [
        ['precision' => 12, 'scale' => 4, 'unsigned' => TRUE],
        ['sub' => ['type' => 'numeric', 'precision' => 12, 'scale' => 4, 'unsigned' => TRUE]],
      ],
      'zero scale' => [
        ['precision' => 10, 'scale' => 0],
        ['sub' => ['type' => 'numeric', 'precision' => 10, 'scale' => 0, 'unsigned' => FALSE]],
      ],
    ];
  }

  /**
   * The property definition is a single, optional decimal.
   */
  public function testPropertyDefinitions(): void {
    $properties = DecimalType::propertyDefinitions(['name' => 'sub']);

    $this->assertSame(['sub'], array_keys($properties));
    $this->assertSame('decimal', $properties['sub']->getDataType());
    $this->assertFalse($properties['sub']->isRequired());
  }

  /**
   * The declared default field settings. Note: no allowed_values.
   *
   * Unlike IntegerType and FloatType, DecimalType does not use OptionsTrait,
   * so it never adds an 'allowed_values' key.
   */
  public function testDefaultFieldSettings(): void {
    $this->assertSame([
      'min' => '',
      'max' => '',
      'prefix' => '',
      'suffix' => '',
      'label' => '',
      'required' => FALSE,
      'translatable' => FALSE,
      'description' => '',
      'description_display' => 'after',
      'check_empty' => FALSE,
    ], DecimalType::defaultFieldSettings());
  }

  /**
   * Decimal's Range bounds come from the int-size map, not precision/scale.
   *
   * CHARACTERIZATION, not contract. A DECIMAL(10,2) column can only hold
   * values up to 99999999.99, yet getConstraints() admits up to
   * 2147483647 regardless of precision/scale — the guard exists "to prevent
   * a PDO exception" but is simultaneously too loose for a narrow column and
   * too tight for a wide one. This pins the current bounds; whether writing a
   * value past the precision limit but inside this Range actually causes a
   * database error is NOT VERIFIED here (probing that is a separate concern
   * from pinning what getConstraints() returns today). A fix deriving the
   * bounds from precision/scale would change these numbers, and that change
   * is the intended signal.
   *
   * @param array<string, mixed> $column
   *   Column settings to merge over the defaults.
   * @param int|float $expectedMin
   *   The expected Range minimum.
   * @param int $expectedMax
   *   The expected Range maximum.
   *
   * @dataProvider providerConstraints
   */
  #[DataProvider('providerConstraints')]
  public function testConstraints(array $column, int|float $expectedMin, int $expectedMax): void {
    $instance = $this->decimalInstance($column, []);
    $constraints = $instance->getConstraints();

    $this->assertArrayHasKey('Range', $constraints);
    $this->assertSame($expectedMin, $constraints['Range']['min']);
    $this->assertSame($expectedMax, $constraints['Range']['max']);
    $this->assertCount(2, $constraints['Range'], 'No message override is present when no bound is explicitly configured.');
  }

  /**
   * Data provider for ::testConstraints().
   *
   * @return array<string, array{array<string, mixed>, int|float, int}>
   *   Test cases.
   */
  public static function providerConstraints(): array {
    return [
      'a wide precision does not widen the Range' => [
        ['precision' => 20, 'scale' => 4], -2147483648, 2147483647,
      ],
      'unsigned still uses the int-size map, not the precision-scaled bound' => [
        ['precision' => 20, 'scale' => 4, 'unsigned' => TRUE], 0, 4294967295,
      ],
    ];
  }

  /**
   * PreSave rounds to the column's configured scale, not a fixed one.
   *
   * @param int $scale
   *   The column 'scale' setting under test.
   * @param string $value
   *   The value to store.
   * @param string $expected
   *   The expected stored value.
   *
   * @dataProvider providerPreSaveRoundsToScale
   */
  #[DataProvider('providerPreSaveRoundsToScale')]
  public function testPreSaveRoundsToScale(int $scale, string $value, string $expected): void {
    $field_name = $this->createCaseField(['column' => ['scale' => $scale]], 'scale_' . $scale);

    $node = Node::create(['type' => 'page', 'title' => 'scale ' . $scale]);
    $node->set($field_name, [['sub' => $value]]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertSame($expected, $item->get('sub')->getValue());
  }

  /**
   * Data provider for ::testPreSaveRoundsToScale().
   *
   * @return array<string, array{int, string, string}>
   *   Test cases.
   */
  public static function providerPreSaveRoundsToScale(): array {
    return [
      'scale 0 rounds to a whole number' => [0, '3.6', '4'],
      'scale 4 keeps up to four digits' => [4, '3.14159', '3.1416'],
    ];
  }

  /**
   * Generated sample values stay within the precision/scale-derived bounds.
   *
   * @param int $precision
   *   The column 'precision' setting under test.
   * @param int $scale
   *   The column 'scale' setting under test.
   *
   * @dataProvider providerSampleValueWithinPrecision
   */
  #[DataProvider('providerSampleValueWithinPrecision')]
  public function testSampleValueWithinPrecision(int $precision, int $scale): void {
    $instance = $this->decimalInstance(['precision' => $precision, 'scale' => $scale], []);
    $bound = (10 ** ($precision - $scale)) - 1;

    for ($i = 0; $i < 20; $i++) {
      $value = DecimalType::generateSampleValue($instance, 'node');
      $this->assertGreaterThanOrEqual(-$bound, $value);
      $this->assertLessThanOrEqual($bound, $value);
    }
  }

  /**
   * Data provider for ::testSampleValueWithinPrecision().
   *
   * @return array<string, array{int, int}>
   *   Test cases.
   */
  public static function providerSampleValueWithinPrecision(): array {
    return [
      'default precision and scale' => [10, 2],
      'narrow precision' => [4, 2],
    ];
  }

  /**
   * Returns a configured decimal type instance.
   *
   * @param array<string, mixed> $column
   *   Column settings to merge over the defaults.
   * @param array<string, mixed> $fieldSettings
   *   Field settings to pass through (merged with the plugin's own defaults
   *   by getFieldSettings()).
   *
   * @return \Drupal\custom_field\Plugin\CustomField\FieldType\DecimalType
   *   The configured instance.
   */
  protected function decimalInstance(array $column, array $fieldSettings): DecimalType {
    $instance = $this->typeManager->createInstance('decimal', [
      'settings' => [
        'name' => 'sub',
        'type' => 'decimal',
        'precision' => 10,
        'scale' => 2,
        'unsigned' => FALSE,
        ...$column,
        'field_settings' => $fieldSettings,
      ],
    ]);
    $this->assertInstanceOf(CustomFieldTypeInterface::class, $instance);
    $this->assertInstanceOf(DecimalType::class, $instance);

    return $instance;
  }

}
