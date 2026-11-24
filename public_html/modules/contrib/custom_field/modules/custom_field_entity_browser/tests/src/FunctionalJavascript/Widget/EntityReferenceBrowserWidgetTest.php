<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field_entity_browser\FunctionalJavascript\Widget;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\entity_browser\Entity\EntityBrowser;
use Drupal\field\Entity\FieldConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\user\RoleInterface;
use Drupal\Tests\custom_field\Traits\CustomFieldTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * FunctionalJavascript tests for entity_reference_entity_browser.
 *
 * Covers selection via the widget's target_id AJAX path (same contract the
 * entity browser JS uses after a modal selection) and Remove AJAX.
 *
 * @group custom_field
 * @group custom_field_entity_browser
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[Group('custom_field_entity_browser')]
#[RunTestsInSeparateProcesses]
class EntityReferenceBrowserWidgetTest extends WebDriverTestBase {

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
    'entity_browser',
    'custom_field',
    'custom_field_entity_browser',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Referenceable page nodes.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected array $targets = [];

  /**
   * Test entity browser machine name.
   */
  protected const BROWSER_ID = 'test_cf_browser';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);
    $body = FieldConfig::loadByName('node', 'page', 'body');
    $body?->delete();

    $this->createTestEntityBrowser();

    $admin = $this->drupalCreateUser([
      'administer content types',
      'administer node fields',
      'administer node form display',
      'access content',
      'create page content',
      'edit any page content',
      'access ' . self::BROWSER_ID . ' entity browser pages',
    ]);
    $this->drupalLogin($admin);
    user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, [
      'access ' . self::BROWSER_ID . ' entity browser pages',
    ]);

    $this->targets['alpha'] = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'JS Browser Target Alpha',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->targets['beta'] = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'JS Browser Target Beta',
      'status' => NodeInterface::PUBLISHED,
    ]);

    $this->createCustomField(
      'field_test',
      [
        'ref' => [
          'name' => 'ref',
          'type' => 'entity_reference',
          'target_type' => 'node',
        ],
      ],
      [
        'ref' => [
          'label' => 'Node browser',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'handler' => 'default:node',
          'handler_settings' => [
            'target_bundles' => ['page' => 'page'],
            'sort' => ['field' => '_none', 'direction' => 'ASC'],
            'auto_create' => FALSE,
            'auto_create_bundle' => '',
          ],
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'ref' => [
        'type' => 'entity_reference_entity_browser',
        'weight' => 0,
        'label' => 'Node browser',
        'entity_browser' => self::BROWSER_ID,
        'open' => TRUE,
        'field_widget_display' => 'label',
        'field_widget_edit' => TRUE,
        'field_widget_remove' => TRUE,
        'field_widget_replace' => FALSE,
        'field_widget_display_settings' => [],
      ],
    ]);
  }

  /**
   * Creates a minimal modal entity browser used by the widget.
   */
  protected function createTestEntityBrowser(): void {
    $browser = EntityBrowser::create([
      'name' => self::BROWSER_ID,
      'label' => 'Test CF Browser',
      'display' => 'modal',
      'display_configuration' => [
        'width' => '650',
        'height' => '500',
        'link_text' => 'Select entities',
        'auto_open' => FALSE,
      ],
      'selection_display' => 'no_display',
      'selection_display_configuration' => [],
      'widget_selector' => 'single',
      'widget_selector_configuration' => [],
      'widgets' => [],
    ]);
    $browser->save();
    // Modal display generates URLs for entity_browser.{id}; rebuild routes.
    $this->container->get('router.builder')->rebuild();
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
   * Applies a selection the same way entity browser JS updates target_id.
   *
   * @param string $entity_type
   *   Entity type id.
   * @param int|string $entity_id
   *   Entity id.
   */
  protected function applyEntityBrowserSelection(string $entity_type, int|string $entity_id): void {
    $assert = $this->assertSession();
    $session = $this->getSession();

    $selector = 'input[name="field_test[0][ref][target_id]"], input[type="hidden"][name*="[ref][target_id]"]';
    $assert->elementExists('css', $selector);

    $session->executeScript(sprintf(
      'var el = document.querySelector(%s);
       if (!el) { throw new Error("target_id input missing"); }
       el.value = %s;
       if (typeof jQuery !== "undefined") {
         jQuery(el).trigger("entity_browser_value_updated");
       }
       else {
         el.dispatchEvent(new Event("entity_browser_value_updated", {bubbles: true}));
       }',
      json_encode($selector),
      json_encode($entity_type . ':' . $entity_id)
    ));
    try {
      $assert->assertWaitOnAjaxRequest(10000);
    }
    catch (\RuntimeException) {
      // Caller waits for selection UI text when AJAX binding is absent.
    }
  }

  /**
   * Tests selecting via target_id AJAX path and saving the node.
   */
  public function testSelectViaTargetIdAndSave(): void {
    $assert = $this->assertSession();
    $alpha = $this->targets['alpha'];

    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();
    $page->fillField('title[0][value]', 'JS EB select node');

    $this->applyEntityBrowserSelection('node', $alpha->id());

    $assert->waitForText('JS Browser Target Alpha');
    $assert->buttonExists('Remove');

    $hidden = $page->find(
      'css',
      'input[name="field_test[0][ref][target_id]"], input[type="hidden"][name*="[ref][target_id]"]'
    );
    $this->assertNotNull($hidden);
    $this->assertEquals('node:' . $alpha->id(), $hidden->getValue());

    $page->pressButton('Save');
    $assert->waitForText('has been created');

    $node = $this->loadNodeByTitle('JS EB select node');
    $this->assertEquals($alpha->id(), $node->get('field_test')->ref);
  }

  /**
   * Tests Remove clears the selection.
   */
  public function testRemoveSelectionAjax(): void {
    $assert = $this->assertSession();
    $alpha = $this->targets['alpha'];

    $node = Node::create([
      'type' => 'page',
      'title' => 'JS EB remove node',
      'field_test' => [
        'ref' => $alpha->id(),
      ],
    ]);
    $node->save();

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->pageTextContains('JS Browser Target Alpha');
    $assert->elementExists('css', '.remove-button');

    $page = $this->getSession()->getPage();
    $page->find('css', '.remove-button')->press();
    try {
      $assert->assertWaitOnAjaxRequest(10000);
    }
    catch (\RuntimeException) {
      // Full form rebuild is still valid if AJAX was not bound.
    }

    $assert->pageTextNotContains('JS Browser Target Alpha');

    $hidden = $page->find(
      'css',
      'input[name="field_test[0][ref][target_id]"], input[type="hidden"][name*="[ref][target_id]"]'
    );
    if ($hidden) {
      $this->assertSame('', (string) $hidden->getValue());
    }

    $page->pressButton('Save');
    $assert->waitForText('has been updated');

    $reloaded = $this->loadNodeByTitle('JS EB remove node');
    $value = $reloaded->get('field_test')->ref ?? NULL;
    $this->assertTrue($value === NULL || $value === '');
  }

  /**
   * Tests empty widget renders the subfield and target_id input.
   */
  public function testEmptyWidgetRendersTargetId(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');
    $assert->pageTextContains('Node browser');
    $assert->elementExists(
      'css',
      'input[name="field_test[0][ref][target_id]"], input[type="hidden"][name*="[ref][target_id]"]'
    );
  }

}
