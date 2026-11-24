<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Type;

use Drupal\custom_field\Plugin\CustomField\FieldType\FloatType;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldTypeKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the 'float' sub-field type.
 *
 * Modelled on core's field type Kernel tests, closest counterpart
 * core/modules/field/tests/src/Kernel/Number/NumberFloatItemTest.php.
 *
 * Unlike integer and decimal, NumericTypeBase::getConstraints() gives float
 * no DB-derived Range bounds at all ($type !== 'float' guard), so a Range
 * constraint exists only when a field-level min or max is configured, and
 * float is the only one of the three where minMessage/maxMessage are
 * actually reachable, because the DB-derived counterpart bound that forces
 * the other two types onto notInRangeMessage does not exist here.
 *
 * ::testConstraints() below asserts getConstraints() on a manager-created
 * instance directly rather than through a saved field.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldType\FloatType
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(FloatType::class)]
class FloatTypeTest extends CustomFieldTypeKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static function typeId(): string {
    return 'float';
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultColumn(string $name): array {
    return parent::defaultColumn($name) + [
      'size' => 'normal',
      'unsigned' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function roundTripCases(): array {
    return [
      'a simple decimal' => [
        'value' => ['sub' => 3.14],
        'expected' => ['sub' => '3.14'],
      ],
      // The at-limit partners for ::validationCases()' two out-of-range rows.
      // A bound that is exactly met must be accepted, which is the half of a
      // Range constraint that a violation-only case set never demonstrates.
      // Fractional bounds are covered against a plugin instance directly in
      // ::testConstraints().
      'at the configured minimum' => [
        'settings' => ['min' => 10],
        'value' => ['sub' => 10],
        'expected' => ['sub' => '10'],
      ],
      'at the configured maximum' => [
        'settings' => ['max' => 20],
        'value' => ['sub' => 20],
        'expected' => ['sub' => '20'],
      ],
      'at a fractional minimum' => [
        'settings' => ['min' => 0.5],
        'value' => ['sub' => 0.5],
        'expected' => ['sub' => '0.5'],
      ],
      'at a fractional maximum' => [
        'settings' => ['max' => 9.75],
        'value' => ['sub' => 9.75],
        'expected' => ['sub' => '9.75'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * NEW coverage: TestFloat, the deleted test plugin, carried zero violation
   * cases, so float's Range validation was entirely unexercised before this
   * class. The fractional-bound message reachability is covered separately
   * in ::testConstraints().
   */
  protected function validationCases(): array {
    return [
      'below the configured minimum' => [
        'settings' => ['min' => 10],
        'value' => ['sub' => 5],
        'property' => 'sub',
        'message' => 'sub: the value may be no less than 10.',
      ],
      'above the configured maximum' => [
        'settings' => ['max' => 20],
        'value' => ['sub' => 25],
        'property' => 'sub',
        'message' => 'sub: the value may be no greater than 20.',
      ],
      'below a fractional minimum' => [
        'settings' => ['min' => 0.5],
        'value' => ['sub' => 0.4],
        'property' => 'sub',
        'message' => 'sub: the value may be no less than 0.5.',
      ],
      'above a fractional maximum' => [
        'settings' => ['max' => 9.75],
        'value' => ['sub' => 9.8],
        'property' => 'sub',
        'message' => 'sub: the value may be no greater than 9.75.',
      ],
      // A zero bound is falsy. Float derives no counterpart bound, so these
      // use minMessage and maxMessage. An empty bound is saved as NULL whether
      // it was entered as NULL or ''.
      // @see https://www.drupal.org/project/custom_field/issues/3623223
      'below a zero minimum with a NULL maximum' => [
        'settings' => ['min' => 0, 'max' => NULL],
        'value' => ['sub' => -1],
        'property' => 'sub',
        'message' => 'sub: the value may be no less than 0.',
      ],
      'below a zero minimum with an empty-string maximum' => [
        'settings' => ['min' => 0, 'max' => ''],
        'value' => ['sub' => -1],
        'property' => 'sub',
        'message' => 'sub: the value may be no less than 0.',
      ],
      'above a zero maximum with a NULL minimum' => [
        'settings' => ['min' => NULL, 'max' => 0],
        'value' => ['sub' => 1],
        'property' => 'sub',
        'message' => 'sub: the value may be no greater than 0.',
      ],
      'above a zero maximum with an empty-string minimum' => [
        'settings' => ['min' => '', 'max' => 0],
        'value' => ['sub' => 1],
        'property' => 'sub',
        'message' => 'sub: the value may be no greater than 0.',
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
    $field_name = $this->createCaseField(['settings' => ['min' => 0.5, 'max' => 9.75]], 'fractional');
    $settings = $this->storedColumnSettings($field_name);

    $this->assertSame(0.5, $settings['min']);
    $this->assertSame(9.75, $settings['max']);
  }

  /**
   * The schema is a float column, signed/sized from the column settings.
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
    $this->assertSame($expected, FloatType::schema(['name' => 'sub'] + $column));
  }

  /**
   * Data provider for ::testSchema().
   *
   * @return array<string, array{array<string, mixed>, array<string, mixed>}>
   *   Test cases.
   */
  public static function providerSchema(): array {
    return [
      'defaults to normal size, signed' => [
        [],
        ['sub' => ['type' => 'float', 'unsigned' => FALSE, 'size' => 'normal']],
      ],
      'big unsigned' => [
        ['size' => 'big', 'unsigned' => TRUE],
        ['sub' => ['type' => 'float', 'unsigned' => TRUE, 'size' => 'big']],
      ],
    ];
  }

  /**
   * The property definition is a single, optional float.
   */
  public function testPropertyDefinitions(): void {
    $properties = FloatType::propertyDefinitions(['name' => 'sub']);

    $this->assertSame(['sub'], array_keys($properties));
    $this->assertSame('float', $properties['sub']->getDataType());
    $this->assertFalse($properties['sub']->isRequired());
  }

  /**
   * The declared default field settings, including float's allowed_values.
   */
  public function testDefaultFieldSettings(): void {
    $this->assertSame([
      'allowed_values' => [],
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
    ], FloatType::defaultFieldSettings());
  }

  /**
   * Float is the only type where minMessage and maxMessage are reachable.
   *
   * For integer and decimal, the DB-derived counterpart bound is never NULL,
   * so NumericTypeBase::getConstraints() always falls onto notInRangeMessage
   * once any bound is set. float skips the DB-derived bounds entirely
   * ($type !== 'float'), so configuring only a min (or only a max) leaves
   * the other side NULL and the min/max-specific message is used instead.
   * This asserts getConstraints() on a manager-created instance rather than
   * a saved field.
   *
   * @param array<string, mixed> $fieldSettings
   *   Field settings to merge over the plugin's own defaults.
   * @param string $expectedMessageKey
   *   The expected message key.
   * @param string $expectedMessage
   *   The expected message text, tags stripped.
   *
   * @dataProvider providerConstraints
   */
  #[DataProvider('providerConstraints')]
  public function testConstraints(array $fieldSettings, string $expectedMessageKey, string $expectedMessage): void {
    $instance = $this->floatInstance([], $fieldSettings);
    $constraints = $instance->getConstraints();

    $this->assertArrayHasKey('Range', $constraints);
    $this->assertArrayHasKey($expectedMessageKey, $constraints['Range']);
    $this->assertSame($expectedMessage, strip_tags((string) $constraints['Range'][$expectedMessageKey]));
  }

  /**
   * Data provider for ::testConstraints().
   *
   * @return array<string, array{array<string, mixed>, string, string}>
   *   Test cases.
   */
  public static function providerConstraints(): array {
    return [
      'a fractional minimum only reaches minMessage' => [
        ['min' => 0.003],
        'minMessage',
        'sub: the value may be no less than 0.003.',
      ],
      'a fractional maximum only reaches maxMessage' => [
        ['max' => 2.5],
        'maxMessage',
        'sub: the value may be no greater than 2.5.',
      ],
    ];
  }

  /**
   * With no min/max configured, float gets no Range constraint at all.
   *
   * CHARACTERIZATION, not contract. Unlike integer and decimal, float never
   * falls back to a DB-derived Range: NumericTypeBase::getConstraints()
   * explicitly excludes it ($type !== 'float'), so a float column with no min
   * or max is entirely unconstrained at the validation layer — the opposite
   * side of the same guard that makes integer and decimal always constrained
   * regardless of configuration. This pins current behavior; closing that
   * gap would change this expectation, and that change is the intended
   * signal.
   */
  public function testNoConstraintsWithoutBounds(): void {
    $instance = $this->floatInstance([], []);

    $this->assertSame([], $instance->getConstraints());
  }

  /**
   * Generated sample values stay within the size-derived scale and range.
   *
   * FloatType::generateSampleValue() branches on 'size': only 'big' maps to
   * a double-precision column, so it gets a wider range and more decimal
   * places; every other size shares the same narrower bounds.
   *
   * @param string $size
   *   The column 'size' setting under test.
   * @param float $expectedMin
   *   The expected lower bound.
   * @param float $expectedMax
   *   The expected upper bound.
   *
   * @dataProvider providerSampleValueSizeBranches
   */
  #[DataProvider('providerSampleValueSizeBranches')]
  public function testSampleValueSizeBranches(string $size, float $expectedMin, float $expectedMax): void {
    $instance = $this->floatInstance(['size' => $size], []);

    for ($i = 0; $i < 20; $i++) {
      $value = FloatType::generateSampleValue($instance, 'node');
      $this->assertGreaterThanOrEqual($expectedMin, $value);
      $this->assertLessThanOrEqual($expectedMax, $value);
    }
  }

  /**
   * Data provider for ::testSampleValueSizeBranches().
   *
   * @return array<string, array{string, float, float}>
   *   Test cases.
   */
  public static function providerSampleValueSizeBranches(): array {
    return [
      'normal size bounds to +/-9999' => ['normal', -9999.0, 9999.0],
      'big size bounds to +/-1000000' => ['big', -1000000.0, 1000000.0],
    ];
  }

  /**
   * Returns a configured float type instance.
   *
   * @param array<string, mixed> $column
   *   Column settings to merge over the defaults.
   * @param array<string, mixed> $fieldSettings
   *   Field settings to pass through (merged with the plugin's own defaults
   *   by getFieldSettings()).
   *
   * @return \Drupal\custom_field\Plugin\CustomField\FieldType\FloatType
   *   The configured instance.
   */
  protected function floatInstance(array $column, array $fieldSettings): FloatType {
    $instance = $this->typeManager->createInstance('float', [
      'settings' => [
        'name' => 'sub',
        'type' => 'float',
        'size' => 'normal',
        'unsigned' => FALSE,
        ...$column,
        'field_settings' => $fieldSettings,
      ],
    ]);
    $this->assertInstanceOf(CustomFieldTypeInterface::class, $instance);
    $this->assertInstanceOf(FloatType::class, $instance);

    return $instance;
  }

}
