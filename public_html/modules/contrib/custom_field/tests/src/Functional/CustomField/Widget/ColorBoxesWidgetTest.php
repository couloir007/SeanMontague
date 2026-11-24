<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the color_boxes widget.
 *
 * The underlying value field is a plain visually-hidden textfield, not
 * a FAPI 'hidden' type or a native input[type=color], so it can be
 * filled directly via a normal submitForm() call.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class ColorBoxesWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $field = $this->createCustomField(
      'field_test',
      [
        'color_boxes_optional' => [
          'name' => 'color_boxes_optional',
          'type' => 'color',
        ],
        'color_boxes_required' => [
          'name' => 'color_boxes_required',
          'type' => 'color',
        ],
      ],
      [
        'color_boxes_optional' => [
          'label' => 'Optional color boxes',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
        ],
        'color_boxes_required' => [
          'label' => 'Required color boxes',
          'check_empty' => FALSE,
          'required' => TRUE,
          'description' => '',
        ],
      ],
    );

    // Marks the outer field required so the subfield-level 'required'
    // setting above actually takes effect - see CustomFieldWidgetBase.
    $field->setRequired(TRUE)->save();
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    $this->setFormDisplay('field_test', [
      'color_boxes_optional' => [
        'type' => 'color_boxes',
        'weight' => 0,
        'label' => 'Optional color boxes',
      ],
      'color_boxes_required' => [
        'type' => 'color_boxes',
        'weight' => 1,
        'label' => 'Required color boxes',
      ],
    ]);
  }

  /**
   * Tests the default_colors widget setting and its normalization.
   *
   * The settingsColorValidate() lowercases every extracted hex match
   * regardless of submitted case, despite the field's own description
   * text asking for upper case.
   */
  public function testDefaultColorsSettingNormalization(): void {
    $assert = $this->assertSession();
    $base = self::FIELD_PATH . '[color_boxes_optional]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');
    $assert->fieldExists($base . '[default_colors]');

    $this->submitForm([
      $base . '[default_colors]' => '#FF0000,#00Ff00 garbage #0000FF',
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    $this->drupalGet('node/add/page');
    $assert->responseContains('#ff0000');
    $assert->responseContains('#00ff00');
    $assert->responseContains('#0000ff');
  }

  /**
   * Tests that the underlying value field and swatch container both exist.
   */
  public function testColorBoxStructureRenders(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists(
      'field_test[0][color_boxes_optional][value]'
    );
    $this->assertStringContainsString(
      'visually-hidden',
      (string) $field->getAttribute('class')
    );
    $this->assertEquals('7', $field->getAttribute('maxlength'));

    // Swatch container that JS attaches the blotches into.
    $assert->elementExists('css', '.custom-field-color-box-container');
  }

  /**
   * Tests that a submitted value persists uppercase through save/reload.
   */
  public function testColorValuePersists(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Color boxes node',
      'field_test[0][color_boxes_optional][value]' => '#ff5733',
      'field_test[0][color_boxes_required][value]' => '#00ff00',
    ], 'Save');

    $node = $this->loadNodeByTitle('Color boxes node');
    $this->assertEquals(
      '#FF5733',
      $node->get('field_test')->color_boxes_optional
    );

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals(
      'field_test[0][color_boxes_optional][value]',
      '#FF5733'
    );
  }

  /**
   * Tests that an empty value stores NULL, unlike the native color widget.
   */
  public function testEmptyValueStoresNull(): void {
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Empty color boxes node',
      'field_test[0][color_boxes_optional][value]' => '',
      'field_test[0][color_boxes_required][value]' => '#000000',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty color boxes node');
    $value = $node->get('field_test')->color_boxes_optional;
    $this->assertTrue($value === NULL || $value === '');
  }

  /**
   * Tests the CSS class added when required with no existing default.
   *
   * Exists so client-side HTML5 validation doesn't silently block save
   * on a visually-hidden required field with no visible indication why.
   */
  public function testRequiredWithNoDefaultAddsValidationClass(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists(
      'field_test[0][color_boxes_required][value]'
    );
    $this->assertStringContainsString(
      'color_field_widget_box__color',
      (string) $field->getAttribute('class')
    );
  }

  /**
   * Tests that a required color boxes field is enforced on submit.
   *
   * Optional subfield is filled so only the required empty value fails.
   */
  public function testRequiredColorBoxesValidation(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Required color boxes node',
      'field_test[0][color_boxes_optional][value]' => '#abcdef',
      'field_test[0][color_boxes_required][value]' => '',
    ], 'Save');
    $assert->pageTextNotContains('Required color boxes node has been created');

    $this->submitForm([
      'field_test[0][color_boxes_required][value]' => '#00ff00',
    ], 'Save');

    $node = $this->loadNodeByTitle('Required color boxes node');
    $this->assertEquals(
      '#00FF00',
      $node->get('field_test')->color_boxes_required
    );
  }

}
