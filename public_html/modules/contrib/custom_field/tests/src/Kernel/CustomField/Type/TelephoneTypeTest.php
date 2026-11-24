<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Type;

use Drupal\custom_field\Plugin\CustomField\FieldType\TelephoneType;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldTypeKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the 'telephone' sub-field type.
 *
 * Modelled on core's field type Kernel tests, closest counterpart
 * core/modules/telephone/tests/src/Kernel/TelephoneItemTest.php.
 *
 * TelephoneType extends StringType, so the prefix/suffix, allowed_values and
 * Length-constraint behavior it inherits is covered once in StringTypeTest
 * rather than duplicated here. What this class covers is what telephone adds or
 * changes: its own default length, its `pattern` setting, its format table and
 * its sample value shape.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldType\TelephoneType
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(TelephoneType::class)]
class TelephoneTypeTest extends CustomFieldTypeKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static function typeId(): string {
    return 'telephone';
  }

  /**
   * {@inheritdoc}
   */
  protected function roundTripCases(): array {
    return [
      'international prefix' => [
        'column' => ['length' => 256],
        'value' => ['sub' => '+0123456789'],
        'expected' => ['sub' => '+0123456789'],
      ],
      'country code and subscriber number' => [
        'column' => ['length' => 256],
        'value' => ['sub' => '+41791234567'],
        'expected' => ['sub' => '+41791234567'],
      ],
      'formatted number with separators' => [
        'column' => ['length' => 256],
        'value' => ['sub' => '555-123-4567'],
        'expected' => ['sub' => '555-123-4567'],
      ],
      'a value violating the configured pattern is still stored' => [
        // CONTRACT, by design. The `pattern` setting exists only to fill
        // the HTML pattern attribute on the widget
        // (TelephoneWidget::widget()) — a browser-side input hint. It is
        // deliberately NOT enforced on save, so Feeds, JSON:API, migrations
        // and programmatic writes are unconstrained.
        // This test exists to stop that being "fixed" by accident: enforcing
        // the pattern on save would reject data that existing sites have
        // already stored through those paths.
        'column' => ['length' => 256],
        'settings' => ['pattern' => 'US'],
        'value' => ['sub' => 'not a phone number at all'],
        'expected' => ['sub' => 'not a phone number at all'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function validationCases(): array {
    return [
      'one character over the configured length' => [
        'column' => ['length' => 256],
        'value' => ['sub' => str_repeat('1', 257)],
        'property' => 'sub',
        'message' => 'sub: may not be longer than 256 characters.',
      ],
    ];
  }

  /**
   * The inherited schema falls back to 255, not TelephoneType::MAX_LENGTH.
   *
   * CHARACTERIZATION, not contract. TelephoneType declares
   * `const MAX_LENGTH = 256` documented as "the default max length for
   * telephone fields", but nothing reads it: StringType::schema() and
   * CustomFieldTypeBase::getMaxlength() both use `self::MAX_LENGTH`, which
   * binds to the declaring class and so resolves to the base's 255. The value
   * 256 is instead hardcoded separately in custom_field_update_10003(),
   * CustomItem::storageSettingsForm() and
   * UpdaterCommands::promptColumnOptions().
   *
   * No supported creation path reaches this state — the UI length element is
   * `#required` with a default of 256, and the install and drush paths always
   * write `length` — so this pins the dead constant rather than a reachable
   * configuration. A future fix would most likely change `self::MAX_LENGTH` to
   * `static::MAX_LENGTH` so the subclass constant takes effect, at which point
   * this expectation becomes 256 and that failure is the intended signal.
   */
  public function testSchemaFallsBackToTheBaseMaxLength(): void {
    $this->assertSame(
      ['sub' => ['type' => 'varchar', 'length' => 255]],
      TelephoneType::schema(['name' => 'sub']),
      'The inherited schema uses the base MAX_LENGTH of 255, not the declared 256.',
    );
  }

  /**
   * The inherited getMaxlength() also ignores the subclass constant.
   *
   * The same `self::` binding as ::testSchemaFallsBackToTheBaseMaxLength(),
   * seen from the other reader: CustomFieldTypeBase::getMaxlength() also uses
   * `self::MAX_LENGTH`, so a telephone column without an explicit length
   * reports 255 rather than the 256 the subclass declares.
   * CHARACTERIZATION, not contract — a fix is expected to make this 256.
   */
  public function testGetMaxlengthIgnoresTheSubclassConstant(): void {
    $this->assertSame(255, $this->telephoneInstance([])->getMaxlength());
  }

  /**
   * An explicit length is honoured, as every supported path supplies one.
   */
  public function testExplicitLengthIsHonoured(): void {
    $this->assertSame(
      ['sub' => ['type' => 'varchar', 'length' => 256]],
      TelephoneType::schema(['name' => 'sub', 'length' => 256]),
    );

    $this->assertSame(256, $this->telephoneInstance(['length' => 256])->getMaxlength());
  }

  /**
   * The declared default field settings, including telephone's own `pattern`.
   */
  public function testDefaultFieldSettings(): void {
    $this->assertSame([
      'pattern' => '',
      'prefix' => '',
      'suffix' => '',
      'allowed_values' => [],
      'label' => '',
      'required' => FALSE,
      'translatable' => FALSE,
      'description' => '',
      'description_display' => 'after',
      'check_empty' => FALSE,
    ], TelephoneType::defaultFieldSettings());
  }

  /**
   * The format table exposes every country with a complete entry.
   *
   * The widget reads this table by key (TelephoneWidget::widget()), so the
   * key set is effectively public API. Each entry's key shape is declared in
   * the method's own return docblock, so asserting it here would be a
   * tautology.
   */
  public function testTelephoneFormats(): void {
    $formats = $this->telephoneInstance([])->getTelephoneFormats();

    $this->assertSame(
      ['AU', 'BR', 'CA', 'CN', 'FR', 'DE', 'IN', 'JP', 'MX', 'ZA', 'ES', 'GB', 'US'],
      array_keys($formats),
    );

    $this->assertSame('xxx-xxx-xxxx', $formats['US']['format']);
    $this->assertSame('[0-9]{3}-[0-9]{3}-[0-9]{4}', $formats['US']['pattern']);
    $this->assertSame('xxxx-xxx-xxxx', $formats['GB']['format']);
  }

  /**
   * Generated sample values use the area-prefix-line shape.
   *
   * @param int $iteration
   *   Unused; the provider exists only to run the generator repeatedly, since a
   *   single draw would not exercise the random bounds.
   *
   * @dataProvider providerSampleIterations
   */
  #[DataProvider('providerSampleIterations')]
  public function testSampleValueShape(int $iteration): void {
    $instance = $this->telephoneInstance([]);

    $this->assertMatchesRegularExpression(
      '/^[1-9][0-9]{2}-[1-9][0-9]{2}-[0-9]{4}$/',
      TelephoneType::generateSampleValue($instance, 'node'),
    );
  }

  /**
   * Data provider for ::testSampleValueShape().
   *
   * @return array<string, array{int}>
   *   Test cases.
   */
  public static function providerSampleIterations(): array {
    return [
      'draw 1' => [1],
      'draw 2' => [2],
      'draw 3' => [3],
      'draw 4' => [4],
      'draw 5' => [5],
    ];
  }

  /**
   * Returns a configured telephone type instance.
   *
   * @param array<string, mixed> $settings
   *   Column settings to merge over the name.
   *
   * @return \Drupal\custom_field\Plugin\CustomField\FieldType\TelephoneType
   *   The configured instance.
   */
  protected function telephoneInstance(array $settings): TelephoneType {
    $instance = $this->typeManager->createInstance('telephone', [
      'settings' => ['name' => 'sub'] + $settings,
    ]);
    $this->assertInstanceOf(CustomFieldTypeInterface::class, $instance);
    $this->assertInstanceOf(TelephoneType::class, $instance);

    return $instance;
  }

}
