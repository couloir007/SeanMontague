<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the telephone widget.
 *
 * Requires the contrib 'maxlength' module for the maxlength_js coverage.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class TelephoneWidgetTest extends CustomFieldFunctionalTestBase {

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
        'tel_basic' => [
          'name' => 'tel_basic',
          'type' => 'telephone',
        ],
        'tel_pattern' => [
          'name' => 'tel_pattern',
          'type' => 'telephone',
          'length' => 256,
        ],
        'tel_no_length' => [
          'name' => 'tel_no_length',
          'type' => 'telephone',
        ],
      ],
      [
        'tel_basic' => [
          'label' => 'Telephone basic',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'pattern' => '',
        ],
        'tel_pattern' => [
          'label' => 'Telephone pattern',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'pattern' => 'US',
        ],
        'tel_no_length' => [
          'label' => 'Telephone no length',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'pattern' => '',
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'tel_basic' => [
        'type' => 'telephone',
        'weight' => 0,
        'label' => 'Telephone basic',
        'size' => 60,
        'placeholder' => '',
        'maxlength' => 20,
        'maxlength_js' => TRUE,
      ],
      'tel_pattern' => [
        'type' => 'telephone',
        'weight' => 1,
        'label' => 'Telephone pattern',
        'size' => 60,
        'placeholder' => '',
        'maxlength' => '',
        'maxlength_js' => FALSE,
      ],
      'tel_no_length' => [
        'type' => 'telephone',
        'weight' => 2,
        'label' => 'Telephone no length',
        'size' => 60,
        'placeholder' => '',
        'maxlength' => '',
        'maxlength_js' => FALSE,
      ],
    ]);
  }

  /**
   * Tests placeholder and size widget settings via form display.
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();
    $base = self::FIELD_PATH . '[tel_basic]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');

    $assert->fieldValueEquals($base . '[size]', '60');
    $assert->fieldValueEquals($base . '[placeholder]', '');

    $this->submitForm([
      $base . '[size]' => '40',
      $base . '[placeholder]' => '555-000-0000',
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    $this->drupalGet('node/add/page');
    $field = $assert->fieldExists('field_test[0][tel_basic]');
    $this->assertEquals('40', $field->getAttribute('size'));
    $this->assertEquals('555-000-0000', $field->getAttribute('placeholder'));
  }

  /**
   * Tests that the field renders as a native tel input.
   */
  public function testTelElementType(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][tel_basic]"]',
      'type',
      'tel'
    );
    // Default size from setUp / widget defaults.
    $this->assertEquals(
      '60',
      $assert->fieldExists('field_test[0][tel_basic]')->getAttribute('size')
    );
  }

  /**
   * Tests that the widget-level maxlength setting is respected.
   *
   * This assumes TelephoneWidget::widget() no longer unconditionally
   * overwrites '#maxlength' with TelephoneType::MAX_LENGTH after calling
   * parent::widget() - once that override is removed, the inherited
   * TextWidget logic correctly narrows '#maxlength' to the widget
   * setting when it's smaller than the storage ceiling. tel_basic is
   * configured with maxlength => 20 in setUp() to prove this.
   */
  public function testMaxlengthSettingIsRespected(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists('field_test[0][tel_basic]');
    $this->assertEquals('20', $field->getAttribute('maxlength'));
  }

  /**
   * Tests maxlength with an explicit storage length configured.
   *
   * The tel_pattern's storage columns set length => 256 in setUp(), matching
   * what the storage settings form's Length field actually saves for a
   * telephone field (confirmed via a screenshot of that form - its
   * default value is 256). getMaxlength() reads this directly from
   * $this->settings['length'] when it's non-empty, so it returns 256
   * here without touching the MAX_LENGTH constant fallback at all.
   */
  public function testMaxlengthWithExplicitStorageLength(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists('field_test[0][tel_pattern]');
    $this->assertEquals('256', $field->getAttribute('maxlength'));
  }

  /**
   * Tests maxlength when no storage length was set at all.
   *
   * The tel_no_length's storage columns omit 'length' entirely in setUp() -
   * only reachable via direct API/programmatic field creation, not
   * through the storage settings form (which always saves some value,
   * apparently defaulting to 256 - see
   * testMaxlengthWithExplicitStorageLength()). With
   * $this->settings['length'] unset, getMaxlength() falls through to
   * self::MAX_LENGTH inside CustomFieldTypeBase, which is 255 - not
   * TelephoneType's own MAX_LENGTH constant (256), since self:: doesn't
   * follow late static binding to the runtime subclass.
   */
  public function testMaxlengthFallsBackWhenStorageLengthUnset(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists('field_test[0][tel_no_length]');
    $this->assertEquals('255', $field->getAttribute('maxlength'));
  }

  /**
   * Tests the maxlength_js data-maxlength attribute.
   *
   * TelephoneWidget doesn't override widgetSettingsForm(), so
   * maxlength_js is fully inherited from TextWidget - same as there,
   * it's gated only by the maxlength module existing, with no
   * title-field condition. tel_basic has maxlength_js enabled with
   * maxlength => 20 in setUp(), so data-maxlength should reflect the
   * narrowed value, not the 255 storage ceiling.
   */
  public function testMaxlengthJsSetting(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][tel_basic]"]',
      'data-maxlength',
      '20'
    );
  }

  /**
   * Tests that a configured pattern renders the HTML5 pattern attribute.
   */
  public function testPatternAttributeAndDescription(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists('field_test[0][tel_pattern]');
    $this->assertEquals(
      '[0-9]{3}-[0-9]{3}-[0-9]{4}',
      $field->getAttribute('pattern')
    );
    $assert->pageTextContains('xxx-xxx-xxxx');
  }

  /**
   * Tests that a custom description overrides the pattern format hint.
   */
  public function testCustomDescriptionOverridesPatternHint(): void {
    $this->updateFieldSettings('field_test', [
      'tel_pattern' => [
        'description' => 'Custom help text for this field.',
      ],
    ]);

    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->pageTextContains('Custom help text for this field.');
    $assert->pageTextNotContains('xxx-xxx-xxxx');
  }

  /**
   * Tests that a value not matching the configured pattern still saves.
   *
   * No #element_validate is attached to the pattern attribute, and
   * TelephoneType inherits StringType::getConstraints() unmodified
   * (Length only) - the country format is a client-side/cosmetic hint
   * only. Proven here with a real submission rather than assumed.
   */
  public function testPatternNotEnforcedServerSide(): void {
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Non matching pattern node',
      'field_test[0][tel_pattern]' => 'not a us phone number',
    ], 'Save');

    $node = $this->loadNodeByTitle('Non matching pattern node');
    $this->assertEquals(
      'not a us phone number',
      $node->get('field_test')->tel_pattern
    );
  }

  /**
   * Tests that a submitted value persists through save and reload.
   */
  public function testValuePersists(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Telephone node',
      'field_test[0][tel_basic]' => '555-123-4567',
    ], 'Save');

    $node = $this->loadNodeByTitle('Telephone node');
    $this->assertEquals('555-123-4567', $node->get('field_test')->tel_basic);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals('field_test[0][tel_basic]', '555-123-4567');
  }

  /**
   * Tests that an empty value stores NULL.
   */
  public function testEmptyValueStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty telephone node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty telephone node');
    $value = $node->get('field_test')->tel_basic ?? NULL;
    $this->assertTrue($value === NULL || $value === '');
  }

  /**
   * Tests that a required telephone field is enforced.
   *
   * Scoped to its own field rather than setUp()'s shared field_test, so
   * the other tests aren't forced to also submit a value for it.
   */
  public function testRequiredTelephoneValidation(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_tel_required',
      [
        'tel_required' => [
          'name' => 'tel_required',
          'type' => 'telephone',
        ],
      ],
      [
        'tel_required' => [
          'label' => 'Telephone required',
          'check_empty' => FALSE,
          'required' => TRUE,
          'description' => '',
          'pattern' => '',
        ],
      ],
    );
    $required_field->setRequired(TRUE)->save();
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    $this->setFormDisplay('field_tel_required', [
      'tel_required' => [
        'type' => 'telephone',
        'weight' => 0,
        'label' => 'Telephone required',
        'size' => 60,
        'placeholder' => '',
        'maxlength' => '',
        'maxlength_js' => FALSE,
      ],
    ]);

    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists('field_tel_required[0][tel_required]');
    $this->assertStringContainsString(
      'required',
      (string) $field->getAttribute('class')
    );

    $this->submitForm([
      'title[0][value]' => 'Required telephone node',
    ], 'Save');
    $assert->pageTextNotContains('Required telephone node has been created');

    $this->submitForm([
      'field_tel_required[0][tel_required]' => '555-123-4567',
    ], 'Save');

    $node = $this->loadNodeByTitle('Required telephone node');
    $this->assertEquals(
      '555-123-4567',
      $node->get('field_tel_required')->tel_required
    );
  }

}
