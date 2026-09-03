<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the datetime_default widget.
 *
 * Covers date-only and datetime storage types, year_range settings presence,
 * timezone selection when enabled, seconds in submitted time values, empty
 * and required behavior. Form keys follow the custom_field_datetime /
 * custom_field_datetime_date elements: [value][date] and optionally
 * [value][time], plus [timezone] when timezone_enabled is TRUE.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class DateTimeDefaultWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createCustomField(
      'field_test',
      [
        'dt_datetime' => [
          'name' => 'dt_datetime',
          'type' => 'datetime',
          'datetime_type' => 'datetime',
        ],
        'dt_date' => [
          'name' => 'dt_date',
          'type' => 'datetime',
          'datetime_type' => 'date',
        ],
        'dt_tz' => [
          'name' => 'dt_tz',
          'type' => 'datetime',
          'datetime_type' => 'datetime',
        ],
        'dt_seconds' => [
          'name' => 'dt_seconds',
          'type' => 'datetime',
          'datetime_type' => 'datetime',
        ],
      ],
      [
        'dt_datetime' => [
          'label' => 'Datetime basic',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'timezone_enabled' => FALSE,
          'timezone_options' => [],
          'seconds_enabled' => FALSE,
        ],
        'dt_date' => [
          'label' => 'Date only',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'timezone_enabled' => FALSE,
          'timezone_options' => [],
          'seconds_enabled' => FALSE,
        ],
        'dt_tz' => [
          'label' => 'Datetime with timezone',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'timezone_enabled' => TRUE,
          'timezone_options' => [
            'America/Chicago',
            'America/Denver',
          ],
          'seconds_enabled' => FALSE,
        ],
        'dt_seconds' => [
          'label' => 'Datetime with seconds',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'timezone_enabled' => FALSE,
          'timezone_options' => [],
          'seconds_enabled' => TRUE,
        ],
      ],
    );

    $widget_defaults = [
      'type' => 'datetime_default',
      'year_range' => '1900:2050',
    ];

    $this->setFormDisplay('field_test', [
      'dt_datetime' => [
        'weight' => 0,
        'label' => 'Datetime basic',
      ] + $widget_defaults,
      'dt_date' => [
        'weight' => 1,
        'label' => 'Date only',
      ] + $widget_defaults,
      'dt_tz' => [
        'weight' => 2,
        'label' => 'Datetime with timezone',
      ] + $widget_defaults,
      'dt_seconds' => [
        'weight' => 3,
        'label' => 'Datetime with seconds',
      ] + $widget_defaults,
    ]);
  }

  /**
   * Converts a local-timezone datetime string to the UTC storage format.
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
   * Tests year_range appears on the widget settings form.
   *
   * Year_range uses custom_field_date_year_range (not a plain textfield), so
   * presence is asserted via the element label rather than a field name.
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');

    $assert->pageTextContains('Year range');
  }

  /**
   * Tests datetime type renders date and time inputs.
   */
  public function testDatetimeRendersDateAndTimeElements(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][dt_datetime][value][date]"]',
      'type',
      'date'
    );
    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][dt_datetime][value][time]"]',
      'type',
      'time'
    );
  }

  /**
   * Tests date-only type renders a native date input without time.
   */
  public function testDateOnlyRendersDateElement(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][dt_date][value][date]"]',
      'type',
      'date'
    );
    $assert->fieldNotExists('field_test[0][dt_date][value][time]');
  }

  /**
   * Tests a datetime value persists through save and reload.
   */
  public function testDatetimeValuePersists(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Datetime node',
      'field_test[0][dt_datetime][value][date]' => '2026-08-15',
      'field_test[0][dt_datetime][value][time]' => '09:00:00',
    ], 'Save');

    $node = $this->loadNodeByTitle('Datetime node');
    $this->assertEquals(
      $this->localToStorageDatetime('2026-08-15 09:00:00'),
      $node->get('field_test')->dt_datetime
    );

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldExists('field_test[0][dt_datetime][value][date]');
  }

  /**
   * Tests a date-only value persists without a time component.
   */
  public function testDateOnlyValuePersists(): void {
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Date only node',
      'field_test[0][dt_date][value][date]' => '2026-08-15',
    ], 'Save');

    $node = $this->loadNodeByTitle('Date only node');
    $this->assertEquals('2026-08-15', $node->get('field_test')->dt_date);
  }

  /**
   * Tests timezone select renders and is restricted when enabled.
   */
  public function testTimezoneSelectWhenEnabled(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->fieldExists('field_test[0][dt_tz][timezone]');
    $assert->optionExists('field_test[0][dt_tz][timezone]', 'America/Chicago');
    $assert->optionExists('field_test[0][dt_tz][timezone]', 'America/Denver');
    // Restricted list should not include arbitrary zones.
    $assert->optionNotExists('field_test[0][dt_tz][timezone]', 'Europe/London');

    // Disabled subfield has no timezone select.
    $assert->fieldNotExists('field_test[0][dt_datetime][timezone]');
  }

  /**
   * Tests timezone value persists with the datetime value.
   */
  public function testTimezoneValuePersists(): void {
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Timezone node',
      'field_test[0][dt_tz][value][date]' => '2026-08-15',
      'field_test[0][dt_tz][value][time]' => '09:00:00',
      'field_test[0][dt_tz][timezone]' => 'America/Denver',
    ], 'Save');

    $node = $this->loadNodeByTitle('Timezone node');
    $this->assertNotEmpty($node->get('field_test')->dt_tz);
    $this->assertEquals(
      'America/Denver',
      $node->get('field_test')->dt_tz__timezone
    );
  }

  /**
   * Tests seconds are accepted when seconds_enabled is TRUE.
   */
  public function testSecondsEnabledAllowsSecondsInValue(): void {
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Seconds node',
      'field_test[0][dt_seconds][value][date]' => '2026-08-15',
      'field_test[0][dt_seconds][value][time]' => '09:00:30',
    ], 'Save');

    $node = $this->loadNodeByTitle('Seconds node');
    $stored = $node->get('field_test')->dt_seconds;
    $this->assertNotEmpty($stored);
    // Seconds should survive into UTC storage (may shift hour by TZ).
    $this->assertStringContainsString(':30', (string) $stored);
  }

  /**
   * Tests empty value stores NULL.
   */
  public function testEmptyValueStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty datetime node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty datetime node');
    foreach (['dt_datetime', 'dt_date', 'dt_tz', 'dt_seconds'] as $subfield) {
      $value = $node->get('field_test')->{$subfield} ?? NULL;
      $this->assertTrue(
        $value === NULL || $value === '',
        sprintf('%s should be empty.', $subfield)
      );
    }
  }

  /**
   * Tests that a required datetime field is enforced.
   */
  public function testRequiredDatetimeValidation(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_dt_required',
      [
        'dt_required' => [
          'name' => 'dt_required',
          'type' => 'datetime',
          'datetime_type' => 'date',
        ],
      ],
      [
        'dt_required' => [
          'label' => 'Datetime required',
          'check_empty' => FALSE,
          'required' => TRUE,
          'description' => '',
          'timezone_enabled' => FALSE,
          'timezone_options' => [],
          'seconds_enabled' => FALSE,
        ],
      ],
    );
    $required_field->setRequired(TRUE)->save();
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    $this->setFormDisplay('field_dt_required', [
      'dt_required' => [
        'type' => 'datetime_default',
        'weight' => 0,
        'label' => 'Datetime required',
        'year_range' => '1900:2050',
      ],
    ]);

    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Required datetime node',
    ], 'Save');
    $assert->pageTextNotContains('Required datetime node has been created');

    $this->submitForm([
      'field_dt_required[0][dt_required][value][date]' => '2026-08-15',
    ], 'Save');

    $node = $this->loadNodeByTitle('Required datetime node');
    $this->assertEquals(
      '2026-08-15',
      $node->get('field_dt_required')->dt_required
    );
  }

}
