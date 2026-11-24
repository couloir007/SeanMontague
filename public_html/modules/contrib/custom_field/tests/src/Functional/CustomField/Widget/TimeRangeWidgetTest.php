<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the time_range widget.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class TimeRangeWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createCustomField(
      'field_test',
      [
        'range_optional' => [
          'name' => 'range_optional',
          'type' => 'time_range',
        ],
        'range_required_end' => [
          'name' => 'range_required_end',
          'type' => 'time_range',
        ],
      ],
      [
        'range_optional' => [
          'label' => 'Optional end range',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'seconds_enabled' => TRUE,
          'seconds_step' => 5,
        ],
        'range_required_end' => [
          'label' => 'Required end range',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'seconds_enabled' => FALSE,
          'seconds_step' => 5,
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'range_optional' => [
        'type' => 'time_range',
        'weight' => 0,
        'label' => 'Optional end range',
        'start_label' => 'Start time',
        'end_label' => 'End time',
        'time_end_required' => FALSE,
      ],
      'range_required_end' => [
        'type' => 'time_range',
        'weight' => 1,
        'label' => 'Required end range',
        'start_label' => 'Start time',
        'end_label' => 'End time',
        'time_end_required' => TRUE,
      ],
    ]);
  }

  /**
   * Tests the start_label, end_label and time_end_required settings.
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();
    $base = self::FIELD_PATH . '[range_optional]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');

    $assert->fieldValueEquals($base . '[start_label]', 'Start time');
    $assert->fieldValueEquals($base . '[end_label]', 'End time');
    $assert->checkboxNotChecked($base . '[time_end_required]');

    $this->submitForm([
      $base . '[start_label]' => 'Opens at',
      $base . '[end_label]' => 'Closes at',
      $base . '[time_end_required]' => TRUE,
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    $this->drupalGet('node/add/page');
    $assert->pageTextContains('Opens at');
    $assert->pageTextContains('Closes at');

    // time_end_required was enabled via the settings form: start without end
    // must be rejected on this subfield.
    $this->submitForm([
      'title[0][value]' => 'Settings end-required node',
      'field_test[0][range_optional][value]' => '09:00',
    ], 'Save');
    $assert->pageTextContains('end time is required');
    $assert->pageTextNotContains('Settings end-required node has been created');
  }

  /**
   * Tests that both the start and end fields render as native time inputs.
   */
  public function testTimeRangeElementType(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][range_optional][value]"]',
      'type',
      'time'
    );
    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][range_optional][end_value]"]',
      'type',
      'time'
    );
  }

  /**
   * Tests that the step attribute follows seconds_enabled for both fields.
   *
   * The seconds_enabled setting is field-level (inherited from
   * TimeType, not overridden by TimeRangeType), so it applies uniformly
   * to the whole range subfield, not independently per start/end.
   */
  public function testStepAttributeAppliesToBothFields(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    // seconds_enabled TRUE → step on start and end.
    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][range_optional][value]"]',
      'step',
      '5'
    );
    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][range_optional][end_value]"]',
      'step',
      '5'
    );

    // seconds_enabled FALSE → no step attribute on either input.
    $assert->elementAttributeNotExists(
      'css',
      'input[name="field_test[0][range_required_end][value]"]',
      'step'
    );
    $assert->elementAttributeNotExists(
      'css',
      'input[name="field_test[0][range_required_end][end_value]"]',
      'step'
    );
  }

  /**
   * Tests edit display drops seconds when seconds_enabled is FALSE.
   *
   * Mirrors TimeWidgetTest: storage may retain second precision from the
   * submitted string, but formatForWidget(FALSE) shows H:i only.
   */
  public function testDisplayFormatDropsSecondsWhenDisabled(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Range no-seconds display node',
      'field_test[0][range_required_end][value]' => '09:05:45',
      'field_test[0][range_required_end][end_value]' => '17:30:15',
    ], 'Save');

    $node = $this->loadNodeByTitle('Range no-seconds display node');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals(
      'field_test[0][range_required_end][value]',
      '09:05'
    );
    $assert->fieldValueEquals(
      'field_test[0][range_required_end][end_value]',
      '17:30'
    );
  }

  /**
   * Tests that a valid start/end pair persists correctly.
   *
   * Includes the duration computed by CustomItem::preSave() (end - start, only
   * reachable here since validateStartEnd() already guarantees end is strictly
   * after start for any submission that reaches save).
   */
  public function testStartAndEndPersist(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Range node',
      'field_test[0][range_optional][value]' => '09:00',
      'field_test[0][range_optional][end_value]' => '17:00',
    ], 'Save');

    $node = $this->loadNodeByTitle('Range node');
    $this->assertEquals(32400, $node->get('field_test')->range_optional);
    $this->assertEquals(
      61200,
      $node->get('field_test')->range_optional__end
    );
    // 17:00 - 09:00 = 8 hours.
    $this->assertEquals(
      28800,
      $node->get('field_test')->range_optional__duration
    );

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals(
      'field_test[0][range_optional][value]',
      '09:00:00'
    );
    $assert->fieldValueEquals(
      'field_test[0][range_optional][end_value]',
      '17:00:00'
    );
  }

  /**
   * Tests that a start time alone is valid when the end isn't required.
   *
   * Also confirms duration stays NULL when there's no end time -
   * CustomItem::preSave() only computes it inside the branch where an
   * end_time was successfully parsed.
   */
  public function testStartOnlyValidWhenEndNotRequired(): void {
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Start only node',
      'field_test[0][range_optional][value]' => '09:00',
    ], 'Save');

    $node = $this->loadNodeByTitle('Start only node');
    $this->assertEquals(32400, $node->get('field_test')->range_optional);
    $end = $node->get('field_test')->range_optional__end ?? NULL;
    $this->assertTrue($end === NULL || $end === '');
    $duration = $node->get('field_test')->range_optional__duration ?? NULL;
    $this->assertTrue($duration === NULL || $duration === '');
  }

  /**
   * Tests that a start time alone is rejected when the end is required.
   */
  public function testEndRequiredValidation(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Missing end node',
      'field_test[0][range_required_end][value]' => '09:00',
    ], 'Save');

    $assert->pageTextContains('end time is required');
    $assert->pageTextNotContains('Missing end node has been created');
  }

  /**
   * Tests that an end time before the start time is rejected.
   */
  public function testEndBeforeStartRejected(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'End before start node',
      'field_test[0][range_optional][value]' => '17:00',
      'field_test[0][range_optional][end_value]' => '09:00',
    ], 'Save');

    $assert->pageTextContains('end time must be after the start time');
    $assert->pageTextNotContains('End before start node has been created');
  }

  /**
   * Tests that an equal start and end time is rejected.
   *
   * The validateStartEnd() method compares timestamps with >=, not >,
   * so a zero-duration range is rejected the same way an
   * end-before-start range is - not treated as a valid, if unusual,
   * zero-length range.
   */
  public function testEqualStartAndEndRejected(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Equal times node',
      'field_test[0][range_optional][value]' => '12:00',
      'field_test[0][range_optional][end_value]' => '12:00',
    ], 'Save');

    $assert->pageTextContains('end time must be after the start time');
    $assert->pageTextNotContains('Equal times node has been created');
  }

  /**
   * Tests that an end time without a start time is rejected.
   */
  public function testEndWithoutStartRejected(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'End only node',
      'field_test[0][range_optional][end_value]' => '17:00',
    ], 'Save');

    $assert->pageTextContains('cannot have an end time with no start time');
    $assert->pageTextNotContains('End only node has been created');
  }

  /**
   * Tests that both fields empty stores NULL for the whole subfield.
   */
  public function testEmptyBothStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty range node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty range node');
    $value = $node->get('field_test')->range_optional ?? NULL;
    $this->assertTrue($value === NULL || $value === '');
  }

  /**
   * Tests that a required time range blocks a fully empty submission.
   *
   * Scoped to its own field rather than setUp()'s shared field_test, so
   * the other tests aren't forced to also submit a value for it.
   *
   * The validateStartEnd() method itself unconditionally returns with
   * no error when both start and end are empty, and doesn't reference
   * #required anywhere - so this rejection comes from some other,
   * generic Drupal required-field check rather than anything in
   * TimeRangeWidget's own #element_validate logic. Confirmed by an
   * actual test run: an earlier version of this test assumed the
   * opposite (that emptiness would pass through unblocked) and was
   * disproven - the node was never created.
   */
  public function testRequiredTimeRangeBlocksEmptySubmission(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_range_required',
      [
        'range_required' => [
          'name' => 'range_required',
          'type' => 'time_range',
        ],
      ],
      [
        'range_required' => [
          'label' => 'Range required',
          'check_empty' => FALSE,
          'required' => TRUE,
          'description' => '',
          'seconds_enabled' => FALSE,
          'seconds_step' => 5,
        ],
      ],
    );
    $required_field->setRequired(TRUE)->save();
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    $this->setFormDisplay('field_range_required', [
      'range_required' => [
        'type' => 'time_range',
        'weight' => 0,
        'label' => 'Range required',
        'start_label' => 'Start time',
        'end_label' => 'End time',
        'time_end_required' => FALSE,
      ],
    ]);

    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty required range node',
    ], 'Save');

    $assert->pageTextNotContains(
      'Empty required range node has been created'
    );
  }

  /**
   * Tests that a required time range accepts and stores a real value.
   *
   * Uses a fresh field rather than reusing the one from
   * testRequiredTimeRangeBlocksEmptySubmission(), for clean isolation
   * between the two.
   */
  public function testRequiredTimeRangeAcceptsValue(): void {
    $required_field = $this->createCustomField(
      'field_range_required_filled',
      [
        'range_required_filled' => [
          'name' => 'range_required_filled',
          'type' => 'time_range',
        ],
      ],
      [
        'range_required_filled' => [
          'label' => 'Range required filled',
          'check_empty' => FALSE,
          'required' => TRUE,
          'description' => '',
          'seconds_enabled' => FALSE,
          'seconds_step' => 5,
        ],
      ],
    );
    $required_field->setRequired(TRUE)->save();
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    $this->setFormDisplay('field_range_required_filled', [
      'range_required_filled' => [
        'type' => 'time_range',
        'weight' => 0,
        'label' => 'Range required filled',
        'start_label' => 'Start time',
        'end_label' => 'End time',
        'time_end_required' => FALSE,
      ],
    ]);

    $this->drupalGet('node/add/page');
    $field_name = 'field_range_required_filled[0]'
      . '[range_required_filled][value]';
    $this->submitForm([
      'title[0][value]' => 'Filled required range node',
      $field_name => '09:00',
    ], 'Save');

    $node = $this->loadNodeByTitle('Filled required range node');
    $this->assertEquals(
      32400,
      $node->get('field_range_required_filled')->range_required_filled
    );
  }

}
