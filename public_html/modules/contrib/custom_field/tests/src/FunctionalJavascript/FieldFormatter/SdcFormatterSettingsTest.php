<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\FunctionalJavascript\FieldFormatter;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the SingleDirectoryComponentFormatter settings form.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
final class SdcFormatterSettingsTest extends FormatterJavascriptTestBase {

  /**
   * Selector for an element that only exists once the settings form renders.
   */
  private const READY_CSS = 'select[name*="[component]"]';

  /**
   * The component selected in these tests.
   */
  private const COMPONENT_ID = 'sdc_test:my-banner';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'custom_field_test',
    'custom_field_sdc_test',
    'node',
    'field_ui',
    'sdc_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->loadDisplay()->setComponent($this->fieldName, [
      'type' => 'custom_field_sdc',
      'label' => 'above',
      'settings' => [
        'component' => '',
        'variant' => '',
        'props' => [],
        'slots' => [],
      ],
      'weight' => 1,
      'region' => 'content',
    ])->save();

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that the settings form renders correctly with no component selected.
   */
  public function testSettingsFormRendersWithNoComponent(): void {
    $this->openFormatterSettings(self::READY_CSS);

    $session = $this->assertSession();
    $session->elementNotExists('css', 'details[id*="edit-slots"]');
    $session->elementNotExists('css', 'details[id*="edit-props"]');
  }

  /**
   * Tests that selecting a component via AJAX reveals prop settings.
   */
  public function testSelectingComponentRevealsPropSettings(): void {
    $component = $this->openFormatterSettings(self::READY_CSS);
    $this->selectOption($component, self::COMPONENT_ID);

    $this->waitForVisible(
      'details[id*="props"]',
      'Prop settings did not appear after selecting a component.',
    );
  }

  /**
   * Tests that selecting a component with slots reveals slot configuration.
   */
  public function testSelectingComponentWithSlotsRevealsSlotSettings(): void {
    $component = $this->openFormatterSettings(self::READY_CSS);
    $this->selectOption($component, self::COMPONENT_ID);

    $this->waitForVisible(
      'details[id*="slots"]',
      'Slot settings did not appear after selecting a component.',
    );
  }

  /**
   * Tests that changing component back to empty resets slot and prop settings.
   */
  public function testChangingComponentResetsSettings(): void {
    $display = $this->loadDisplay();
    $settings = $display->getComponent($this->fieldName);
    $settings['settings']['component'] = self::COMPONENT_ID;
    $display->setComponent($this->fieldName, $settings)->save();

    $component = $this->openFormatterSettings(self::READY_CSS);
    $this->selectOption($component, '');

    $session = $this->assertSession();
    $session->elementNotExists('css', 'details[id*="slots"]');
    $session->elementNotExists('css', 'details[id*="props"]');
  }

  /**
   * Tests that formatter settings save correctly through the form.
   */
  public function testFormatterSettingsSaveThroughForm(): void {
    $component = $this->openFormatterSettings(self::READY_CSS);
    $this->selectOption($component, self::COMPONENT_ID);

    $this->getSession()->getPage()->pressButton('Save');

    // pressButton() returns when the click is dispatched, not when the request
    // completes, so wait for the response before reading the display back.
    $this->waitForVisible(
      '[data-drupal-messages]',
      'Save did not complete: no status message appeared.',
    );

    $settings = $this->loadDisplay()->getComponent($this->fieldName);
    $this->assertSame(self::COMPONENT_ID, $settings['settings']['component']);
  }

}
