<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the datetime_datelist widget.
 *
 * Covers widget settings (date_order, time_type, increment), date-only vs
 * datetime part sets, 12/24 hour modes, value persist via select parts, empty
 * and required. Datelist form keys are [value][year], [value][month],
 * [value][day], and for datetime also [value][hour], [value][minute], and
 * optionally [value][ampm].
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class DateTimeDatelistWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createCustomField(
      'field_test',
      [
        'dl_datetime' => [
          'name' => 'dl_datetime',
          'type' => 'datetime',
          'datetime_type' => 'datetime',
        ],
        'dl_date' => [
          'name' => 'dl_date',
          'type' => 'datetime',
          'datetime_type' => 'date',
        ],
        'dl_12h' => [
          'name' => 'dl_12h',
          'type' => 'datetime',
          'datetime_type' => 'datetime',
        ],
      ],
      [
        'dl_datetime' => [
          'label' => 'Datelist datetime',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'timezone_enabled' => FALSE,
          'timezone_options' => [],
          'seconds_enabled' => FALSE,
        ],
        'dl_date' => [
          'label' => 'Datelist date only',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'timezone_enabled' => FALSE,
          'timezone_options' => [],
          'seconds_enabled' => FALSE,
        ],
        'dl_12h' => [
          'label' => 'Datelist 12 hour',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'timezone_enabled' => FALSE,
          'timezone_options' => [],
          'seconds_enabled' => FALSE,
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'dl_datetime' => [
        'type' => 'datetime_datelist',
        'weight' => 0,
        'label' => 'Datelist datetime',
        'year_range' => '1900:2050',
        'date_order' => 'YMD',
        'time_type' => '24',
        'increment' => '15',
      ],
      'dl_date' => [
        'type' => 'datetime_datelist',
        'weight' => 1,
        'label' => 'Datelist date only',
        'year_range' => '1900:2050',
        'date_order' => 'MDY',
        'time_type' => 'none',
        'increment' => '15',
      ],
      'dl_12h' => [
        'type' => 'datetime_datelist',
        'weight' => 2,
        'label' => 'Datelist 12 hour',
        'year_range' => '1900:2050',
        'date_order' => 'DMY',
        'time_type' => '12',
        'increment' => '30',
      ],
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
   * Tests datelist widget settings on the form display UI.
   *
   * Time_type and increment are only exposed for datetime storage types;
   * date-only hides them as hidden elements.
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();
    $base = self::FIELD_PATH . '[dl_datetime]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');

    $assert->fieldValueEquals($base . '[date_order]', 'YMD');
    $assert->fieldValueEquals($base . '[time_type]', '24');
    $assert->fieldValueEquals($base . '[increment]', '15');
    // Inherited from DateTimeWidgetBase (not in datelist.md historically).
    $assert->pageTextContains('Year range');

    $this->submitForm([
      $base . '[date_order]' => 'DMY',
      $base . '[time_type]' => '12',
      $base . '[increment]' => '30',
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');
  }

  /**
   * Tests datetime datelist renders year/month/day/hour/minute selects.
   */
  public function testDatetimePartsRender(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    foreach (['year', 'month', 'day', 'hour', 'minute'] as $part) {
      $assert->fieldExists("field_test[0][dl_datetime][value][{$part}]");
    }
    // 24-hour mode has no ampm.
    $assert->fieldNotExists('field_test[0][dl_datetime][value][ampm]');
  }

  /**
   * Tests date-only datelist omits time parts.
   */
  public function testDateOnlyPartsRender(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    foreach (['year', 'month', 'day'] as $part) {
      $assert->fieldExists("field_test[0][dl_date][value][{$part}]");
    }
    $assert->fieldNotExists('field_test[0][dl_date][value][hour]');
    $assert->fieldNotExists('field_test[0][dl_date][value][minute]');
    $assert->fieldNotExists('field_test[0][dl_date][value][ampm]');
  }

  /**
   * Tests 12-hour mode includes ampm and uses the configured increment.
   */
  public function testTwelveHourModeRendersAmpm(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->fieldExists('field_test[0][dl_12h][value][ampm]');
    // Increment 30 → minute options should include 0 and 30.
    $assert->optionExists('field_test[0][dl_12h][value][minute]', '0');
    $assert->optionExists('field_test[0][dl_12h][value][minute]', '30');
  }

  /**
   * Tests a datetime datelist value persists through save.
   */
  public function testDatetimeValuePersists(): void {
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Datelist datetime node',
      'field_test[0][dl_datetime][value][year]' => '2026',
      'field_test[0][dl_datetime][value][month]' => '8',
      'field_test[0][dl_datetime][value][day]' => '15',
      'field_test[0][dl_datetime][value][hour]' => '9',
      'field_test[0][dl_datetime][value][minute]' => '15',
    ], 'Save');

    $node = $this->loadNodeByTitle('Datelist datetime node');
    $this->assertEquals(
      $this->localToStorageDatetime('2026-08-15 09:15:00'),
      $node->get('field_test')->dl_datetime
    );
  }

  /**
   * Tests a date-only datelist value persists as Y-m-d.
   */
  public function testDateOnlyValuePersists(): void {
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Datelist date node',
      'field_test[0][dl_date][value][year]' => '2026',
      'field_test[0][dl_date][value][month]' => '8',
      'field_test[0][dl_date][value][day]' => '15',
    ], 'Save');

    $node = $this->loadNodeByTitle('Datelist date node');
    $this->assertEquals('2026-08-15', $node->get('field_test')->dl_date);
  }

  /**
   * Tests empty datelist stores NULL.
   */
  public function testEmptyValueStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty datelist node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty datelist node');
    foreach (['dl_datetime', 'dl_date', 'dl_12h'] as $subfield) {
      $value = $node->get('field_test')->{$subfield} ?? NULL;
      $this->assertTrue(
        $value === NULL || $value === '',
        sprintf('%s should be empty.', $subfield)
      );
    }
  }

  /**
   * Tests required datelist validation.
   */
  public function testRequiredDatelistValidation(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_dl_required',
      [
        'dl_required' => [
          'name' => 'dl_required',
          'type' => 'datetime',
          'datetime_type' => 'date',
        ],
      ],
      [
        'dl_required' => [
          'label' => 'Datelist required',
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

    $this->setFormDisplay('field_dl_required', [
      'dl_required' => [
        'type' => 'datetime_datelist',
        'weight' => 0,
        'label' => 'Datelist required',
        'year_range' => '1900:2050',
        'date_order' => 'YMD',
        'time_type' => 'none',
        'increment' => '15',
      ],
    ]);

    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Required datelist node',
    ], 'Save');
    $assert->pageTextNotContains('Required datelist node has been created');

    $this->submitForm([
      'field_dl_required[0][dl_required][value][year]' => '2026',
      'field_dl_required[0][dl_required][value][month]' => '8',
      'field_dl_required[0][dl_required][value][day]' => '15',
    ], 'Save');

    $node = $this->loadNodeByTitle('Required datelist node');
    $this->assertEquals(
      '2026-08-15',
      $node->get('field_dl_required')->dl_required
    );
  }

}
