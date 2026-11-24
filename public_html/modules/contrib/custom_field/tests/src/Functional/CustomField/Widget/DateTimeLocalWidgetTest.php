<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the datetime_local widget.
 *
 * Only applicable when datetime_type is "datetime" (see isApplicable()).
 * Uses a single datetime-local input under [value][date]. Covers year_range
 * settings presence, timezone when enabled, seconds in the value, empty and
 * required behavior.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class DateTimeLocalWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createCustomField(
      'field_test',
      [
        'local_basic' => [
          'name' => 'local_basic',
          'type' => 'datetime',
          'datetime_type' => 'datetime',
        ],
        'local_tz' => [
          'name' => 'local_tz',
          'type' => 'datetime',
          'datetime_type' => 'datetime',
        ],
        'local_seconds' => [
          'name' => 'local_seconds',
          'type' => 'datetime',
          'datetime_type' => 'datetime',
        ],
      ],
      [
        'local_basic' => [
          'label' => 'Local basic',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'timezone_enabled' => FALSE,
          'timezone_options' => [],
          'seconds_enabled' => FALSE,
        ],
        'local_tz' => [
          'label' => 'Local with timezone',
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
        'local_seconds' => [
          'label' => 'Local with seconds',
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
      'type' => 'datetime_local',
      'year_range' => '1900:2050',
    ];

    $this->setFormDisplay('field_test', [
      'local_basic' => [
        'weight' => 0,
        'label' => 'Local basic',
      ] + $widget_defaults,
      'local_tz' => [
        'weight' => 1,
        'label' => 'Local with timezone',
      ] + $widget_defaults,
      'local_seconds' => [
        'weight' => 2,
        'label' => 'Local with seconds',
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
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');
    $assert->pageTextContains('Year range');
  }

  /**
   * Tests the element renders as datetime-local.
   */
  public function testRendersDatetimeLocalElement(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][local_basic][value][date]"]',
      'type',
      'datetime-local'
    );
  }

  /**
   * Tests a datetime-local value persists through save and reload.
   */
  public function testValuePersists(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Local datetime node',
      'field_test[0][local_basic][value][date]' => '2026-08-15T09:00',
    ], 'Save');

    $node = $this->loadNodeByTitle('Local datetime node');
    $this->assertEquals(
      $this->localToStorageDatetime('2026-08-15 09:00:00'),
      $node->get('field_test')->local_basic
    );

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldExists('field_test[0][local_basic][value][date]');
  }

  /**
   * Tests timezone select when enabled, absent when disabled.
   */
  public function testTimezoneSelect(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->fieldExists('field_test[0][local_tz][timezone]');
    $assert->optionExists('field_test[0][local_tz][timezone]', 'America/Chicago');
    $assert->optionExists('field_test[0][local_tz][timezone]', 'America/Denver');
    $assert->optionNotExists('field_test[0][local_tz][timezone]', 'Europe/London');
    $assert->fieldNotExists('field_test[0][local_basic][timezone]');
  }

  /**
   * Tests timezone value persists.
   */
  public function testTimezoneValuePersists(): void {
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Local timezone node',
      'field_test[0][local_tz][value][date]' => '2026-08-15T09:00',
      'field_test[0][local_tz][timezone]' => 'America/Denver',
    ], 'Save');

    $node = $this->loadNodeByTitle('Local timezone node');
    $this->assertNotEmpty($node->get('field_test')->local_tz);
    $this->assertEquals(
      'America/Denver',
      $node->get('field_test')->local_tz__timezone
    );
  }

  /**
   * Tests seconds in the datetime-local value when seconds_enabled.
   */
  public function testSecondsEnabledAllowsSecondsInValue(): void {
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Local seconds node',
      'field_test[0][local_seconds][value][date]' => '2026-08-15T09:00:30',
    ], 'Save');

    $node = $this->loadNodeByTitle('Local seconds node');
    $stored = $node->get('field_test')->local_seconds;
    $this->assertNotEmpty($stored);
    $this->assertStringContainsString(':30', (string) $stored);
  }

  /**
   * Tests empty value stores NULL.
   */
  public function testEmptyValueStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty local node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty local node');
    foreach (['local_basic', 'local_tz', 'local_seconds'] as $subfield) {
      $value = $node->get('field_test')->{$subfield} ?? NULL;
      $this->assertTrue(
        $value === NULL || $value === '',
        sprintf('%s should be empty.', $subfield)
      );
    }
  }

  /**
   * Tests required datetime_local validation.
   */
  public function testRequiredLocalValidation(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_local_required',
      [
        'local_required' => [
          'name' => 'local_required',
          'type' => 'datetime',
          'datetime_type' => 'datetime',
        ],
      ],
      [
        'local_required' => [
          'label' => 'Local required',
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

    $this->setFormDisplay('field_local_required', [
      'local_required' => [
        'type' => 'datetime_local',
        'weight' => 0,
        'label' => 'Local required',
        'year_range' => '1900:2050',
      ],
    ]);

    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Required local node',
    ], 'Save');
    $assert->pageTextNotContains('Required local node has been created');

    $this->submitForm([
      'field_local_required[0][local_required][value][date]' => '2026-08-15T10:00',
    ], 'Save');

    $node = $this->loadNodeByTitle('Required local node');
    $this->assertEquals(
      $this->localToStorageDatetime('2026-08-15 10:00:00'),
      $node->get('field_local_required')->local_required
    );
  }

}
