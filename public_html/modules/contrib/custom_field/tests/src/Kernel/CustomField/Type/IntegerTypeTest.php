<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Type;

use Drupal\custom_field\Plugin\CustomField\FieldType\IntegerType;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldTypeKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the 'integer' sub-field type.
 *
 * Modelled on core's field type Kernel tests, closest counterpart
 * core/modules/field/tests/src/Kernel/Number/NumberIntegerItemTest.php.
 *
 * IntegerType extends NumericTypeBase, whose getConstraints() derives a
 * Range constraint from NumericTrait's int-size map whenever no field-level
 * min/max is configured, and always ends up on the notInRangeMessage branch
 * once a bound IS configured, because the DB-derived counterpart bound is
 * never NULL for this type.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldType\IntegerType
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(IntegerType::class)]
class IntegerTypeTest extends CustomFieldTypeKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static function typeId(): string {
    return 'integer';
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
      'zero' => [
        'value' => ['sub' => 0],
        'expected' => ['sub' => '0'],
      ],
      'at the signed 32-bit maximum' => [
        'value' => ['sub' => 2147483647],
        'expected' => ['sub' => '2147483647'],
      ],
      'at the signed 32-bit minimum' => [
        'value' => ['sub' => -2147483648],
        'expected' => ['sub' => '-2147483648'],
      ],
      'at the configured minimum' => [
        'settings' => ['min' => 500, 'max' => 600],
        'value' => ['sub' => 500],
        'expected' => ['sub' => '500'],
      ],
      'at the configured maximum' => [
        'settings' => ['min' => 500, 'max' => 600],
        'value' => ['sub' => 600],
        'expected' => ['sub' => '600'],
      ],
      // The next two rows prove the int-size map is actually read for
      // schema()/getConstraints() rather than the normal-size bounds being
      // hardcoded; ::testConstraints() covers the same settings directly.
      'at the unsigned maximum' => [
        'column' => ['unsigned' => TRUE],
        'value' => ['sub' => 4294967295],
        'expected' => ['sub' => '4294967295'],
      ],
      'at the tiny maximum' => [
        'column' => ['size' => 'tiny'],
        'value' => ['sub' => 127],
        'expected' => ['sub' => '127'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function validationCases(): array {
    return [
      'one over the signed 32-bit maximum' => [
        'value' => ['sub' => 2147483648],
        'property' => 'sub',
        'message' => 'This value should be between -2147483648 and 2147483647.',
      ],
      'one under the signed 32-bit minimum' => [
        'value' => ['sub' => -2147483649],
        'property' => 'sub',
        'message' => 'This value should be between -2147483648 and 2147483647.',
      ],
      'one under the configured minimum' => [
        'settings' => ['min' => 500, 'max' => 600],
        'value' => ['sub' => 499],
        'property' => 'sub',
        'message' => 'sub: the value must be between 500 and 600.',
      ],
      'one over the configured maximum' => [
        'settings' => ['min' => 500, 'max' => 600],
        'value' => ['sub' => 601],
        'property' => 'sub',
        'message' => 'sub: the value must be between 500 and 600.',
      ],
      // The config save stores these bounds as 5.0 and 10.0 (see
      // ::testWholeNumberBoundsAreStoredAsFloats()); the message still shows
      // them as whole numbers.
      'below whole-number bounds stored as floats' => [
        'settings' => ['min' => 5, 'max' => 10],
        'value' => ['sub' => 4],
        'property' => 'sub',
        'message' => 'sub: the value must be between 5 and 10.',
      ],
      'above whole-number bounds stored as floats' => [
        'settings' => ['min' => 5, 'max' => 10],
        'value' => ['sub' => 11],
        'property' => 'sub',
        'message' => 'sub: the value must be between 5 and 10.',
      ],
      // A zero bound is falsy, and the other bound is derived from the
      // int-size map. Choosing the message by truthiness once paired
      // minMessage or maxMessage with both bounds, which the Range constraint
      // refuses to build. An empty bound is saved as NULL whether it was
      // entered as NULL or ''.
      // @see https://www.drupal.org/project/custom_field/issues/3623223
      'below a zero minimum with a NULL maximum' => [
        'settings' => ['min' => 0, 'max' => NULL],
        'value' => ['sub' => -1],
        'property' => 'sub',
        'message' => 'sub: the value must be between 0 and 2147483647.',
      ],
      'below a zero minimum with an empty-string maximum' => [
        'settings' => ['min' => 0, 'max' => ''],
        'value' => ['sub' => -1],
        'property' => 'sub',
        'message' => 'sub: the value must be between 0 and 2147483647.',
      ],
      'above a zero maximum with a NULL minimum' => [
        'settings' => ['min' => NULL, 'max' => 0],
        'value' => ['sub' => 1],
        'property' => 'sub',
        'message' => 'sub: the value must be between -2147483648 and 0.',
      ],
      'above a zero maximum with an empty-string minimum' => [
        'settings' => ['min' => '', 'max' => 0],
        'value' => ['sub' => 1],
        'property' => 'sub',
        'message' => 'sub: the value must be between -2147483648 and 0.',
      ],
    ];
  }

  /**
   * The schema is an int column sized and signed from the column settings.
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
    $this->assertSame($expected, IntegerType::schema(['name' => 'sub'] + $column));
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
        ['sub' => ['type' => 'int', 'size' => 'normal', 'unsigned' => FALSE]],
      ],
      'tiny unsigned' => [
        ['size' => 'tiny', 'unsigned' => TRUE],
        ['sub' => ['type' => 'int', 'size' => 'tiny', 'unsigned' => TRUE]],
      ],
      'big signed' => [
        ['size' => 'big'],
        ['sub' => ['type' => 'int', 'size' => 'big', 'unsigned' => FALSE]],
      ],
    ];
  }

  /**
   * The property definition is a single, optional integer.
   */
  public function testPropertyDefinitions(): void {
    $properties = IntegerType::propertyDefinitions(['name' => 'sub']);

    $this->assertSame(['sub'], array_keys($properties));
    $this->assertSame('integer', $properties['sub']->getDataType());
    $this->assertFalse($properties['sub']->isRequired());
  }

  /**
   * The declared default field settings, including integer's allowed_values.
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
    ], IntegerType::defaultFieldSettings());
  }

  /**
   * Integer always gets a Range constraint, whose bounds and message vary.
   *
   * NumericTypeBase::getConstraints() reads $settings['type'] unguarded and
   * calls getFieldSettings(), which has no null-coalesce on the
   * 'field_settings' key (CustomFieldTypeBase::getFieldSettings()). Both
   * 'type' and 'field_settings' must therefore be present on the instance,
   * unlike StringType's bare createInstance() call, or a TypeError follows.
   *
   * @param array<string, mixed> $column
   *   Column settings to merge over the defaults.
   * @param array<string, mixed> $fieldSettings
   *   Field settings to merge over the plugin's own defaults.
   * @param int|float $expectedMin
   *   The expected Range minimum.
   * @param int|float $expectedMax
   *   The expected Range maximum.
   * @param string|null $expectedMessageKey
   *   The expected message key, or NULL if no message override is present.
   * @param string|null $expectedMessage
   *   The expected message text, tags stripped, or NULL.
   *
   * @dataProvider providerConstraints
   */
  #[DataProvider('providerConstraints')]
  public function testConstraints(array $column, array $fieldSettings, int|float $expectedMin, int|float $expectedMax, ?string $expectedMessageKey, ?string $expectedMessage): void {
    $instance = $this->integerInstance($column, $fieldSettings);
    $constraints = $instance->getConstraints();

    $this->assertArrayHasKey('Range', $constraints);
    $this->assertSame($expectedMin, $constraints['Range']['min']);
    $this->assertSame($expectedMax, $constraints['Range']['max']);

    if ($expectedMessageKey === NULL) {
      $this->assertCount(2, $constraints['Range'], 'No message override is present when no bound is explicitly configured.');
      return;
    }

    $this->assertArrayHasKey($expectedMessageKey, $constraints['Range']);
    $this->assertSame($expectedMessage, strip_tags((string) $constraints['Range'][$expectedMessageKey]));
  }

  /**
   * Data provider for ::testConstraints().
   *
   * The 'big' row's minimum is a float, not an int: NumericTrait's size map
   * writes the literal -9223372036854775808, which overflows PHP_INT_MAX by
   * one and so PHP parses it as a float, not an int (getDefaultMinValue()'s
   * int|float return type accounts for exactly this). CHARACTERIZATION, not
   * contract: this pins the current, slightly inconsistent behavior (the
   * 'big' unsigned maximum is a proper int via the PHP_INT_MAX constant, but
   * the signed minimum is not); a fix normalizing both to int would change
   * this expectation, and that change is the intended signal.
   *
   * @return array<string, array{array<string, mixed>, array<string, mixed>, int|float, int|float, string|null, string|null}>
   *   Test cases.
   */
  public static function providerConstraints(): array {
    return [
      'no bounds falls back to the signed 32-bit range' => [
        [], [], -2147483648, 2147483647, NULL, NULL,
      ],
      'min only still derives a max, so the message is notInRangeMessage' => [
        [], ['min' => 500], 500, 2147483647, 'notInRangeMessage', 'sub: the value must be between 500 and 2147483647.',
      ],
      'max only still derives a min, so the message is notInRangeMessage' => [
        [], ['max' => 600], -2147483648, 600, 'notInRangeMessage', 'sub: the value must be between -2147483648 and 600.',
      ],
      'both configured' => [
        [], ['min' => 500, 'max' => 600], 500, 600, 'notInRangeMessage', 'sub: the value must be between 500 and 600.',
      ],
      // A zero bound is falsy. Choosing the message by truthiness once paired
      // minMessage with two bounds, which the Range constraint refuses to
      // build. Zero is stored as 0.0, because the config schema types min
      // and max as float.
      // @see https://www.drupal.org/project/custom_field/issues/3623223
      'a zero minimum still derives a max, so the message is notInRangeMessage' => [
        [], ['min' => 0.0], 0.0, 2147483647, 'notInRangeMessage', 'sub: the value must be between 0 and 2147483647.',
      ],
      'unsigned uses the 0-based normal-size range' => [
        ['unsigned' => TRUE], [], 0, 4294967295, NULL, NULL,
      ],
      'tiny uses the 1-byte signed range' => [
        ['size' => 'tiny'], [], -128, 127, NULL, NULL,
      ],
      'big uses the (float, due to overflow) 64-bit signed range' => [
        ['size' => 'big'], [], (float) PHP_INT_MIN, PHP_INT_MAX, NULL, NULL,
      ],
    ];
  }

  /**
   * The check_empty setting drops an item when a sibling column is populated.
   *
   * Mirrors StringTypeTest::testCheckEmpty(), but the blank value is NULL
   * rather than '': CustomItem::isEmpty() only treats '' or NULL as blank,
   * and an integer property never round-trips through '' (see the "zero"
   * round-trip case, which is deliberately NOT blank).
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
        'sub' => ['name' => 'sub', 'type' => 'integer', 'size' => 'normal', 'unsigned' => FALSE],
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
      'check_empty on, populated value' => [TRUE, 42, TRUE],
      'check_empty off, blank value' => [FALSE, NULL, TRUE],
    ];
  }

  /**
   * Prefix and suffix are display-only and do not touch the stored value.
   */
  public function testPrefixSuffixNotStored(): void {
    $field_name = $this->createCaseField([
      'settings' => ['prefix' => '$ ', 'suffix' => ' pts'],
    ], 'prefix_suffix');

    $node = Node::create(['type' => 'page', 'title' => 'prefix suffix']);
    $node->set($field_name, [['sub' => 42]]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertSame('42', $item->get('sub')->getValue());
  }

  /**
   * Generated sample values stay within the configured min/max.
   */
  public function testSampleValueRespectsBounds(): void {
    $field_name = $this->createCaseField([
      'settings' => ['min' => 10, 'max' => 20],
    ], 'bounds');

    $field = $this->container->get('entity_field.manager')
      ->getFieldDefinitions('node', 'page')[$field_name];
    $instance = $this->getCustomFieldItems($field)['sub'];

    for ($i = 0; $i < 20; $i++) {
      $value = IntegerType::generateSampleValue($instance, 'node');
      $this->assertGreaterThanOrEqual(10, $value);
      $this->assertLessThanOrEqual(20, $value);
    }
  }

  /**
   * Generated sample values round fractional bounds inward.
   *
   * An integer column can carry a fractional bound set programmatically, since
   * the config schema types min and max as float. Truncating min would produce
   * samples below it, so min is rounded up and max down.
   */
  public function testSampleValueRoundsFractionalBoundsInward(): void {
    $field_name = $this->createCaseField([
      'settings' => ['min' => 5.5, 'max' => 9.5],
    ], 'fractional_bounds');

    $field = $this->container->get('entity_field.manager')
      ->getFieldDefinitions('node', 'page')[$field_name];
    $instance = $this->getCustomFieldItems($field)['sub'];

    for ($i = 0; $i < 200; $i++) {
      $value = IntegerType::generateSampleValue($instance, 'node');
      $this->assertGreaterThanOrEqual(6, $value);
      $this->assertLessThanOrEqual(9, $value);
    }
  }

  /**
   * Whole-number bounds are stored in the field config as floats.
   *
   * Every sub-field type shares one config schema mapping, which types min and
   * max as float for the sake of float and decimal columns. The bounds still
   * apply as before: see the validation cases.
   *
   * @see https://www.drupal.org/project/custom_field/issues/3628888
   */
  public function testWholeNumberBoundsAreStoredAsFloats(): void {
    $field_name = $this->createCaseField(['settings' => ['min' => 5, 'max' => 10]], 'whole');
    $settings = $this->storedColumnSettings($field_name);

    $this->assertSame(5.0, $settings['min']);
    $this->assertSame(10.0, $settings['max']);
  }

  /**
   * Sample generation draws from allowed_values when they are configured.
   */
  public function testSampleValueUsesAllowedValues(): void {
    $field_name = $this->createCaseField([
      'settings' => [
        'allowed_values' => [
          ['key' => 5, 'label' => 'Five'],
          ['key' => 10, 'label' => 'Ten'],
        ],
      ],
    ], 'allowed');

    $field = $this->container->get('entity_field.manager')
      ->getFieldDefinitions('node', 'page')[$field_name];
    $instance = $this->getCustomFieldItems($field)['sub'];

    for ($i = 0; $i < 10; $i++) {
      $this->assertContains(
        IntegerType::generateSampleValue($instance, 'node'),
        [5, 10],
        'The generated sample is one of the allowed values.',
      );
    }
  }

  /**
   * Returns a configured integer type instance.
   *
   * @param array<string, mixed> $column
   *   Column settings to merge over the defaults.
   * @param array<string, mixed> $fieldSettings
   *   Field settings to pass through (merged with the plugin's own defaults
   *   by getFieldSettings()).
   *
   * @return \Drupal\custom_field\Plugin\CustomField\FieldType\IntegerType
   *   The configured instance.
   */
  protected function integerInstance(array $column, array $fieldSettings): IntegerType {
    $instance = $this->typeManager->createInstance('integer', [
      'settings' => [
        'name' => 'sub',
        'type' => 'integer',
        'size' => 'normal',
        'unsigned' => FALSE,
        ...$column,
        'field_settings' => $fieldSettings,
      ],
    ]);
    $this->assertInstanceOf(CustomFieldTypeInterface::class, $instance);
    $this->assertInstanceOf(IntegerType::class, $instance);

    return $instance;
  }

}
