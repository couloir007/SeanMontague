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
 * FunctionalJavascript tests for the map_key_value widget.
 *
 * Covers AJAX Add more, filling key/value pairs, and saving.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class MapKeyValueWidgetTest extends WebDriverTestBase {

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
        'kv' => [
          'name' => 'kv',
          'type' => 'map',
        ],
      ],
      [
        'kv' => [
          'label' => 'Key value map',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'kv' => [
        'type' => 'map_key_value',
        'weight' => 0,
        'label' => 'Key value map',
        'key_label' => 'Key',
        'value_label' => 'Value',
      ],
    ]);
  }

  /**
   * Sets form display for a custom field, including per-subfield widgets.
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
   * Tests AJAX add more, fill pairs, and save.
   */
  public function testAjaxAddMoreAndSave(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $page = $this->getSession()->getPage();
    $page->fillField('title[0][value]', 'JS map kv node');

    // Add first row.
    $add = $page->find('css', 'input[name$="_add_more"]');
    $this->assertNotNull($add, 'Expected an Add more button for the map widget.');
    $add->press();
    $assert->assertWaitOnAjaxRequest();
    $assert->waitForField('field_test[0][kv][0][items][key]');

    $page->fillField('field_test[0][kv][0][items][key]', 'color');
    $page->fillField('field_test[0][kv][0][items][value]', 'blue');

    // Add second row (re-find button after AJAX).
    $add = $page->find('css', 'input[name$="_add_more"]');
    $this->assertNotNull($add);
    $add->press();
    $assert->assertWaitOnAjaxRequest();
    $assert->waitForField('field_test[0][kv][1][items][key]');

    $page->fillField('field_test[0][kv][1][items][key]', 'size');
    $page->fillField('field_test[0][kv][1][items][value]', 'large');

    $page->pressButton('Save');
    $assert->waitForText('JS map kv node');

    $node = $this->loadNodeByTitle('JS map kv node');
    $pairs = $node->get('field_test')->kv;
    $this->assertIsArray($pairs);
    $this->assertCount(2, $pairs);
    $this->assertEquals('color', $pairs[0]['key']);
    $this->assertEquals('blue', $pairs[0]['value']);
    $this->assertEquals('size', $pairs[1]['key']);
    $this->assertEquals('large', $pairs[1]['value']);
  }

}
