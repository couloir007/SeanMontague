<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the select widget.
 *
 * Covers all field_types (string, integer, float). Requires non-empty
 * allowed_values (ListWidgetBase::isApplicable). Settings are limited to
 * empty_option (default "- Select -").
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class SelectWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createCustomField(
      'field_test',
      [
        'sel_string' => [
          'name' => 'sel_string',
          'type' => 'string',
          'length' => 255,
        ],
        'sel_integer' => [
          'name' => 'sel_integer',
          'type' => 'integer',
          'size' => 'normal',
          'unsigned' => FALSE,
        ],
        'sel_float' => [
          'name' => 'sel_float',
          'type' => 'float',
          'size' => 'normal',
          'unsigned' => FALSE,
        ],
      ],
      [
        'sel_string' => [
          'label' => 'String select',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'allowed_values' => [
            ['key' => 'apple', 'label' => 'Apple'],
            ['key' => 'banana', 'label' => 'Banana'],
          ],
        ],
        'sel_integer' => [
          'label' => 'Integer select',
          'check_empty' => FALSE,
          'required' => FALSE,
          'min' => '',
          'max' => '',
          'prefix' => '',
          'suffix' => '',
          'allowed_values' => [
            ['key' => '1', 'label' => 'One'],
            ['key' => '2', 'label' => 'Two'],
          ],
        ],
        'sel_float' => [
          'label' => 'Float select',
          'check_empty' => FALSE,
          'required' => FALSE,
          'min' => '',
          'max' => '',
          'prefix' => '',
          'suffix' => '',
          'allowed_values' => [
            ['key' => '1.5', 'label' => 'One and a half'],
            ['key' => '2.5', 'label' => 'Two and a half'],
          ],
        ],
      ],
    );

    $widget_defaults = [
      'type' => 'select',
      'empty_option' => '- Select -',
    ];

    $this->setFormDisplay('field_test', [
      'sel_string' => $widget_defaults + [
        'weight' => 0,
        'label' => 'String select',
      ],
      'sel_integer' => $widget_defaults + [
        'weight' => 1,
        'label' => 'Integer select',
      ],
      'sel_float' => $widget_defaults + [
        'weight' => 2,
        'label' => 'Float select',
      ],
    ]);
  }

  /**
   * Tests empty_option widget setting.
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();
    $base = self::FIELD_PATH . '[sel_string]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');

    $assert->fieldValueEquals($base . '[empty_option]', '- Select -');

    $this->submitForm([
      $base . '[empty_option]' => '- Choose -',
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    $this->drupalGet('node/add/page');
    $assert->optionExists('field_test[0][sel_string]', '');
    $assert->pageTextContains('- Choose -');
  }

  /**
   * Tests select elements and allowed_values options for all types.
   */
  public function testOptionsRenderForAllTypes(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    foreach (['sel_string', 'sel_integer', 'sel_float'] as $subfield) {
      $assert->elementExists(
        'css',
        'select[name="field_test[0][' . $subfield . ']"]'
      );
    }

    $assert->optionExists('field_test[0][sel_string]', 'apple');
    $assert->optionExists('field_test[0][sel_string]', 'banana');
    $assert->optionExists('field_test[0][sel_integer]', '1');
    $assert->optionExists('field_test[0][sel_integer]', '2');
    $assert->optionExists('field_test[0][sel_float]', '1.5');
    $assert->optionExists('field_test[0][sel_float]', '2.5');
  }

  /**
   * Tests create/edit with options for string, integer, and float.
   */
  public function testCreateAndEditWithOptions(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Select options node',
      'field_test[0][sel_string]' => 'apple',
      'field_test[0][sel_integer]' => '1',
      'field_test[0][sel_float]' => '1.5',
    ], 'Save');

    $node = $this->loadNodeByTitle('Select options node');
    $this->assertEquals('apple', $node->get('field_test')->sel_string);
    $this->assertEquals(1, (int) $node->get('field_test')->sel_integer);
    $this->assertEquals(1.5, (float) $node->get('field_test')->sel_float);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals('field_test[0][sel_string]', 'apple');
    $assert->fieldValueEquals('field_test[0][sel_integer]', '1');
    $assert->fieldValueEquals('field_test[0][sel_float]', '1.5');

    $this->submitForm([
      'field_test[0][sel_string]' => 'banana',
      'field_test[0][sel_integer]' => '2',
      'field_test[0][sel_float]' => '2.5',
    ], 'Save');

    $node = $this->reloadNode($node->id());
    $this->assertEquals('banana', $node->get('field_test')->sel_string);
    $this->assertEquals(2, (int) $node->get('field_test')->sel_integer);
    $this->assertEquals(2.5, (float) $node->get('field_test')->sel_float);
  }

  /**
   * Tests empty selection stores NULL for all types.
   */
  public function testEmptyValues(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty select node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty select node');
    foreach (['sel_string', 'sel_integer', 'sel_float'] as $subfield) {
      $value = $node->get('field_test')->{$subfield} ?? NULL;
      $this->assertTrue(
        $value === NULL || $value === '',
        sprintf('%s should be empty.', $subfield)
      );
    }
  }

  /**
   * Tests required select validation for a string subfield.
   */
  public function testRequiredSelectValidation(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_sel_required',
      [
        'sel_required' => [
          'name' => 'sel_required',
          'type' => 'string',
          'length' => 255,
        ],
      ],
      [
        'sel_required' => [
          'label' => 'Select required',
          'check_empty' => FALSE,
          'required' => TRUE,
          'description' => '',
          'allowed_values' => [
            ['key' => 'yes', 'label' => 'Yes'],
            ['key' => 'no', 'label' => 'No'],
          ],
        ],
      ],
    );
    $required_field->setRequired(TRUE)->save();
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    $this->setFormDisplay('field_sel_required', [
      'sel_required' => [
        'type' => 'select',
        'weight' => 0,
        'label' => 'Select required',
        'empty_option' => '- Select -',
      ],
    ]);

    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Required select node',
    ], 'Save');
    $assert->pageTextNotContains('Required select node has been created');

    $this->submitForm([
      'field_sel_required[0][sel_required]' => 'yes',
    ], 'Save');

    $node = $this->loadNodeByTitle('Required select node');
    $this->assertEquals(
      'yes',
      $node->get('field_sel_required')->sel_required
    );
  }

}
