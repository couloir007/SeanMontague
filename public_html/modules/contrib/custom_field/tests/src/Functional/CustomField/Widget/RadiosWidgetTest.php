<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the radios widget.
 *
 * Covers all field_types (string, integer, float). Requires non-empty
 * allowed_values (ListWidgetBase::isApplicable). When the subfield is not
 * required, an empty option (default label "N/A") is prepended to the
 * options list.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class RadiosWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createCustomField(
      'field_test',
      [
        'rad_string' => [
          'name' => 'rad_string',
          'type' => 'string',
          'length' => 255,
        ],
        'rad_integer' => [
          'name' => 'rad_integer',
          'type' => 'integer',
          'size' => 'normal',
          'unsigned' => FALSE,
        ],
        'rad_float' => [
          'name' => 'rad_float',
          'type' => 'float',
          'size' => 'normal',
          'unsigned' => FALSE,
        ],
      ],
      [
        'rad_string' => [
          'label' => 'String radios',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'allowed_values' => [
            ['key' => 'apple', 'label' => 'Apple'],
            ['key' => 'banana', 'label' => 'Banana'],
          ],
        ],
        'rad_integer' => [
          'label' => 'Integer radios',
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
        'rad_float' => [
          'label' => 'Float radios',
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
      'type' => 'radios',
      'empty_option' => 'N/A',
    ];

    $this->setFormDisplay('field_test', [
      'rad_string' => $widget_defaults + [
        'weight' => 0,
        'label' => 'String radios',
      ],
      'rad_integer' => $widget_defaults + [
        'weight' => 1,
        'label' => 'Integer radios',
      ],
      'rad_float' => $widget_defaults + [
        'weight' => 2,
        'label' => 'Float radios',
      ],
    ]);
  }

  /**
   * Tests empty_option widget setting (default N/A for radios).
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();
    $base = self::FIELD_PATH . '[rad_string]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');

    $assert->fieldValueEquals($base . '[empty_option]', 'N/A');

    $this->submitForm([
      $base . '[empty_option]' => 'None',
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    $this->drupalGet('node/add/page');
    // Empty option is prepended for non-required radios.
    $assert->pageTextContains('None');
    $assert->elementExists(
      'css',
      'input[type="radio"][name="field_test[0][rad_string]"][value=""]'
    );
  }

  /**
   * Tests radio inputs and allowed_values for all types.
   */
  public function testOptionsRenderForAllTypes(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    foreach (['rad_string', 'rad_integer', 'rad_float'] as $subfield) {
      $assert->elementExists(
        'css',
        'input[type="radio"][name="field_test[0][' . $subfield . ']"]'
      );
    }

    $assert->elementExists(
      'css',
      'input[type="radio"][name="field_test[0][rad_string]"][value="apple"]'
    );
    $assert->elementExists(
      'css',
      'input[type="radio"][name="field_test[0][rad_string]"][value="banana"]'
    );
    $assert->elementExists(
      'css',
      'input[type="radio"][name="field_test[0][rad_integer]"][value="1"]'
    );
    $assert->elementExists(
      'css',
      'input[type="radio"][name="field_test[0][rad_float]"][value="1.5"]'
    );

    // Non-required: empty option present with default label N/A.
    $assert->elementExists(
      'css',
      'input[type="radio"][name="field_test[0][rad_string]"][value=""]'
    );
    $assert->pageTextContains('N/A');
  }

  /**
   * Tests create/edit with radios for string, integer, and float.
   */
  public function testCreateAndEditWithOptions(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Radios options node',
      'field_test[0][rad_string]' => 'apple',
      'field_test[0][rad_integer]' => '1',
      'field_test[0][rad_float]' => '1.5',
    ], 'Save');

    $node = $this->loadNodeByTitle('Radios options node');
    $this->assertEquals('apple', $node->get('field_test')->rad_string);
    $this->assertEquals(1, (int) $node->get('field_test')->rad_integer);
    $this->assertEquals(1.5, (float) $node->get('field_test')->rad_float);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals('field_test[0][rad_string]', 'apple');
    $assert->fieldValueEquals('field_test[0][rad_integer]', '1');
    $assert->fieldValueEquals('field_test[0][rad_float]', '1.5');

    $this->submitForm([
      'field_test[0][rad_string]' => 'banana',
      'field_test[0][rad_integer]' => '2',
      'field_test[0][rad_float]' => '2.5',
    ], 'Save');

    $node = $this->reloadNode($node->id());
    $this->assertEquals('banana', $node->get('field_test')->rad_string);
    $this->assertEquals(2, (int) $node->get('field_test')->rad_integer);
    $this->assertEquals(2.5, (float) $node->get('field_test')->rad_float);
  }

  /**
   * Tests empty / N/A selection stores NULL for all types.
   */
  public function testEmptyValues(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty radios node',
      // Explicit empty radio value.
      'field_test[0][rad_string]' => '',
      'field_test[0][rad_integer]' => '',
      'field_test[0][rad_float]' => '',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty radios node');
    foreach (['rad_string', 'rad_integer', 'rad_float'] as $subfield) {
      $value = $node->get('field_test')->{$subfield} ?? NULL;
      $this->assertTrue(
        $value === NULL || $value === '',
        sprintf('%s should be empty.', $subfield)
      );
    }
  }

  /**
   * Tests required radios: no empty option, empty submit rejected.
   */
  public function testRequiredRadiosValidation(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_rad_required',
      [
        'rad_required' => [
          'name' => 'rad_required',
          'type' => 'string',
          'length' => 255,
        ],
      ],
      [
        'rad_required' => [
          'label' => 'Radios required',
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

    $this->setFormDisplay('field_rad_required', [
      'rad_required' => [
        'type' => 'radios',
        'weight' => 0,
        'label' => 'Radios required',
        'empty_option' => 'N/A',
      ],
    ]);

    $this->drupalGet('node/add/page');

    // Required radios should not prepend the empty option.
    $assert->elementNotExists(
      'css',
      'input[type="radio"][name="field_rad_required[0][rad_required]"][value=""]'
    );
    $assert->elementExists(
      'css',
      'input[type="radio"][name="field_rad_required[0][rad_required]"][value="yes"]'
    );

    $this->submitForm([
      'title[0][value]' => 'Required radios node',
    ], 'Save');
    $assert->pageTextNotContains('Required radios node has been created');

    $this->submitForm([
      'field_rad_required[0][rad_required]' => 'yes',
    ], 'Save');

    $node = $this->loadNodeByTitle('Required radios node');
    $this->assertEquals(
      'yes',
      $node->get('field_rad_required')->rad_required
    );
  }

}
