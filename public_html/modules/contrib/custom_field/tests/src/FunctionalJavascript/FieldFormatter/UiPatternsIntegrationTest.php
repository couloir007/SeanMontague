<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\FunctionalJavascript\FieldFormatter;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests custom field formatters nested under UI Patterns Component per item.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
final class UiPatternsIntegrationTest extends FormatterJavascriptTestBase {

  /**
   * The slot under test, as it appears in form element names.
   */
  private const SLOT = '[slots][subheading]';

  /**
   * Selector for an element that only exists once the settings form renders.
   */
  private const READY_CSS = 'select[name*="[component_id]"]';

  /**
   * The component exposing the slot under test.
   */
  private const COMPONENT_ID = 'custom_field_sdc_test:card';

  /**
   * The subfield whose format_type is toggled.
   */
  private const SUBFIELD = 'string';


  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'custom_field_test',
    'custom_field_sdc_test',
    'ui_patterns',
    'ui_patterns_field_formatters',
    'node',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // The ui_patterns module declares php >=8.3.0 in its composer.json. On
    // lower versions Composer resolves to 2.0.19, which predates that
    // constraint and triggers a fatal error at compile time in
    // EntityReferencedSource: an enum property fetch inside a plugin
    // attribute is PHP 8.2+ syntax. The error text is written into the AJAX
    // response body, so the request returns HTTP 200 with a payload that is
    // not valid JSON and the form never rebuilds. Rather than test whichever
    // older release Composer falls back to, skip below the version
    // ui_patterns supports.
    if (version_compare(PHP_VERSION, '8.3.0', '<')) {
      $this->markTestSkipped('ui_patterns requires PHP 8.3 or higher.');
    }

    $this->loadDisplay()->setComponent($this->fieldName, [
      'type' => 'ui_patterns_component_per_item',
      'label' => 'above',
      'settings' => [],
      'weight' => 1,
      'region' => 'content',
    ])->save();

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Path 1: [Field] Formatter → custom_formatter → format_type AJAX.
   */
  public function testFieldFormatterSourceAndSubfieldAjax(): void {
    $this->openComponentSlotSettings();

    // Select source [Field] Formatter (an empty slot uses add_more_button).
    $source = $this->waitForVisible(
      'select[name*="' . self::SLOT . '[add_more_button]"]',
      'Slot source select did not appear.',
    );
    $this->selectOptionMatching(
      $source,
      static fn (string $value): bool => str_contains($value, 'field_formatter'),
      'No field_formatter option in slot source select.',
    );

    $this->selectCustomFormatter();
    $this->assertSubfieldFormatTypeAjax();
  }

  /**
   * Path 2: [Entity] → [Field] → field_test → Field Formatter → formatter.
   */
  public function testEntityFieldSourceAndSubfieldAjax(): void {
    $this->openComponentSlotSettings();

    // Select source [Entity] → [Field] (entity_field).
    $source = $this->waitForVisible(
      'select[name*="' . self::SLOT . '[add_more_button]"]',
      'Slot source select did not appear.',
    );
    $this->selectOptionMatching(
      $source,
      static fn (string $value): bool => $value === 'entity_field',
      'No entity_field option in slot source select.',
    );

    // Select our custom field. The element name is
    // …[slots][subheading][sources][0][source][derivable_context] and the
    // options look like field:node:BUNDLE:field_test.
    $field_select = $this->waitForVisible(
      'select[name*="' . self::SLOT . '"][name*="[source][derivable_context]"]',
      'Expected Field (derivable_context) select after entity_field source.',
    );
    $this->selectOptionMatching(
      $field_select,
      fn (string $value): bool => str_ends_with($value, ':' . $this->fieldName),
      'No option ending with :' . $this->fieldName . ' in Field select.',
    );

    // Nested source: [Field] Formatter.
    $nested_source = $this->waitForVisible(
      'select[name*="' . self::SLOT . '"][name*="[source_id]"]',
      'Expected nested source select after choosing field.',
    );
    $this->selectOptionMatching(
      $nested_source,
      static fn (string $value): bool => str_contains($value, 'field_formatter'),
      'No field_formatter option in nested source select.',
    );

    $this->selectCustomFormatter();
    $this->assertSubfieldFormatTypeAjax();
  }

