<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the text widget.
 *
 * Requires the contrib 'maxlength' module for the maxlength_js coverage.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class TextWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'maxlength',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createCustomField(
      'field_test',
      [
        'text_test' => [
          'name' => 'text_test',
          'type' => 'string',
          'length' => 100,
        ],
      ],
      [
        'text_test' => [
          'label' => 'Text test',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'prefix' => '$',
          'suffix' => ' USD',
          'allowed_values' => [
            ['key' => 'red', 'label' => 'Red'],
            ['key' => 'blue', 'label' => 'Blue'],
          ],
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'text_test' => [
        'type' => 'text',
        'weight' => 0,
        'label' => 'Text test',
        'size' => 60,
        'placeholder' => '',
        'maxlength' => '',
        'maxlength_js' => FALSE,
      ],
    ]);
  }

  /**
   * Tests the size, placeholder and maxlength widget settings.
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();
    $base = self::FIELD_PATH . '[text_test]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');

    $assert->fieldValueEquals($base . '[size]', '60');
    // Defaults to the storage length when no widget maxlength is set.
    $assert->fieldValueEquals($base . '[maxlength]', '100');

    $this->submitForm([
      $base . '[placeholder]' => 'Enter some text',
      $base . '[maxlength]' => 20,
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    $this->drupalGet('node/add/page');
    $field = $assert->fieldExists('field_test[0][text_test]');
    $this->assertEquals('Enter some text', $field->getAttribute('placeholder'));
    // The narrower widget-level maxlength wins over the storage length.
    $this->assertEquals('20', $field->getAttribute('maxlength'));
  }

  /**
   * Tests the maxlength_js setting.
   *
   * Unlike the link widget, this checkbox isn't gated behind any other
   * subfield being enabled - only the maxlength module needs to exist.
   */
  public function testMaxlengthJsSetting(): void {
    $assert = $this->assertSession();
    $path = self::FIELD_PATH . '[text_test][maxlength_js]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');
    $assert->fieldExists($path);

    $this->submitForm([
      $path => TRUE,
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    $this->drupalGet('node/add/page');
    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][text_test]"]',
      'data-maxlength',
      '100'
    );
  }

  /**
   * Tests that the field-level prefix and suffix render on the form.
   */
  public function testPrefixSuffixRender(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->pageTextContains('$');
    $assert->pageTextContains('USD');
  }

  /**
   * Tests that a submitted value persists through save and reload.
   */
  public function testValuePersists(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Text node',
      'field_test[0][text_test]' => 'Hello world',
    ], 'Save');

    $node = $this->loadNodeByTitle('Text node');
    $this->assertEquals('Hello world', $node->get('field_test')->text_test);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals('field_test[0][text_test]', 'Hello world');
  }

  /**
   * Tests that an empty value stores NULL.
   */
  public function testEmptyValueStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty text node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty text node');
    $value = $node->get('field_test')->text_test ?? NULL;
    $this->assertTrue($value === NULL || $value === '');
  }

  /**
   * Tests that a value over the storage length is rejected.
   *
   * Not asserting specific message text: the widget sets '#maxlength'
   * directly on the textfield element (from getMaxLength()), so Drupal
   * core's generic Form API maxlength validation likely rejects this
   * before StringType::getConstraints()'s Length constraint ever gets a
   * chance to fire - only that the oversized value is rejected one way
   * or another is asserted here.
   */
  public function testLengthConstraintRejectsLongValue(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $too_long = str_repeat('a', 101);

    $this->submitForm([
      'title[0][value]' => 'Too long text node',
      'field_test[0][text_test]' => $too_long,
    ], 'Save');

    $assert->pageTextNotContains('Too long text node has been created');
  }

  /**
   * Tests that allowed_values isn't enforced by this widget.
   *
   * TextWidget never reads the 'allowed_values' field setting, and
   * StringType::getConstraints() only ever adds a Length constraint -
   * that setting exists for select-style widgets (Select, Radios,
   * Select or Other), not this one. Written to actually prove that
   * with a real submission rather than assume it from reading the code.
   */
  public function testAllowedValuesNotEnforced(): void {
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Arbitrary value node',
      'field_test[0][text_test]' => 'not in the allowed list',
    ], 'Save');

    $node = $this->loadNodeByTitle('Arbitrary value node');
    $this->assertEquals(
      'not in the allowed list',
      $node->get('field_test')->text_test
    );
  }

  /**
   * Tests that a required text field is enforced.
   *
   * Scoped to its own field rather than setUp()'s shared field_test, so
   * the other tests aren't forced to also submit a value for it.
   */
  public function testRequiredTextValidation(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_text_required',
      [
        'text_required' => [
          'name' => 'text_required',
          'type' => 'string',
          'length' => 100,
        ],
      ],
      [
        'text_required' => [
          'label' => 'Text required',
          'check_empty' => FALSE,
          'required' => TRUE,
          'description' => '',
          'prefix' => '',
          'suffix' => '',
          'allowed_values' => [],
        ],
      ],
    );
    $required_field->setRequired(TRUE)->save();
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    $this->setFormDisplay('field_text_required', [
      'text_required' => [
        'type' => 'text',
        'weight' => 0,
        'label' => 'Text required',
        'size' => 60,
        'placeholder' => '',
        'maxlength' => '',
        'maxlength_js' => FALSE,
      ],
    ]);

    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists('field_text_required[0][text_required]');
    $this->assertStringContainsString(
      'required',
      (string) $field->getAttribute('class')
    );

    $this->submitForm([
      'title[0][value]' => 'Required text node',
    ], 'Save');
    $assert->pageTextNotContains('Required text node has been created');

    $this->submitForm([
      'field_text_required[0][text_required]' => 'Some value',
    ], 'Save');

    $node = $this->loadNodeByTitle('Required text node');
    $this->assertEquals(
      'Some value',
      $node->get('field_text_required')->text_required
    );
  }

}
