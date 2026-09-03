<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\FunctionalJavascript\CustomField\Widget;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\custom_field\Traits\CustomFieldTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * FunctionalJavascript tests for the daterange_default widget AJAX behavior.
 *
 * Complements DateRangeWidgetTest (BrowserTestBase) which covers non-JS
 * paths. This class exercises the AJAX-driven UI changes for:
 * - all_day checkbox (date vs datetime-local swap, timezone hide, mutual
 *   exclusivity with same_day / duration)
 * - same_day checkbox (end becomes date + time, hides all_day / duration)
 * - duration select (hides/shows end date; custom restores it)
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class DateRangeWidgetTest extends WebDriverTestBase {

  use CustomFieldTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'field_ui',
    'node',
    'custom_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);

    // Avoid TextareaWithSummaryWidget settingsSummary() noise on form display.
    $body = FieldConfig::loadByName('node', 'page', 'body');
    $body?->delete();

    $admin = $this->drupalCreateUser([
      'administer content types',
      'administer node fields',
      'administer node form display',
      'access content',
      'create page content',
      'edit any page content',
    ]);
    $this->drupalLogin($admin);

    $this->createCustomField(
      'field_test',
      [
        // Both interactive checkboxes on one subfield for mutual exclusivity.
        'range_interactive' => [
          'name' => 'range_interactive',
          'type' => 'daterange',
          'datetime_type' => 'datetime',
        ],
        'range_duration' => [
          'name' => 'range_duration',
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
        'range_interactive' => [
          'label' => 'Range interactive',
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
          ],
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
          'seconds_enabled' => FALSE,
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
      'range_interactive' => [
        'weight' => 0,
        'all_day_checkbox' => TRUE,
        'same_day_checkbox' => TRUE,
      ] + $widget_defaults,
      'range_duration' => ['weight' => 1] + $widget_defaults,
      'range_tz' => [
        'weight' => 2,
        'all_day_checkbox' => TRUE,
      ] + $widget_defaults,
    ]);
  }

  /**
   * Sets form display for a custom field, including per-subfield widgets.
   *
   * Mirrors CustomFieldFunctionalTestBase::setFormDisplay() so this JS suite
   * can use the same field setup helpers without extending BrowserTestBase.
   *
   * @param string $field_name
   *   Field machine name.
   * @param array $subfield_widgets
   *   Keyed by subfield name with widget settings.
   * @param string $widget_type
   *   Overall widget plugin id.
   * @param array $widget_settings
   *   Overall widget settings.
   * @param string $bundle
   *   Content type bundle.
   * @param string $mode
   *   Form mode.
   */
  protected function setFormDisplay(
    string $field_name,
    array $subfield_widgets,
    string $widget_type = 'custom_stacked',
    array $widget_settings = [],
    string $bundle = 'page',
    string $mode = 'default',
  ): void {
    $form_display = EntityFormDisplay::load("node.{$bundle}.{$mode}");
    if (!$form_display) {
      $form_display = EntityFormDisplay::create([
        'targetEntityType' => 'node',
        'bundle' => $bundle,
        'mode' => $mode,
        'status' => TRUE,
      ]);
    }

    $settings = $widget_settings + [
      'wrapper' => 'details',
      'open' => TRUE,
      'fields' => $subfield_widgets,
    ];

    $form_display->setComponent($field_name, [
      'type' => $widget_type,
      'weight' => 10,
      'region' => 'content',
      'settings' => $settings,
    ])->save();
  }

  /**
   * Tests all_day AJAX swaps datetime-local for date and hides same_day.
   */
  public function testAllDayCheckboxAjaxSwitchesToDateInputs(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('node/add/page');

    // Initial state: datetime-local for start/end; both checkboxes visible.
    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][range_interactive][value][date]"]',
      'type',
      'datetime-local'
    );
    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][range_interactive][end_value][date]"]',
      'type',
      'datetime-local'
    );
    $assert->fieldExists('field_test[0][range_interactive][all_day]');
    $assert->fieldExists('field_test[0][range_interactive][same_day]');

    $page->checkField('field_test[0][range_interactive][all_day]');
    $assert->assertWaitOnAjaxRequest();

    // After all_day: date-only inputs; same_day removed via #access.
    $assert->waitForElement('css', 'input[name="field_test[0][range_interactive][value][date]"][type="date"]');
    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][range_interactive][value][date]"]',
      'type',
      'date'
    );
    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][range_interactive][end_value][date]"]',
      'type',
      'date'
    );
    $assert->fieldNotExists('field_test[0][range_interactive][same_day]');
  }

  /**
   * Tests unchecking all_day restores datetime-local and same_day.
   */
  public function testAllDayCheckboxUncheckRestoresDatetimeLocal(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('node/add/page');

    $page->checkField('field_test[0][range_interactive][all_day]');
    $assert->assertWaitOnAjaxRequest();
    $assert->waitForElement('css', 'input[name="field_test[0][range_interactive][value][date]"][type="date"]');

    $page->uncheckField('field_test[0][range_interactive][all_day]');
    $assert->assertWaitOnAjaxRequest();
    $assert->waitForElement('css', 'input[name="field_test[0][range_interactive][value][date]"][type="datetime-local"]');

    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][range_interactive][value][date]"]',
      'type',
      'datetime-local'
    );
    $assert->fieldExists('field_test[0][range_interactive][same_day]');
  }

  /**
   * Tests same_day AJAX hides all_day (mutual exclusivity).
   *
   * Primary AJAX contract for same_day is that all_day is removed via #access
   * while same_day is checked, and start stays datetime-local. End markup after
   * same-day rebuild is element-dependent (custom_field_datetime_date may use
   * different child names when #date_time_element is "time"); those details are
   * not asserted here.
   */
  public function testSameDayCheckboxAjaxHidesAllDay(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('node/add/page');

    $assert->fieldExists('field_test[0][range_interactive][all_day]');
    $assert->fieldExists('field_test[0][range_interactive][same_day]');
    $assert->fieldExists('field_test[0][range_interactive][end_value][date]');

    $page->checkField('field_test[0][range_interactive][same_day]');
    $assert->assertWaitOnAjaxRequest();

    // all_day is inaccessible while same_day is checked.
    $assert->fieldNotExists('field_test[0][range_interactive][all_day]');
    $assert->fieldExists('field_test[0][range_interactive][same_day]');
    // Start remains a combined datetime-local input.
    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][range_interactive][value][date]"]',
      'type',
      'datetime-local'
    );

    // End should remain usable. Match any end_value control (date and/or time).
    $end_inputs = $page->findAll('css', 'input[name*="[range_interactive][end_value]"]');
    $this->assertNotEmpty(
      $end_inputs,
      'Expected at least one end_value input after enabling same_day.'
    );
  }

  /**
   * Tests unchecking same_day restores the all_day checkbox.
   */
  public function testSameDayCheckboxUncheckRestoresAllDay(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('node/add/page');

    $page->checkField('field_test[0][range_interactive][same_day]');
    $assert->assertWaitOnAjaxRequest();
    $assert->fieldNotExists('field_test[0][range_interactive][all_day]');

    $page->uncheckField('field_test[0][range_interactive][same_day]');
    $assert->assertWaitOnAjaxRequest();
    $assert->waitForField('field_test[0][range_interactive][all_day]');

    $assert->fieldExists('field_test[0][range_interactive][all_day]');
    $assert->fieldExists('field_test[0][range_interactive][same_day]');
    $assert->fieldExists('field_test[0][range_interactive][end_value][date]');
  }

  /**
   * Tests duration preset hides the end date; Custom restores it.
   */
  public function testDurationSelectAjaxTogglesEndDateVisibility(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('node/add/page');

    // Default is "custom", so end is visible.
    $assert->fieldExists('field_test[0][range_duration][end_value][date]');
    $assert->optionExists('field_test[0][range_duration][duration]', '86400');
    $assert->optionExists('field_test[0][range_duration][duration]', 'custom');

    $page->selectFieldOption('field_test[0][range_duration][duration]', '86400');
    $assert->assertWaitOnAjaxRequest();

    // Preset duration: end field is inaccessible.
    $assert->fieldNotExists('field_test[0][range_duration][end_value][date]');

    $page->selectFieldOption('field_test[0][range_duration][duration]', 'custom');
    $assert->assertWaitOnAjaxRequest();
    $assert->waitForField('field_test[0][range_duration][end_value][date]');

    $assert->fieldExists('field_test[0][range_duration][end_value][date]');
  }

  /**
   * Tests all_day hides the timezone select when timezone_enabled is TRUE.
   */
  public function testAllDayCheckboxHidesTimezoneSelect(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('node/add/page');

    $assert->fieldExists('field_test[0][range_tz][timezone]');
    $assert->fieldExists('field_test[0][range_tz][all_day]');

    $page->checkField('field_test[0][range_tz][all_day]');
    $assert->assertWaitOnAjaxRequest();
    $assert->waitForElement('css', 'input[name="field_test[0][range_tz][value][date]"][type="date"]');

    $assert->fieldNotExists('field_test[0][range_tz][timezone]');

    $page->uncheckField('field_test[0][range_tz][all_day]');
    $assert->assertWaitOnAjaxRequest();
    $assert->waitForField('field_test[0][range_tz][timezone]');

    $assert->fieldExists('field_test[0][range_tz][timezone]');
  }

  /**
   * Tests all_day hides the duration select when both features are active.
   *
   * Configures duration + all_day on one subfield via display settings update
   * is not required here: range_duration has no all_day checkbox. Instead we
   * verify duration is hidden on the interactive range only when all_day or
   * same_day would gate it — covered indirectly by access rules on the
   * interactive subfield when duration is also enabled.
   *
   * This test enables duration on range_interactive at runtime through field
   * settings so the duration element participates in the same AJAX wrapper.
   */
  public function testAllDayAndSameDayHideDurationSelect(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Enable duration on the interactive subfield so duration shares the
    // AJAX wrapper with the checkboxes.
    $this->updateFieldSettings('field_test', [
      'range_interactive' => [
        'duration_enabled' => TRUE,
        'duration_options' => [
          ['key' => 86400, 'label' => '1 day'],
        ],
      ],
    ]);

    $this->drupalGet('node/add/page');

    $assert->fieldExists('field_test[0][range_interactive][duration]');
    $assert->fieldExists('field_test[0][range_interactive][all_day]');
    $assert->fieldExists('field_test[0][range_interactive][same_day]');

    $page->checkField('field_test[0][range_interactive][all_day]');
    $assert->assertWaitOnAjaxRequest();
    $assert->fieldNotExists('field_test[0][range_interactive][duration]');
    $assert->fieldNotExists('field_test[0][range_interactive][same_day]');

    $page->uncheckField('field_test[0][range_interactive][all_day]');
    $assert->assertWaitOnAjaxRequest();
    $assert->waitForField('field_test[0][range_interactive][duration]');
    $assert->fieldExists('field_test[0][range_interactive][duration]');
    $assert->fieldExists('field_test[0][range_interactive][same_day]');

    $page->checkField('field_test[0][range_interactive][same_day]');
    $assert->assertWaitOnAjaxRequest();
    $assert->fieldNotExists('field_test[0][range_interactive][duration]');
    $assert->fieldNotExists('field_test[0][range_interactive][all_day]');
  }

}
