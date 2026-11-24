<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the float widget.
 *
 * Shared NumberWidgetBase/NumericTypeBase behavior (min/max attributes,
 * prefix/suffix, the min=0 message-selection fix) is already covered
 * thoroughly by IntegerWidgetTest - this file focuses on what's
 * specific to float: the always-'any' step and genuine floating point
 * storage (as opposed to decimal's fixed-precision column).
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class FloatWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createCustomField(
      'field_test',
      [
        'float_basic' => [
          'name' => 'float_basic',
          'type' => 'float',
        ],
      ],
      [
        'float_basic' => [
          'label' => 'Float basic',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'min' => '',
          'max' => '',
          'prefix' => '',
          'suffix' => '',
          'allowed_values' => [],
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'float_basic' => [
        'type' => 'float',
        'weight' => 0,
        'label' => 'Float basic',
        'placeholder' => '',
      ],
    ]);
  }

  /**
   * Tests the placeholder widget setting.
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();
    $base = self::FIELD_PATH . '[float_basic][placeholder]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');
    $assert->fieldValueEquals($base, '');

    $this->submitForm([
      $base => 'Enter a measurement',
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    $this->drupalGet('node/add/page');
    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][float_basic]"]',
      'placeholder',
      'Enter a measurement'
    );
  }

  /**
   * Tests that the field renders as a native number input with step=any.
   *
   * FloatWidget explicitly re-sets #step to 'any', even though
   * NumberWidgetBase's parent::widget() already defaults it to the same
   * value - redundant but harmless, so the observable result is the
   * same either way.
   */
  public function testNumberElementTypeAndStep(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists('field_test[0][float_basic]');
    $this->assertEquals('number', $field->getAttribute('type'));
    $this->assertEquals('any', $field->getAttribute('step'));
  }

  /**
   * Tests that a submitted value persists through save and reload.
   *
   * Kept to a small value with few decimal digits: per FloatType's own
   * generateSampleValue() documentation, a non-'big'-size float column
   * is single-precision and reliably holds only ~7 significant digits.
   * A value with more precision than that could show floating-point
   * representation drift that isn't a bug, just how the storage works.
   */
  public function testValuePersists(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Float node',
      'field_test[0][float_basic]' => '3.5',
    ], 'Save');

    $node = $this->loadNodeByTitle('Float node');
    $this->assertEquals(3.5, $node->get('field_test')->float_basic);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals('field_test[0][float_basic]', '3.5');
  }

  /**
   * Tests that an empty value stores NULL.
   */
  public function testEmptyValueStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty float node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty float node');
    $value = $node->get('field_test')->float_basic ?? NULL;
    $this->assertTrue($value === NULL || $value === '');
  }

  /**
   * Tests that a required float field is enforced.
   *
   * Scoped to its own field rather than setUp()'s shared field_test, so
   * the other tests aren't forced to also submit a value for it.
   */
  public function testRequiredFloatValidation(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_float_required',
      [
        'float_required' => [
          'name' => 'float_required',
          'type' => 'float',
        ],
      ],
      [
        'float_required' => [
          'label' => 'Float required',
          'check_empty' => FALSE,
          'required' => TRUE,
          'description' => '',
          'min' => '',
          'max' => '',
          'prefix' => '',
          'suffix' => '',
          'allowed_values' => [],
        ],
      ],
    );
    $required_field->setRequired(TRUE)->save();
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    $this->setFormDisplay('field_float_required', [
      'float_required' => [
        'type' => 'float',
        'weight' => 0,
        'label' => 'Float required',
        'placeholder' => '',
      ],
    ]);

    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists('field_float_required[0][float_required]');
    $this->assertStringContainsString(
      'required',
      (string) $field->getAttribute('class')
    );

    $this->submitForm([
      'title[0][value]' => 'Required float node',
    ], 'Save');
    $assert->pageTextNotContains('Required float node has been created');

    $this->submitForm([
      'field_float_required[0][float_required]' => '2.5',
    ], 'Save');

    $node = $this->loadNodeByTitle('Required float node');
    $this->assertEquals(
      2.5,
      $node->get('field_float_required')->float_required
    );
  }

}
