<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the duration widget.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class DurationWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createCustomField(
      'field_test',
      [
        'dur_options' => [
          'name' => 'dur_options',
          'type' => 'duration',
        ],
        'dur_input' => [
          'name' => 'dur_input',
          'type' => 'duration',
        ],
      ],
      [
        'dur_options' => [
          'label' => 'Duration options',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'duration_options' => [
            ['key' => 86400, 'label' => '1 day'],
            ['key' => 604800, 'label' => '1 week'],
          ],
        ],
        'dur_input' => [
          'label' => 'Duration input',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'duration_options' => [
            ['key' => 86400, 'label' => '1 day'],
            ['key' => 604800, 'label' => '1 week'],
          ],
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'dur_options' => [
        'type' => 'duration',
        'weight' => 0,
        'label' => 'Duration options',
        'duration_element' => 'options',
      ],
      'dur_input' => [
        'type' => 'duration',
        'weight' => 1,
        'label' => 'Duration input',
        'duration_element' => 'input',
      ],
    ]);
  }

  /**
   * Tests the duration_element widget setting.
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();
    $base = self::FIELD_PATH . '[dur_options][duration_element]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');
    $assert->fieldValueEquals($base, 'options');

    $this->submitForm([
      $base => 'input',
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    $this->drupalGet('node/add/page');
    $assert->fieldExists('field_test[0][dur_options][days]');
  }

  /**
   * Tests options-mode element renders as a select.
   *
   * Options come from the configured duration_options setting.
   */
  public function testOptionsModeRendersSelect(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->optionExists('field_test[0][dur_options]', '86400');
    $assert->optionExists('field_test[0][dur_options]', '604800');
  }

  /**
   * Tests that selecting a pre-defined option persists its key.
   */
  public function testOptionsModeValuePersists(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Options node',
      'field_test[0][dur_options]' => '604800',
    ], 'Save');

    $node = $this->loadNodeByTitle('Options node');
    $this->assertEquals(604800, $node->get('field_test')->dur_options);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals('field_test[0][dur_options]', '604800');
  }

  /**
   * Tests that leaving the options select unselected stores NULL.
   */
  public function testOptionsModeEmptyStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty options node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty options node');
    $value = $node->get('field_test')->dur_options ?? NULL;
    $this->assertTrue($value === NULL || $value === '');
  }

  /**
   * Tests stale duration_options values are silently lost on resave.
   *
   * DurationWidget::widget() only sets #default_value when the stored
   * value is a key in the current $options array (built fresh from the
   * duration_options field setting on every render) - there's no
   * fallback for a value that doesn't match, so #default_value simply
   * becomes NULL. Practical effect: if a site builder ever changes
   * duration_options after content has been saved, any existing value
   * no longer in the list silently shows "- Select -" on edit, and
   * resaving without touching the dropdown erases it.
   */
  public function testOptionsModeStaleValueIsSilentlyLost(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Stale value node',
      'field_test[0][dur_options]' => '86400',
    ], 'Save');

    $node = $this->loadNodeByTitle('Stale value node');
    $this->assertEquals(86400, $node->get('field_test')->dur_options);

    // Change the allowed options so the stored value (86400) is no
    // longer among them.
    $this->updateFieldSettings('field_test', [
      'dur_options' => [
        'duration_options' => [
          ['key' => 3600, 'label' => '1 hour'],
        ],
      ],
    ]);

    $this->drupalGet('node/' . $node->id() . '/edit');
    // The select shows no selection at all, despite the stored value
    // still genuinely being 86400 in the database.
    $assert->fieldValueEquals('field_test[0][dur_options]', '');

    // Resaving without touching the dropdown wipes the value.
    $this->submitForm([], 'Save');
    $node = $this->reloadNode($node->id());
    $value = $node->get('field_test')->dur_options ?? NULL;
    $this->assertTrue($value === NULL || $value === '');
  }

  /**
   * Tests that the input-mode element renders days/hours/minutes fields.
   */
  public function testInputModeRendersDaysHoursMinutes(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->fieldExists('field_test[0][dur_input][days]');
    $assert->fieldExists('field_test[0][dur_input][hours]');
    $assert->fieldExists('field_test[0][dur_input][minutes]');
  }

  /**
   * Tests that submitted days/hours/minutes persist as total seconds.
   */
  public function testInputModeValuePersists(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Input node',
      'field_test[0][dur_input][days]' => '1',
      'field_test[0][dur_input][hours]' => '2',
      'field_test[0][dur_input][minutes]' => '30',
    ], 'Save');

    $node = $this->loadNodeByTitle('Input node');
    // 1 day + 2 hours + 30 minutes = 86400 + 7200 + 1800.
    $this->assertEquals(95400, $node->get('field_test')->dur_input);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals('field_test[0][dur_input][days]', '1');
    $assert->fieldValueEquals('field_test[0][dur_input][hours]', '2');
    $assert->fieldValueEquals('field_test[0][dur_input][minutes]', '30');
  }

  /**
   * Tests that blank input-mode fields store 0, not NULL.
   *
   * Duration::valueCallback() computes days*86400 + hours*3600 +
   * minutes*60 from whatever numeric values are submitted, defaulting
   * missing parts to 0 - an all-blank submission computes to literal
   * 0, and massageFormValue()'s is_numeric() check passes for 0, so it
   * stores as a real zero rather than being treated as empty.
   */
  public function testInputModeAllBlankStoresZero(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Blank input node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Blank input node');
    $this->assertEquals(0, $node->get('field_test')->dur_input);
  }

  /**
   * Tests that a required duration rejects an explicit zero value.
   *
   * Duration::validateDuration() rejects a value of exactly 0 when the
   * element is #required, with "must be greater than zero" - a
   * required duration has to be a genuinely positive time span, not
   * just "not empty" the way most other widgets treat 0.
   *
   * Scoped to its own field rather than setUp()'s shared field_test, so
   * the other tests aren't forced to also submit a value for it.
   */
  public function testRequiredInputModeRejectsZero(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_dur_required',
      [
        'dur_required' => [
          'name' => 'dur_required',
          'type' => 'duration',
        ],
      ],
      [
        'dur_required' => [
          'label' => 'Duration required',
          'check_empty' => FALSE,
          'required' => TRUE,
          'description' => '',
          'duration_options' => [
            ['key' => 86400, 'label' => '1 day'],
          ],
        ],
      ],
    );
    $required_field->setRequired(TRUE)->save();
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    $this->setFormDisplay('field_dur_required', [
      'dur_required' => [
        'type' => 'duration',
        'weight' => 0,
        'label' => 'Duration required',
        'duration_element' => 'input',
      ],
    ]);

    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Zero duration node',
    ], 'Save');

    $assert->pageTextContains('must be greater than zero');
    $assert->pageTextNotContains('Zero duration node has been created');

    $this->submitForm([
      'field_dur_required[0][dur_required][hours]' => '1',
    ], 'Save');

    $node = $this->loadNodeByTitle('Zero duration node');
    $this->assertEquals(3600, $node->get('field_dur_required')->dur_required);
  }

}
