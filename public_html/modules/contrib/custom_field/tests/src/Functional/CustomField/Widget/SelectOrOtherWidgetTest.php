<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the select_or_other widget.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class SelectOrOtherWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createCustomField(
      'field_test',
      [
        'choice_string' => [
          'name' => 'choice_string',
          'type' => 'string',
          'length' => 255,
        ],
        'choice_integer' => [
          'name' => 'choice_integer',
          'type' => 'integer',
          'size' => 'normal',
          'unsigned' => FALSE,
        ],
        'choice_float' => [
          'name' => 'choice_float',
          'type' => 'float',
          'size' => 'normal',
          'unsigned' => FALSE,
        ],
      ],
      [
        'choice_string' => [
          'label' => 'String choice',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'allowed_values' => [
            ['key' => 'apple', 'label' => 'Apple'],
            ['key' => 'banana', 'label' => 'Banana'],
          ],
        ],
        'choice_integer' => [
          'label' => 'Integer choice',
          'check_empty' => FALSE,
          'required' => FALSE,
          // min/max feed the "other" number input via SelectOrOtherWidget.
          'min' => 0,
          'max' => 100,
          'allowed_values' => [
            ['key' => '1', 'label' => 'One'],
            ['key' => '2', 'label' => 'Two'],
          ],
        ],
        'choice_float' => [
          'label' => 'Float choice',
          'check_empty' => FALSE,
          'required' => FALSE,
          'min' => 0,
          'max' => 100,
          'allowed_values' => [
            ['key' => '1.5', 'label' => 'One and a half'],
            ['key' => '2.5', 'label' => 'Two and a half'],
          ],
        ],
      ],
    );

    $widget_defaults = [
      'type' => 'select_or_other',
      'empty_option' => '- None -',
      'select_element_type' => 'list',
      'other_field_label' => 'Other',
      'other_option' => 'Other option',
    ];

    $this->setFormDisplay('field_test', [
      'choice_string' => $widget_defaults + [
        'weight' => 0,
        'label' => 'String choice',
        'other_placeholder' => 'Enter custom value',
      ],
      'choice_integer' => $widget_defaults + [
        'weight' => 1,
        'label' => 'Integer choice',
        'other_placeholder' => 'Enter number',
      ],
      'choice_float' => $widget_defaults + [
        'weight' => 2,
        'label' => 'Float choice',
        'other_placeholder' => 'Enter decimal',
      ],
    ]);
  }

  /**
   * Tests widget settings via Manage form display.
   */
  public function testWidgetSettingsFormUi(): void {
    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm([], 'field_test_settings_edit');

    $this->assertSession()->fieldExists(self::FIELD_PATH . '[choice_string][select_element_type]');
    $this->assertSession()->fieldValueEquals(self::FIELD_PATH . '[choice_string][other_field_label]', 'Other');

    $this->submitForm([
      self::FIELD_PATH . '[choice_string][other_field_label]' => 'Something else',
      self::FIELD_PATH . '[choice_string][other_placeholder]' => 'Type here',
    ], 'field_test_plugin_settings_update');

    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('Your settings have been saved.');

    $this->drupalGet('node/add/page');
    $this->assertSession()->pageTextContains('Something else');
    $this->assertSession()->elementAttributeContains(
      'css',
      'input[name="field_test[0][choice_string][other]"]',
      'placeholder',
      'Type here'
    );
  }

  /**
   * Tests create/edit with list options for string, integer, and float.
   */
  public function testCreateAndEditWithOptions(): void {
    $this->drupalGet('node/add/page');
    $this->assertSession()->statusCodeEquals(200);

    foreach (['choice_string', 'choice_integer', 'choice_float'] as $subfield) {
      $this->assertSession()->fieldExists("field_test[0][{$subfield}][select]");
      $this->assertSession()->fieldExists("field_test[0][{$subfield}][other]");
    }

    $this->assertSession()->optionExists('field_test[0][choice_string][select]', 'apple');
    $this->assertSession()->optionExists('field_test[0][choice_integer][select]', '1');
    $this->assertSession()->optionExists('field_test[0][choice_float][select]', '1.5');

    // Integer/float "other" inputs are number fields.
    $this->assertSession()->elementAttributeContains(
      'css',
      'input[name="field_test[0][choice_integer][other]"]',
      'type',
      'number'
    );
    $this->assertSession()->elementAttributeContains(
      'css',
      'input[name="field_test[0][choice_float][other]"]',
      'type',
      'number'
    );
    $this->assertSession()->elementAttributeContains(
      'css',
      'input[name="field_test[0][choice_float][other]"]',
      'step',
      'any'
    );

    $this->submitForm([
      'title[0][value]' => 'Options node',
      'field_test[0][choice_string][select]' => 'apple',
      'field_test[0][choice_integer][select]' => '1',
      'field_test[0][choice_float][select]' => '1.5',
    ], 'Save');

    $node = $this->loadNodeByTitle('Options node');
    $this->assertEquals('apple', $node->get('field_test')->choice_string);
    $this->assertEquals(1, (int) $node->get('field_test')->choice_integer);
    $this->assertEquals(1.5, (float) $node->get('field_test')->choice_float);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->fieldValueEquals('field_test[0][choice_string][select]', 'apple');
    $this->assertSession()->fieldValueEquals('field_test[0][choice_integer][select]', '1');
    $this->assertSession()->fieldValueEquals('field_test[0][choice_float][select]', '1.5');

    $this->submitForm([
      'field_test[0][choice_string][select]' => 'banana',
      'field_test[0][choice_integer][select]' => '2',
      'field_test[0][choice_float][select]' => '2.5',
    ], 'Save');

    $node = $this->reloadNode($node->id());
    $this->assertEquals('banana', $node->get('field_test')->choice_string);
    $this->assertEquals(2, (int) $node->get('field_test')->choice_integer);
    $this->assertEquals(2.5, (float) $node->get('field_test')->choice_float);
  }

  /**
   * Tests create/edit via the Other path for string, integer, and float.
   */
  public function testCreateAndEditWithOtherValues(): void {
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Other values node',
      'field_test[0][choice_string][select]' => 'select_or_other',
      'field_test[0][choice_string][other]' => 'mango',
      'field_test[0][choice_integer][select]' => 'select_or_other',
      'field_test[0][choice_integer][other]' => '42',
      'field_test[0][choice_float][select]' => 'select_or_other',
      'field_test[0][choice_float][other]' => '3.14',
    ], 'Save');

    $node = $this->loadNodeByTitle('Other values node');
    $this->assertEquals('mango', $node->get('field_test')->choice_string);
    $this->assertEquals(42, (int) $node->get('field_test')->choice_integer);
    $this->assertEqualsWithDelta(3.14, (float) $node->get('field_test')->choice_float, 0.001);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->fieldValueEquals('field_test[0][choice_string][select]', 'select_or_other');
    $this->assertSession()->fieldValueEquals('field_test[0][choice_string][other]', 'mango');
    $this->assertSession()->fieldValueEquals('field_test[0][choice_integer][select]', 'select_or_other');
    $this->assertSession()->fieldValueEquals('field_test[0][choice_integer][other]', '42');
    $this->assertSession()->fieldValueEquals('field_test[0][choice_float][select]', 'select_or_other');
    $this->assertSession()->fieldValueEquals('field_test[0][choice_float][other]', '3.14');
  }

  /**
   * Tests empty subfield values.
   */
  public function testEmptyValues(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty choices node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty choices node');
    foreach (['choice_string', 'choice_integer', 'choice_float'] as $subfield) {
      $value = $node->get('field_test')->{$subfield} ?? NULL;
      $this->assertTrue($value === NULL || $value === '', sprintf('%s should be empty.', $subfield));
    }
  }

  /**
   * Tests selecting Other with an empty other textfield stores NULL.
   */
  public function testOtherEmptyStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty other node',
      'field_test[0][choice_string][select]' => 'select_or_other',
      'field_test[0][choice_string][other]' => '',
      'field_test[0][choice_integer][select]' => 'select_or_other',
      'field_test[0][choice_integer][other]' => '',
      'field_test[0][choice_float][select]' => 'select_or_other',
      'field_test[0][choice_float][other]' => '',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty other node');
    foreach (['choice_string', 'choice_integer', 'choice_float'] as $subfield) {
      $value = $node->get('field_test')->{$subfield} ?? NULL;
      $this->assertTrue($value === NULL || $value === '', sprintf('%s other-empty should be null.', $subfield));
    }
  }

  /**
   * Tests select_element_type = buttons for string, integer, and float.
   *
   * Confirms the radios element path for every field_types entry on the
   * widget (string, integer, float), including the empty-option radio
   * when the subfield is not required.
   */
  public function testButtonsModeAllSupportedTypes(): void {
    $assert = $this->assertSession();

    $this->createCustomField(
      'field_buttons',
      [
        'btn_string' => [
          'name' => 'btn_string',
          'type' => 'string',
          'length' => 255,
        ],
        'btn_integer' => [
          'name' => 'btn_integer',
          'type' => 'integer',
          'size' => 'normal',
          'unsigned' => FALSE,
        ],
        'btn_float' => [
          'name' => 'btn_float',
          'type' => 'float',
          'size' => 'normal',
          'unsigned' => FALSE,
        ],
      ],
      [
        'btn_string' => [
          'label' => 'Buttons string',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'allowed_values' => [
            ['key' => 'apple', 'label' => 'Apple'],
            ['key' => 'banana', 'label' => 'Banana'],
          ],
        ],
        'btn_integer' => [
          'label' => 'Buttons integer',
          'check_empty' => FALSE,
          'required' => FALSE,
          'min' => 0,
          'max' => 100,
          'allowed_values' => [
            ['key' => '1', 'label' => 'One'],
            ['key' => '2', 'label' => 'Two'],
          ],
        ],
        'btn_float' => [
          'label' => 'Buttons float',
          'check_empty' => FALSE,
          'required' => FALSE,
          'min' => 0,
          'max' => 100,
          'allowed_values' => [
            ['key' => '1.5', 'label' => 'One and a half'],
            ['key' => '2.5', 'label' => 'Two and a half'],
          ],
        ],
      ],
    );

    $widget_defaults = [
      'type' => 'select_or_other',
      'empty_option' => '- None -',
      'select_element_type' => 'buttons',
      'other_field_label' => 'Other',
      'other_option' => 'Other option',
      'other_placeholder' => '',
    ];

    $this->setFormDisplay('field_buttons', [
      'btn_string' => $widget_defaults + [
        'weight' => 0,
        'label' => 'Buttons string',
      ],
      'btn_integer' => $widget_defaults + [
        'weight' => 1,
        'label' => 'Buttons integer',
      ],
      'btn_float' => $widget_defaults + [
        'weight' => 2,
        'label' => 'Buttons float',
      ],
    ]);

    $this->drupalGet('node/add/page');

    // Radios still expose select/other children; empty option is present
    // when not required.
    foreach (['btn_string', 'btn_integer', 'btn_float'] as $subfield) {
      $assert->fieldExists("field_buttons[0][{$subfield}][select]");
      $assert->fieldExists("field_buttons[0][{$subfield}][other]");
      $assert->elementExists(
        'css',
        'input[type="radio"][name="field_buttons[0][' . $subfield . '][select]"]'
      );
    }

    $this->submitForm([
      'title[0][value]' => 'Buttons mode node',
      'field_buttons[0][btn_string][select]' => 'banana',
      'field_buttons[0][btn_integer][select]' => '2',
      'field_buttons[0][btn_float][select]' => '2.5',
    ], 'Save');

    $node = $this->loadNodeByTitle('Buttons mode node');
    $this->assertEquals('banana', $node->get('field_buttons')->btn_string);
    $this->assertEquals(2, (int) $node->get('field_buttons')->btn_integer);
    $this->assertEquals(2.5, (float) $node->get('field_buttons')->btn_float);

    // Other path still works in buttons mode (string is enough to prove
    // select_or_other + other; int/float other are covered in list mode).
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Buttons other node',
      'field_buttons[0][btn_string][select]' => 'select_or_other',
      'field_buttons[0][btn_string][other]' => 'kiwi',
      'field_buttons[0][btn_integer][select]' => 'select_or_other',
      'field_buttons[0][btn_integer][other]' => '7',
      'field_buttons[0][btn_float][select]' => 'select_or_other',
      'field_buttons[0][btn_float][other]' => '9.5',
    ], 'Save');

    $node = $this->loadNodeByTitle('Buttons other node');
    $this->assertEquals('kiwi', $node->get('field_buttons')->btn_string);
    $this->assertEquals(7, (int) $node->get('field_buttons')->btn_integer);
    $this->assertEquals(9.5, (float) $node->get('field_buttons')->btn_float);
  }

}
