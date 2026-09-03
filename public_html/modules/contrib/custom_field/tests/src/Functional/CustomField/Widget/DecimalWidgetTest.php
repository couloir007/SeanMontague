<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the decimal widget.
 *
 * Shared NumberWidgetBase/NumericTypeBase behavior (min/max attributes,
 * prefix/suffix, the min=0 message-selection fix) is already covered
 * thoroughly by IntegerWidgetTest - this file focuses on what's
 * specific to decimal: the scale-derived step attribute and exact
 * fixed-precision round-tripping.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class DecimalWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createCustomField(
      'field_test',
      [
        'dec_basic' => [
          'name' => 'dec_basic',
          'type' => 'decimal',
        ],
        'dec_custom_scale' => [
          'name' => 'dec_custom_scale',
          'type' => 'decimal',
          'scale' => 3,
        ],
        'dec_unsigned' => [
          'name' => 'dec_unsigned',
          'type' => 'decimal',
          'unsigned' => TRUE,
        ],
      ],
      [
        'dec_basic' => [
          'label' => 'Decimal basic',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'min' => '',
          'max' => '',
          'prefix' => '',
          'suffix' => '',
        ],
        'dec_custom_scale' => [
          'label' => 'Decimal custom scale',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'min' => '',
          'max' => '',
          'prefix' => '',
          'suffix' => '',
        ],
        'dec_unsigned' => [
          'label' => 'Decimal unsigned',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'min' => '',
          'max' => '',
          'prefix' => '',
          'suffix' => '',
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'dec_basic' => [
        'type' => 'decimal',
        'weight' => 0,
        'label' => 'Decimal basic',
        'placeholder' => '',
      ],
      'dec_custom_scale' => [
        'type' => 'decimal',
        'weight' => 1,
        'label' => 'Decimal custom scale',
        'placeholder' => '',
      ],
      'dec_unsigned' => [
        'type' => 'decimal',
        'weight' => 2,
        'label' => 'Decimal unsigned',
        'placeholder' => '',
      ],
    ]);
  }

  /**
   * Tests the placeholder widget setting.
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();
    $base = self::FIELD_PATH . '[dec_basic][placeholder]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');
    $assert->fieldValueEquals($base, '');

    $this->submitForm([
      $base => 'Enter a price',
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    $this->drupalGet('node/add/page');
    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][dec_basic]"]',
      'placeholder',
      'Enter a price'
    );
  }

  /**
   * Tests the input type and step attribute.
   *
   * Tests that the field renders as a native number input with the
   * default scale's step (scale defaults to 2 when unset -> 0.01).
   */
  public function testNumberElementTypeAndDefaultStep(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists('field_test[0][dec_basic]');
    $this->assertEquals('number', $field->getAttribute('type'));
    $this->assertEquals('0.01', $field->getAttribute('step'));
  }

  /**
   * Tests that the step attribute reflects a custom, non-default scale.
   */
  public function testStepReflectsCustomScale(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists('field_test[0][dec_custom_scale]');
    $this->assertEquals('0.001', $field->getAttribute('step'));
  }

  /**
   * Tests that a submitted decimal value persists exactly.
   *
   * Decimal is stored as a fixed-precision 'numeric' DB column (unlike
   * float's genuine floating point), so an exact value matching the
   * configured scale should round-trip without any precision drift.
   */
  public function testValuePersists(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Decimal node',
      'field_test[0][dec_basic]' => '19.99',
    ], 'Save');

    $node = $this->loadNodeByTitle('Decimal node');
    $this->assertEquals(19.99, $node->get('field_test')->dec_basic);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals('field_test[0][dec_basic]', '19.99');
  }

  /**
   * Tests that an empty value stores NULL.
   */
  public function testEmptyValueStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty decimal node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty decimal node');
    $value = $node->get('field_test')->dec_basic ?? NULL;
    $this->assertTrue($value === NULL || $value === '');
  }

  /**
   * Tests that a required decimal field is enforced.
   *
   * Scoped to its own field rather than setUp()'s shared field_test, so
   * the other tests aren't forced to also submit a value for it.
   */
  public function testRequiredDecimalValidation(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_dec_required',
      [
        'dec_required' => [
          'name' => 'dec_required',
          'type' => 'decimal',
        ],
      ],
      [
        'dec_required' => [
          'label' => 'Decimal required',
          'check_empty' => FALSE,
          'required' => TRUE,
          'description' => '',
          'min' => '',
          'max' => '',
          'prefix' => '',
          'suffix' => '',
        ],
      ],
    );
    $required_field->setRequired(TRUE)->save();
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    $this->setFormDisplay('field_dec_required', [
      'dec_required' => [
        'type' => 'decimal',
        'weight' => 0,
        'label' => 'Decimal required',
        'placeholder' => '',
      ],
    ]);

    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists('field_dec_required[0][dec_required]');
    $this->assertStringContainsString(
      'required',
      (string) $field->getAttribute('class')
    );

    $this->submitForm([
      'title[0][value]' => 'Required decimal node',
    ], 'Save');
    $assert->pageTextNotContains('Required decimal node has been created');

    $this->submitForm([
      'field_dec_required[0][dec_required]' => '5.5',
    ], 'Save');

    $node = $this->loadNodeByTitle('Required decimal node');
    $this->assertEquals(5.5, $node->get('field_dec_required')->dec_required);
  }

  /**
   * Tests that a custom-scale value persists at full scale precision.
   */
  public function testCustomScaleValuePersists(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Scale three node',
      'field_test[0][dec_custom_scale]' => '1.234',
    ], 'Save');

    $node = $this->loadNodeByTitle('Scale three node');
    $this->assertEquals(1.234, $node->get('field_test')->dec_custom_scale);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals('field_test[0][dec_custom_scale]', '1.234');
  }

  /**
   * Tests that a signed decimal field accepts and persists negative values.
   */
  public function testNegativeValuePersists(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Negative decimal node',
      'field_test[0][dec_basic]' => '-3.50',
    ], 'Save');

    $node = $this->loadNodeByTitle('Negative decimal node');
    $this->assertEquals(-3.5, $node->get('field_test')->dec_basic);

    $this->drupalGet('node/' . $node->id() . '/edit');
    // Form value keeps scale padding (default scale 2 → -3.50).
    $assert->fieldValueEquals('field_test[0][dec_basic]', '-3.50');
  }

  /**
   * Tests that an unsigned decimal with no min forces #min to 0.
   *
   * Mirrors IntegerWidget: DecimalWidget now clamps the HTML min attribute
   * when the column is unsigned and field min is unset or negative.
   */
  public function testUnsignedWithNoMinForcesZero(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists('field_test[0][dec_unsigned]');
    $this->assertEquals('0', $field->getAttribute('min'));
  }

}
