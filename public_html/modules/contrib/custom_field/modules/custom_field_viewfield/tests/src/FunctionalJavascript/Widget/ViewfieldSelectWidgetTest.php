<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field_viewfield\FunctionalJavascript\Widget;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\custom_field\Traits\CustomFieldTestTrait;
use Drupal\views\Entity\View;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * FunctionalJavascript tests for viewfield_select AJAX display options.
 *
 * @group custom_field
 * @group custom_field_viewfield
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[Group('custom_field_viewfield')]
#[RunTestsInSeparateProcesses]
class ViewfieldSelectWidgetTest extends WebDriverTestBase {

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
    'views',
    'custom_field',
    'custom_field_viewfield',
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

    View::create([
      'id' => 'view_one',
      'label' => 'View One',
      'base_table' => 'node_field_data',
      'status' => TRUE,
      'display' => [
        'default' => [
          'id' => 'default',
          'display_title' => 'Master',
          'display_plugin' => 'default',
          'position' => 0,
          'display_options' => [],
        ],
        'page_1' => [
          'id' => 'page_1',
          'display_title' => 'Page One',
          'display_plugin' => 'page',
          'position' => 1,
          'display_options' => [
            'path' => 'view-one-page',
          ],
        ],
      ],
    ])->save();

    View::create([
      'id' => 'view_two',
      'label' => 'View Two',
      'base_table' => 'node_field_data',
      'status' => TRUE,
      'display' => [
        'default' => [
          'id' => 'default',
          'display_title' => 'Master',
          'display_plugin' => 'default',
          'position' => 0,
          'display_options' => [],
        ],
        'page_1' => [
          'id' => 'page_1',
          'display_title' => 'Page Two',
          'display_plugin' => 'page',
          'position' => 1,
          'display_options' => [
            'path' => 'view-two-page',
          ],
        ],
      ],
    ])->save();

    $this->createCustomField(
      'field_test',
      [
        'viewfield_test' => [
          'name' => 'viewfield_test',
          'type' => 'viewfield',
          'target_type' => 'view',
        ],
      ],
      [
        'viewfield_test' => [
          'label' => 'Viewfield test',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'force_default' => 0,
          'allowed_views' => [
            'view_one' => ['default' => 0, 'page_1' => 'page_1'],
            'view_two' => ['default' => 'default', 'page_1' => 'page_1'],
          ],
          'token_browser' => [
            'recursion_limit' => 3,
            'global_types' => FALSE,
          ],
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'viewfield_test' => [
        'type' => 'viewfield_select',
        'weight' => 0,
        'label' => 'Viewfield test',
        'empty_option' => '- None -',
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
   * Tests AJAX populates display options from allowed_views and saves.
   */
  public function testAjaxDisplayOptionsAndSave(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $page = $this->getSession()->getPage();
    $page->fillField('title[0][value]', 'JS viewfield node');

    // No view selected → display is hidden.
    $assert->elementExists(
      'css',
      'input[type="hidden"][name="field_test[0][viewfield_test][display_id]"], select[name="field_test[0][viewfield_test][display_id]"]'
    );

    // Select view_one → AJAX should expose only page_1.
    $page->selectFieldOption(
      'field_test[0][viewfield_test][target_id]',
      'view_one'
    );
    $assert->assertWaitOnAjaxRequest();
    $assert->waitForElement(
      'css',
      'select[name="field_test[0][viewfield_test][display_id]"]'
    );

    $assert->optionExists(
      'field_test[0][viewfield_test][display_id]',
      'page_1'
    );
    $assert->optionNotExists(
      'field_test[0][viewfield_test][display_id]',
      'default'
    );

    $page->selectFieldOption(
      'field_test[0][viewfield_test][display_id]',
      'page_1'
    );

    // Advanced options live in a closed details (and are #states-hidden until
    // a view is selected). Open the details before interacting.
    $summary = $page->find('css', 'details summary');
    $this->assertNotNull($summary);
    // Prefer the Advanced options summary if multiple details exist.
    $advanced = $page->find('named', ['content', 'Advanced options']);
    if ($advanced) {
      $advanced->click();
    }
    else {
      $summary->click();
    }

    $assert->waitForField('field_test[0][viewfield_test][view_options][arguments]');
    $page->fillField(
      'field_test[0][viewfield_test][view_options][arguments]',
      '99'
    );
    $page->fillField(
      'field_test[0][viewfield_test][view_options][items_to_display]',
      '3'
    );

    $page->pressButton('Save');
    $assert->waitForText('JS viewfield node');

    $node = $this->loadNodeByTitle('JS viewfield node');
    $this->assertEquals('view_one', $node->get('field_test')->viewfield_test);
    $this->assertEquals(
      'page_1',
      $node->get('field_test')->viewfield_test__display
    );
    $this->assertEquals(
      '99',
      $node->get('field_test')->viewfield_test__arguments
    );
    $this->assertEquals(
      3,
      (int) $node->get('field_test')->viewfield_test__items
    );
  }

  /**
   * Tests switching views AJAX-replaces display options.
   */
  public function testAjaxSwitchViewUpdatesDisplays(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $page = $this->getSession()->getPage();

    $page->selectFieldOption(
      'field_test[0][viewfield_test][target_id]',
      'view_one'
    );
    $assert->assertWaitOnAjaxRequest();
    $assert->waitForElement(
      'css',
      'select[name="field_test[0][viewfield_test][display_id]"]'
    );
    $assert->optionExists(
      'field_test[0][viewfield_test][display_id]',
      'page_1'
    );
    $assert->optionNotExists(
      'field_test[0][viewfield_test][display_id]',
      'default'
    );

    // Switch to view_two — both displays should become available.
    $page->selectFieldOption(
      'field_test[0][viewfield_test][target_id]',
      'view_two'
    );
    $assert->assertWaitOnAjaxRequest();
    $assert->waitForElement(
      'css',
      'select[name="field_test[0][viewfield_test][display_id]"]'
    );

    $assert->optionExists(
      'field_test[0][viewfield_test][display_id]',
      'default'
    );
    $assert->optionExists(
      'field_test[0][viewfield_test][display_id]',
      'page_1'
    );
  }

}
