<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the daterange_default widget.
 *
 * Covers the core start/end flow, duration selection, timezone selection
 * (when enabled and when disabled), seconds support, year-range and label
 * widget settings, the allday storage type, and the presence of the all_day /
 * same_day checkboxes. Interactive behavior of the all_day and same_day
 * checkboxes is AJAX-gated (DateRangeWidgetBase::widget() only reacts when
 * the triggering element is the checkbox itself) and is covered by
 * \Drupal\Tests\custom_field\FunctionalJavascript\CustomField\Widget\DateRangeWidgetTest.
 * Presence of the checkboxes themselves is still asserted here.
 *
 * The value/end_value elements use custom_field_datetime(_date), which
 * follow core's date/time sub-key convention. For non-all-day datetime
 * subfields the [value][date] (and [end_value][date]) key holds a
 * combined "Y-m-d\TH:i" or "Y-m-d\TH:i:s" string. Date-only and allday
 * subfields use a plain "Y-m-d" value on the form; allday is stored with
 * fixed 00:00:00 / 23:59:59 times.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class DateRangeWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createCustomField(
      'field_test',
      [
        'range_basic' => [
          'name' => 'range_basic',
          'type' => 'daterange',
          'datetime_type' => 'datetime',
        ],
        'range_date_only' => [
          'name' => 'range_date_only',
          'type' => 'daterange',
          'datetime_type' => 'date',
        ],
        'range_allday' => [
          'name' => 'range_allday',
          'type' => 'daterange',
          'datetime_type' => 'allday',
        ],
        'range_duration' => [
          'name' => 'range_duration',
          'type' => 'daterange',
          'datetime_type' => 'datetime',
        ],
        'range_all_day' => [
          'name' => 'range_all_day',
          'type' => 'daterange',
          'datetime_type' => 'datetime',
        ],
        'range_same_day' => [
          'name' => 'range_same_day',
          'type' => 'daterange',
          'datetime_type' => 'datetime',
        ],
        'range_tz' => [
          'name' => 'range_tz',
          'type' => 'daterange',
          'datetime_type' => 'datetime',
        ],
      ],
      [
        'range_basic' => [
          'label' => 'Range basic',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'timezone_enabled' => FALSE,
          'timezone_options' => [],
          'seconds_enabled' => FALSE,
          'duration_enabled' => FALSE,
          'duration_options' => [],
        ],
        'range_date_only' => [
          'label' => 'Range date only',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'timezone_enabled' => FALSE,
          'timezone_options' => [],
          'seconds_enabled' => FALSE,
          'duration_enabled' => FALSE,
          'duration_options' => [],
        ],
        'range_allday' => [
          'label' => 'Range allday',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'timezone_enabled' => FALSE,
          'timezone_options' => [],
          'seconds_enabled' => FALSE,
          'duration_enabled' => FALSE,
          'duration_options' => [],
        ],
        'range_duration' => [
          'label' => 'Range duration',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'timezone_enabled' => FALSE,
          'timezone_options' => [],
          'seconds_enabled' => FALSE,
          'duration_enabled' => TRUE,
          'duration_options' => [
            ['key' => 86400, 'label' => '1 day'],
            ['key' => 604800, 'label' => '1 week'],
            ['key' => 2592000, 'label' => '1 month'],
          ],
        ],
        'range_all_day' => [
          'label' => 'Range all day',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'timezone_enabled' => FALSE,
          'timezone_options' => [],
          'seconds_enabled' => FALSE,
          'duration_enabled' => FALSE,
          'duration_options' => [],
        ],
        'range_same_day' => [
          'label' => 'Range same day',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'timezone_enabled' => FALSE,
          'timezone_options' => [],
          'seconds_enabled' => FALSE,
          'duration_enabled' => FALSE,
          'duration_options' => [],
        ],
        'range_tz' => [
          'label' => 'Range timezone',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'timezone_enabled' => TRUE,
          'timezone_options' => [
            'America/Chicago',
            'America/Denver',
          ],
          'seconds_enabled' => TRUE,
          'duration_enabled' => FALSE,
          'duration_options' => [],
        ],
      ],
    );

    $widget_defaults = [
      'type' => 'daterange_default',
      'start_label' => 'Start date',
      'end_label' => 'End date',
      'year_range' => '1900:2050',
      'year_range_end' => '1900:2050',
      'date_end_required' => FALSE,
      'all_day_checkbox' => FALSE,
      'same_day_checkbox' => FALSE,
    ];
    $this->setFormDisplay('field_test', [
      // Distinct labels so node-form assertions are unambiguous.
      'range_basic' => [
        'weight' => 0,
        'start_label' => 'Event starts',
        'end_label' => 'Event ends',
      ] + $widget_defaults,
      'range_date_only' => ['weight' => 1] + $widget_defaults,
      'range_allday' => ['weight' => 2] + $widget_defaults,
      'range_duration' => ['weight' => 3] + $widget_defaults,
      'range_all_day' => [
        'weight' => 4,
        'all_day_checkbox' => TRUE,
      ] + $widget_defaults,
      'range_same_day' => [
        'weight' => 5,
        'same_day_checkbox' => TRUE,
      ] + $widget_defaults,
      'range_tz' => ['weight' => 6] + $widget_defaults,
    ]);
  }

  /**
   * Converts a local-timezone datetime string to the UTC storage format.
   *
   * MassageFormValue() converts the submitted value from the site's
   * default timezone to UTC before storage. Expected values therefore
   * need this conversion rather than a 1:1 mapping of the typed input.
   *
   * @param string $local
   *   A "Y-m-d H:i:s" formatted string in the site's default timezone.
   *
   * @return string
   *   The equivalent "Y-m-d\TH:i:s" formatted string in UTC.
   */
  protected function localToStorageDatetime(string $local): string {
    $site_timezone = new \DateTimeZone(date_default_timezone_get());
    $date = new \DateTime($local, $site_timezone);
    $date->setTimezone(new \DateTimeZone('UTC'));
    return $date->format('Y-m-d\TH:i:s');
  }

  /**
   * Tests that a valid start/end pair persists correctly.
   */
  public function testStartAndEndPersist(): void {
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Range node',
      'field_test[0][range_basic][value][date]' => '2026-08-15T09:00',
      'field_test[0][range_basic][end_value][date]' => '2026-08-16T17:00',
    ], 'Save');

    $node = $this->loadNodeByTitle('Range node');
    $this->assertEquals(
      $this->localToStorageDatetime('2026-08-15 09:00:00'),
      $node->get('field_test')->range_basic
    );
    $this->assertEquals(
      $this->localToStorageDatetime('2026-08-16 17:00:00'),
      $node->get('field_test')->range_basic__end
    );
  }

  /**
   * Tests that an end date before the start date is rejected.
   */
  public function testEndBeforeStartRejected(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'End before start node',
      'field_test[0][range_basic][value][date]' => '2026-08-16T09:00',
      'field_test[0][range_basic][end_value][date]' => '2026-08-15T09:00',
    ], 'Save');

    $assert->pageTextContains('end date must be after the start date');
    $assert->pageTextNotContains('End before start node has been created');
  }

  /**
   * Tests that an end date without a start date is rejected.
   */
  public function testEndWithoutStartRejected(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'End only node',
      'field_test[0][range_basic][end_value][date]' => '2026-08-16T09:00',
    ], 'Save');

    $assert->pageTextContains('cannot have an end date with no start date');
    $assert->pageTextNotContains('End only node has been created');
  }

  /**
   * Tests that a start date alone is rejected when the end is required.
   */
  public function testEndRequiredValidation(): void {
    $assert = $this->assertSession();

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');
    $this->submitForm([
      self::FIELD_PATH . '[range_basic][date_end_required]' => TRUE,
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Missing end node',
      'field_test[0][range_basic][value][date]' => '2026-08-15T09:00',
    ], 'Save');

    $assert->pageTextContains('end date is required');
    $assert->pageTextNotContains('Missing end node has been created');
  }

  /**
   * Tests start_label, end_label and checkbox widget settings.
   *
   * Verifies defaults on the form-display settings form, updates labels, and
   * confirms the new labels appear on the node form.
   *
   * year_range / year_range_end use the custom_field_date_year_range element
   * (not a plain text input), so their values are not asserted by form field
   * name here. They remain configured via setFormDisplay() and applied as
   * #date_year_range on the widget elements.
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();
    $base = self::FIELD_PATH . '[range_basic]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');

    $assert->fieldValueEquals($base . '[start_label]', 'Event starts');
    $assert->fieldValueEquals($base . '[end_label]', 'Event ends');
    $assert->checkboxNotChecked($base . '[date_end_required]');
    $assert->checkboxNotChecked($base . '[all_day_checkbox]');
    $assert->checkboxNotChecked($base . '[same_day_checkbox]');
    // Year range settings are present on the form (labels from the element).
    $assert->pageTextContains('Year range start');
    $assert->pageTextContains('Year range end');

    // range_all_day was configured with all_day_checkbox enabled.
    $all_day_base = self::FIELD_PATH . '[range_all_day]';
    $assert->checkboxChecked($all_day_base . '[all_day_checkbox]');

    $this->submitForm([
      $base . '[start_label]' => 'Opens at',
      $base . '[end_label]' => 'Closes at',
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    $this->drupalGet('node/add/page');
    $assert->pageTextContains('Opens at');
    $assert->pageTextContains('Closes at');
  }

  /**
   * Tests that configured start/end labels render on the node form.
   */
  public function testStartAndEndLabelsRender(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->pageTextContains('Event starts');
    $assert->pageTextContains('Event ends');
  }

  /**
   * Tests that a date-only subfield renders as a native date input.
   */
  public function testDateOnlyRendersDateElement(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][range_date_only][value][date]"]',
      'type',
      'date'
    );
  }

  /**
   * Tests that a date-only value persists without a time component.
   */
  public function testDateOnlyValuePersists(): void {
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Date only node',
      'field_test[0][range_date_only][value][date]' => '2026-08-15',
      'field_test[0][range_date_only][end_value][date]' => '2026-08-20',
    ], 'Save');

    $node = $this->loadNodeByTitle('Date only node');
    $this->assertEquals(
      '2026-08-15',
      $node->get('field_test')->range_date_only
    );
    $this->assertEquals(
      '2026-08-20',
      $node->get('field_test')->range_date_only__end
    );
  }

  /**
   * Tests that an allday storage type renders native date inputs.
   *
   * Datetime_type = allday is distinct from the all_day_checkbox widget
   * setting: the storage type always uses date-only inputs and forces
   * midnight / end-of-day times on save.
   */
  public function testAllDayStorageTypeRendersDateElement(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][range_allday][value][date]"]',
      'type',
      'date'
    );
    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][range_allday][end_value][date]"]',
      'type',
      'date'
    );
    // All-day storage type does not expose the interactive all_day checkbox.
    $assert->fieldNotExists('field_test[0][range_allday][all_day]');
  }

  /**
   * Tests that an allday storage type persists with fixed start/end times.
   *
   * MassageFormValue() forces 00:00:00 on start and 23:59:59 on end in the
   * storage timezone (UTC) for DATETIME_TYPE_ALLDAY.
   */
  public function testAllDayStorageTypePersists(): void {
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'All day storage node',
      'field_test[0][range_allday][value][date]' => '2026-08-15',
      'field_test[0][range_allday][end_value][date]' => '2026-08-20',
    ], 'Save');

    $node = $this->loadNodeByTitle('All day storage node');
    $this->assertEquals(
      '2026-08-15T00:00:00',
      $node->get('field_test')->range_allday
    );
    $this->assertEquals(
      '2026-08-20T23:59:59',
      $node->get('field_test')->range_allday__end
    );
  }

  /**
   * Tests that selecting a pre-defined duration auto-calculates the end date.
   */
  public function testDurationSelectionAutoCalculatesEnd(): void {
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Duration node',
      'field_test[0][range_duration][value][date]' => '2026-08-15T09:00',
      'field_test[0][range_duration][duration]' => '86400',
    ], 'Save');

    $node = $this->loadNodeByTitle('Duration node');
    $this->assertEquals(
      $this->localToStorageDatetime('2026-08-15 09:00:00'),
      $node->get('field_test')->range_duration
    );
    // 1 day (86400 seconds) after the start.
    $this->assertEquals(
      $this->localToStorageDatetime('2026-08-16 09:00:00'),
      $node->get('field_test')->range_duration__end
    );
  }

  /**
   * Tests that selecting "Custom" allows manually entering the end date.
   */
  public function testDurationCustomAllowsManualEnd(): void {
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Custom duration node',
      'field_test[0][range_duration][value][date]' => '2026-08-15T09:00',
      'field_test[0][range_duration][duration]' => 'custom',
      'field_test[0][range_duration][end_value][date]' => '2026-08-20T12:00',
    ], 'Save');

    $node = $this->loadNodeByTitle('Custom duration node');
    $this->assertEquals(
      $this->localToStorageDatetime('2026-08-20 12:00:00'),
      $node->get('field_test')->range_duration__end
    );
  }

  /**
   * Tests that the configured duration options appear in the select.
   */
  public function testDurationOptionsRender(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->optionExists('field_test[0][range_duration][duration]', '86400');
    $assert->optionExists('field_test[0][range_duration][duration]', '604800');
    $assert->optionExists('field_test[0][range_duration][duration]', '2592000');
    // Widget always appends a "Custom" option.
    $assert->optionExists('field_test[0][range_duration][duration]', 'custom');
  }

  /**
   * Tests that the all_day checkbox itself renders on the form.
   *
   * Interactive all-day behavior is AJAX-dependent and deferred to a
   * FunctionalJavascript test; presence of the checkbox is not.
   */
  public function testAllDayCheckboxRenders(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->fieldExists('field_test[0][range_all_day][all_day]');
  }

  /**
   * Tests that the same_day checkbox itself renders on the form.
   *
   * Interactive same-day behavior is AJAX-dependent and deferred to a
   * FunctionalJavascript test; presence of the checkbox is not.
   */
  public function testSameDayCheckboxRenders(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->fieldExists('field_test[0][range_same_day][same_day]');
  }

  /**
   * Tests that an empty submission stores NULL.
   */
  public function testEmptyValueStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty range node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty range node');
    $value = $node->get('field_test')->range_basic ?? NULL;
    $this->assertTrue($value === NULL || $value === '');
  }

  /**
   * Tests that the timezone select is present when timezone_enabled is TRUE.
   */
  public function testTimezoneSelectRendersWhenEnabled(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->fieldExists('field_test[0][range_tz][timezone]');
  }

  /**
   * Tests that the timezone select is absent when timezone_enabled is FALSE.
   */
  public function testTimezoneSelectAbsentWhenDisabled(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->fieldNotExists('field_test[0][range_basic][timezone]');
    $assert->fieldNotExists('field_test[0][range_date_only][timezone]');
    $assert->fieldNotExists('field_test[0][range_allday][timezone]');
  }

  /**
   * Tests that only the configured timezone_options appear in the select.
   */
  public function testTimezoneOptionsAreRestricted(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->optionExists('field_test[0][range_tz][timezone]', 'America/Chicago');
    $assert->optionExists('field_test[0][range_tz][timezone]', 'America/Denver');
    // Options outside the field settings must not appear.
    $assert->optionNotExists('field_test[0][range_tz][timezone]', 'UTC');
    $assert->optionNotExists('field_test[0][range_tz][timezone]', 'America/New_York');
  }

  /**
   * Tests that a selected timezone is stored on the field item.
   */
  public function testTimezoneValuePersists(): void {
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Timezone node',
      'field_test[0][range_tz][value][date]' => '2026-08-15T09:00:00',
      'field_test[0][range_tz][end_value][date]' => '2026-08-15T17:00:00',
      'field_test[0][range_tz][timezone]' => 'America/Denver',
    ], 'Save');

    $node = $this->loadNodeByTitle('Timezone node');
    $this->assertEquals(
      $this->localToStorageDatetime('2026-08-15 09:00:00'),
      $node->get('field_test')->range_tz
    );
    $this->assertEquals('America/Denver', $node->get('field_test')->range_tz__timezone);
  }

  /**
   * Tests that seconds_enabled allows non-zero seconds in the submitted value.
   *
   * The widget's #show_seconds flag (sourced from the field setting)
   * controls whether the underlying datetime element accepts and preserves
   * a seconds component. Submitting a value with non-zero seconds and
   * asserting the stored UTC string contains those seconds covers the
   * setting end-to-end.
   */
  public function testSecondsEnabledAllowsSecondsInValue(): void {
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Seconds node',
      'field_test[0][range_tz][value][date]' => '2026-08-15T09:00:30',
      'field_test[0][range_tz][end_value][date]' => '2026-08-15T10:00:45',
    ], 'Save');

    $node = $this->loadNodeByTitle('Seconds node');
    $this->assertEquals(
      $this->localToStorageDatetime('2026-08-15 09:00:30'),
      $node->get('field_test')->range_tz
    );
    $this->assertEquals(
      $this->localToStorageDatetime('2026-08-15 10:00:45'),
      $node->get('field_test')->range_tz__end
    );
  }

  /**
   * Tests that a required date range field is enforced.
   *
   * Scoped to its own field rather than setUp()'s shared field_test so
   * the other tests are not forced to also submit a value for it.
   *
   * Note: the second submission path after a failed required validation
   * has historically been flaky in this environment (node not found after
   * the corrective submit). The timezone conversion is retained for the
   * success path once the underlying save issue is resolved.
   */
  public function testRequiredDateRangeValidation(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_range_required',
      [
        'range_required' => [
          'name' => 'range_required',
          'type' => 'daterange',
          'datetime_type' => 'datetime',
        ],
      ],
      [
        'range_required' => [
          'label' => 'Range required',
          'check_empty' => FALSE,
          'required' => TRUE,
          'description' => '',
          'timezone_enabled' => FALSE,
          'timezone_options' => [],
          'seconds_enabled' => FALSE,
          'duration_enabled' => FALSE,
          'duration_options' => [],
        ],
      ],
    );
    $required_field->setRequired(TRUE)->save();
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    $this->setFormDisplay('field_range_required', [
      'range_required' => [
        'type' => 'daterange_default',
        'weight' => 0,
        'label' => 'Range required',
        'start_label' => 'Start date',
        'end_label' => 'End date',
        'year_range' => '1900:2050',
        'year_range_end' => '1900:2050',
        'date_end_required' => FALSE,
        'all_day_checkbox' => FALSE,
        'same_day_checkbox' => FALSE,
      ],
    ]);

    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Required range node',
    ], 'Save');

    $assert->pageTextNotContains('Required range node has been created');

    $field_name = 'field_range_required[0][range_required][value][date]';
    $this->submitForm([
      $field_name => '2026-08-15T09:00',
    ], 'Save');

    $node = $this->loadNodeByTitle('Required range node');
    $this->assertEquals(
      $this->localToStorageDatetime('2026-08-15 09:00:00'),
      $node->get('field_range_required')->range_required
    );
  }

}
