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
 * FunctionalJavascript tests for the color_boxes widget.
 *
 * Complements ColorBoxesWidgetTest (BrowserTestBase) which covers settings,
 * structure, persist, and required validation without JS. This class covers
 * client-side box selection and the transparent (clear) blotch.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class ColorBoxesWidgetTest extends WebDriverTestBase {

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
        'color_boxes_optional' => [
          'name' => 'color_boxes_optional',
          'type' => 'color',
        ],
        'color_boxes_required' => [
          'name' => 'color_boxes_required',
          'type' => 'color',
        ],
      ],
      [
        'color_boxes_optional' => [
          'label' => 'Optional color boxes',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
        ],
        'color_boxes_required' => [
          'label' => 'Required color boxes',
          'check_empty' => FALSE,
          'required' => TRUE,
          'description' => '',
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'color_boxes_optional' => [
        'type' => 'color_boxes',
        'weight' => 0,
        'label' => 'Optional color boxes',
        // Small fixed palette so tests can target a known swatch.
        'default_colors' => '#ff0000,#00ff00,#0000ff',
      ],
      'color_boxes_required' => [
        'type' => 'color_boxes',
        'weight' => 1,
        'label' => 'Required color boxes',
        'default_colors' => '#ff0000,#00ff00,#0000ff',
      ],
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
   * Tests that clicking a color box sets the hidden input value.
   */
  public function testClickingColorBoxSetsHiddenValue(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('node/add/page');

    $assert->waitForElement('css', '.custom-field-color-box-container .custom_field_color_box__square');

    // Scope to the optional field's container (first color-box container).
    $container = $page->find('css', '.custom-field-color-box-container');
    $this->assertNotNull($container);

    $red = $container->find('css', 'button.custom_field_color_box__square[value="#ff0000"], button.custom_field_color_box__square[value="#FF0000"]');
    // Palette may be lower or mixed case depending on settings path.
    if ($red === NULL) {
      $red = $container->find('css', 'button.custom_field_color_box__square[color="#ff0000"], button.custom_field_color_box__square[color="#FF0000"]');
    }
    $this->assertNotNull($red, 'Expected a red color blotch in the optional palette.');
    $red->click();

    $assert->fieldValueEquals('field_test[0][color_boxes_optional][value]', '#ff0000');
    $this->assertTrue($red->hasClass('active'));
    $this->assertEquals('true', $red->getAttribute('aria-checked'));
  }

  /**
   * Tests that the transparent blotch clears the value on optional fields.
   *
   * Required fields omit the transparent blotch (addTransparentBlotch is
   * false when the subfield is required).
   */
  public function testTransparentBlotchClearsValue(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('node/add/page');

    $assert->waitForElement('css', '.custom-field-color-box-container .custom_field_color_box__square');

    $containers = $page->findAll('css', '.custom-field-color-box-container');
    $this->assertGreaterThanOrEqual(2, count($containers));

    // Optional field: first container should include transparent blotch.
    $optional = $containers[0];
    $green = $optional->find('css', 'button.custom_field_color_box__square[value="#00ff00"], button.custom_field_color_box__square[color="#00ff00"]');
    $this->assertNotNull($green);
    $green->click();
    $assert->fieldValueEquals('field_test[0][color_boxes_optional][value]', '#00ff00');

    $transparent = $optional->find('css', 'button.custom_field_color_box__square--transparent');
    $this->assertNotNull($transparent, 'Optional color boxes should offer a transparent (clear) blotch.');
    $transparent->click();
    $assert->fieldValueEquals('field_test[0][color_boxes_optional][value]', '');

    // Required field: no transparent blotch.
    $required = $containers[1];
    $this->assertNull(
      $required->find('css', 'button.custom_field_color_box__square--transparent'),
      'Required color boxes must not offer a transparent blotch.'
    );
  }

}