  /**
   * Opens the formatter settings, selects the component and opens the slot.
   */
  private function openComponentSlotSettings(): void {
    // Select the component, which has slot "subheading".
    $component = $this->openFormatterSettings(self::READY_CSS);
    $this->selectOption($component, self::COMPONENT_ID);

    $this->clickElement('details[data-drupal-selector*="-slots-subheading"] > summary');
  }

  /**
   * Selects the Default (custom_formatter) formatter for the slot source.
   */
  private function selectCustomFormatter(): void {
    $type = $this->waitForVisible(
      'select[name*="' . self::SLOT . '"][name*="[source][type]"]',
      'Formatter type select did not appear.',
    );
    $this->selectOption($type, 'custom_formatter');
  }

  /**
   * Asserts the subfield format_type select rebuilds its settings via AJAX.
   */
  private function assertSubfieldFormatTypeAjax(): void {
    $page = $this->getSession()->getPage();

    // Field settings visible.
    $this->waitForVisible(
      'table.form-fields-settings-table',
      'Custom field subfield settings table did not appear.',
    );

    // Open the subfield details. Its data-drupal-selector is the stem every
    // control in the row is named from, so capturing it scopes the lookups
    // below to this subfield rather than matching the first element in
    // document order. The id attribute carries a random suffix regenerated on
    // each rebuild, so data-drupal-selector is the stable hook. The suffix
    // match is exact enough to exclude both the nested "-wrappers" details and
    // the sibling "string_long" row.
    $details = $this->waitForVisible(
      'table.form-fields-settings-table details[data-drupal-selector$="-fields-' . self::SUBFIELD . '"]',
      'Subfield details for "' . self::SUBFIELD . '" did not appear.',
    );
    $stem = (string) $details->getAttribute('data-drupal-selector');
    $this->assertNotEmpty($stem, 'Subfield details has no data-drupal-selector.');
    $this->clickElement('details[data-drupal-selector="' . $stem . '"] > summary');

    $format_css = '[data-drupal-selector="' . $stem . '-format-type"]';
    $settings_css = '[data-drupal-selector="' . $stem . '-formatter-settings"]';

    $format = $this->waitForVisible(
      $format_css,
      "Subfield format_type select did not appear for: $stem",
    );
    $format_name = (string) $format->getAttribute('name');
    $this->assertStringContainsString(self::SLOT, $format_name);
    $this->assertStringContainsString('[format_type]', $format_name);

    // Subfields expose different format types, so the non-hidden value is read
    // from the form rather than hard-coded; only "hidden" is common to all.
    $initial_format = (string) $format->getValue();
    $this->assertNotSame(
      'hidden',
      $initial_format,
      "Subfield $stem starts hidden; expected a rendering format type.",
    );

    // The settings wrapper is the AJAX target and is replaced on each change,
    // so it is re-queried rather than held as a NodeElement.
    $this->assertNotEmpty(
      $this->findSettingsControls($settings_css),
      'Default format type should show formatter settings controls.',
    );

    $page->selectFieldOption($format_name, 'hidden');
    $this->waitForAjax();
    $this->assertEmpty(
      $this->findSettingsControls($settings_css),
      'Hidden format type should not render formatter settings controls.',
    );

    $page->selectFieldOption($format_name, $initial_format);
    $this->waitForAjax();
    $this->assertNotEmpty(
      $this->findSettingsControls($settings_css),
      "Format type '$initial_format' should restore formatter settings controls after AJAX.",
    );
  }

  /**
   * Returns the form controls inside a subfield's formatter settings wrapper.
   *
   * @param string $settings_css
   *   Exact selector for the wrapper.
   *
   * @return array
   *   The controls found, empty when the wrapper renders nothing.
   */
  private function findSettingsControls(string $settings_css): array {
    $wrapper = $this->getSession()->getPage()->find('css', $settings_css);
    $this->assertNotNull($wrapper, "Formatter settings wrapper not found: $settings_css");

    return $wrapper->findAll('css', 'input, select, fieldset');
  }

}
