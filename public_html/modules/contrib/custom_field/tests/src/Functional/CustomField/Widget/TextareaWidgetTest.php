<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the textarea widget.
 *
 * Covers plain string_long (unformatted) settings, rows/placeholder/maxlength,
 * maxlength_js when the maxlength module is present, persist, empty, and
 * required. Formatted (text_format) path is covered lightly for element type
 * and value round-trip when formatted is enabled on the subfield.
 *
 * Requires the contrib 'maxlength' module for the maxlength_js coverage.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class TextareaWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'maxlength',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createCustomField(
      'field_test',
      [
        'ta_plain' => [
          'name' => 'ta_plain',
          'type' => 'string_long',
        ],
        'ta_formatted' => [
          'name' => 'ta_formatted',
          'type' => 'string_long',
        ],
      ],
      [
        'ta_plain' => [
          'label' => 'Plain textarea',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'formatted' => FALSE,
          'default_format' => '',
          'format' => [
            'guidelines' => TRUE,
            'help' => TRUE,
          ],
        ],
        'ta_formatted' => [
          'label' => 'Formatted textarea',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'formatted' => TRUE,
          'default_format' => 'plain_text',
          'format' => [
            'guidelines' => FALSE,
            'help' => FALSE,
          ],
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'ta_plain' => [
        'type' => 'textarea',
        'weight' => 0,
        'label' => 'Plain textarea',
        'rows' => 5,
        'placeholder' => '',
        'maxlength' => '',
        'maxlength_js' => FALSE,
      ],
      'ta_formatted' => [
        'type' => 'textarea',
        'weight' => 1,
        'label' => 'Formatted textarea',
        'rows' => 3,
        'placeholder' => '',
        'maxlength' => '',
        'maxlength_js' => FALSE,
      ],
    ]);
  }

  /**
   * Tests rows, placeholder, and maxlength widget settings.
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();
    $base = self::FIELD_PATH . '[ta_plain]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');

    $assert->fieldValueEquals($base . '[rows]', '5');
    $assert->fieldValueEquals($base . '[placeholder]', '');
    $assert->fieldExists($base . '[maxlength]');
    $assert->fieldExists($base . '[maxlength_js]');

    $this->submitForm([
      $base . '[rows]' => 8,
      $base . '[placeholder]' => 'Enter long text',
      $base . '[maxlength]' => 50,
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    $this->drupalGet('node/add/page');
    $field = $assert->fieldExists('field_test[0][ta_plain]');
    $this->assertEquals('8', $field->getAttribute('rows'));
    $this->assertEquals('Enter long text', $field->getAttribute('placeholder'));
    $this->assertEquals('50', $field->getAttribute('data-maxlength'));
  }

  /**
   * Tests maxlength_js enables the data-maxlength attribute path.
   */
  public function testMaxlengthJsSetting(): void {
    $assert = $this->assertSession();
    $path = self::FIELD_PATH . '[ta_plain][maxlength_js]';
    $max_path = self::FIELD_PATH . '[ta_plain][maxlength]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');

    $this->submitForm([
      $max_path => 100,
      $path => TRUE,
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    $this->drupalGet('node/add/page');
    $assert->elementAttributeContains(
      'css',
      'textarea[name="field_test[0][ta_plain]"]',
      'data-maxlength',
      '100'
    );
  }

  /**
   * Tests plain textarea renders as a textarea element.
   */
  public function testPlainRendersTextarea(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->elementExists('css', 'textarea[name="field_test[0][ta_plain]"]');
  }

  /**
   * Tests a plain textarea value persists through save and reload.
   */
  public function testPlainValuePersists(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Textarea node',
      'field_test[0][ta_plain]' => "Line one\nLine two",
    ], 'Save');

    $node = $this->loadNodeByTitle('Textarea node');
    $this->assertEquals(
      "Line one\nLine two",
      $node->get('field_test')->ta_plain
    );

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals(
      'field_test[0][ta_plain]',
      "Line one\nLine two"
    );
  }

  /**
   * Tests empty plain textarea stores NULL (trim of empty string).
   */
  public function testEmptyValueStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty textarea node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty textarea node');
    $value = $node->get('field_test')->ta_plain ?? NULL;
    $this->assertTrue($value === NULL || $value === '');
  }

  /**
   * Tests whitespace-only value is treated as empty by massageFormValue().
   */
  public function testWhitespaceOnlyStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Whitespace textarea node',
      'field_test[0][ta_plain]' => "   \n  ",
    ], 'Save');

    $node = $this->loadNodeByTitle('Whitespace textarea node');
    $value = $node->get('field_test')->ta_plain ?? NULL;
    $this->assertTrue($value === NULL || $value === '');
  }

  /**
   * Tests formatted string_long uses text_format and persists body text.
   *
   * Form key for text_format is [value] under the subfield. Format select
   * may be restricted to default_format when configured.
   */
  public function testFormattedValuePersists(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    // text_format nests the body under [value].
    $assert->fieldExists('field_test[0][ta_formatted][value]');

    $this->submitForm([
      'title[0][value]' => 'Formatted textarea node',
      'field_test[0][ta_formatted][value]' => 'Formatted body text',
    ], 'Save');

    $node = $this->loadNodeByTitle('Formatted textarea node');
    $this->assertEquals(
      'Formatted body text',
      $node->get('field_test')->ta_formatted
    );
  }

  /**
   * Tests required plain textarea validation.
   */
  public function testRequiredTextareaValidation(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_ta_required',
      [
        'ta_required' => [
          'name' => 'ta_required',
          'type' => 'string_long',
        ],
      ],
      [
        'ta_required' => [
          'label' => 'Textarea required',
          'check_empty' => FALSE,
          'required' => TRUE,
          'description' => '',
          'formatted' => FALSE,
          'default_format' => '',
          'format' => [
            'guidelines' => TRUE,
            'help' => TRUE,
          ],
        ],
      ],
    );
    $required_field->setRequired(TRUE)->save();
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    $this->setFormDisplay('field_ta_required', [
      'ta_required' => [
        'type' => 'textarea',
        'weight' => 0,
        'label' => 'Textarea required',
        'rows' => 5,
        'placeholder' => '',
        'maxlength' => '',
        'maxlength_js' => FALSE,
      ],
    ]);

    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Required textarea node',
    ], 'Save');
    $assert->pageTextNotContains('Required textarea node has been created');

    $this->submitForm([
      'field_ta_required[0][ta_required]' => 'Required content',
    ], 'Save');

    $node = $this->loadNodeByTitle('Required textarea node');
    $this->assertEquals(
      'Required content',
      $node->get('field_ta_required')->ta_required
    );
  }

}
