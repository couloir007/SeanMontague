<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field_entity_browser\Functional\Widget;

use Drupal\entity_browser\Entity\EntityBrowser;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\user\RoleInterface;
use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the entity_reference_entity_browser widget.
 *
 * Creates a minimal entity browser config entity. Modal open/select/remove
 * AJAX is covered in FunctionalJavascript.
 *
 * @group custom_field
 * @group custom_field_entity_browser
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[Group('custom_field_entity_browser')]
#[RunTestsInSeparateProcesses]
class EntityReferenceBrowserWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_browser',
    'views',
    'custom_field_entity_browser',
  ];

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

    // Browser must exist before its access permission / routes are available.
    $this->createTestEntityBrowser();
    user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, [
      'access ' . self::BROWSER_ID . ' entity browser pages',
    ]);

    $this->targets['alpha'] = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Browser Target Alpha',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->targets['beta'] = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Browser Target Beta',
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
    // Minimal browser: no widgets. Form rendering only needs a valid browser
    // config entity; selection is exercised via target_id injection.
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
   * Tests widget settings appear on the form display UI.
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();
    $base = self::FIELD_PATH . '[ref]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');

    $assert->fieldExists($base . '[entity_browser]');
    $assert->fieldValueEquals($base . '[entity_browser]', self::BROWSER_ID);
    $assert->fieldExists($base . '[field_widget_display]');
    $assert->checkboxChecked($base . '[field_widget_edit]');
    $assert->checkboxChecked($base . '[field_widget_remove]');
    $assert->checkboxNotChecked($base . '[field_widget_replace]');
    $assert->checkboxChecked($base . '[open]');

    $this->submitForm([
      $base . '[field_widget_replace]' => TRUE,
      $base . '[open]' => FALSE,
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');
  }

  /**
   * Tests empty widget renders the subfield wrapper and target_id input.
   */
  public function testEmptyStateUi(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Node browser');

    // target_id is a hidden input used by entity browser JS after selection.
    $hidden = $this->getSession()->getPage()->find(
      'css',
      'input[name="field_test[0][ref][target_id]"], input[type="hidden"][name*="[ref][target_id]"]'
    );
    $this->assertNotNull($hidden, 'Expected a target_id input on the empty widget.');
    $this->assertSame('', (string) $hidden->getValue());
  }

  /**
   * Tests seeded selection shows label and Remove on edit.
   */
  public function testPersistedSelectionOnEdit(): void {
    $assert = $this->assertSession();
    $alpha = $this->targets['alpha'];

    $node = Node::create([
      'type' => 'page',
      'title' => 'EB seeded node',
      'field_test' => [
        'ref' => $alpha->id(),
      ],
    ]);
    $node->save();
    $this->assertEquals($alpha->id(), $node->get('field_test')->ref);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Browser Target Alpha');
    $assert->buttonExists('Remove');

    // target_id uses entity_type:id when a selection is present.
    $hidden = $this->getSession()->getPage()->find(
      'css',
      'input[name="field_test[0][ref][target_id]"]'
    );
    $this->assertNotNull($hidden, 'Expected target_id hidden field for selection.');
    $this->assertEquals('node:' . $alpha->id(), $hidden->getValue());
  }

  /**
   * Tests massageFormValue shape: entity_type:id becomes a stored target id.
   *
   * Full selection is injected by entity browser JS (covered in
   * FunctionalJavascript). Here we verify storage + edit form display for the
   * same value shape the widget writes after a browser selection.
   */
  public function testStoredTargetIdRoundTrip(): void {
    $assert = $this->assertSession();
    $alpha = $this->targets['alpha'];

    $node = Node::create([
      'type' => 'page',
      'title' => 'EB save node',
      'field_test' => [
        'ref' => $alpha->id(),
      ],
    ]);
    $node->save();
    $this->assertEquals($alpha->id(), $node->get('field_test')->ref);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Browser Target Alpha');

    // submitForm()/fillField() do not reliably set hidden inputs; set directly.
    $page = $this->getSession()->getPage();
    $hidden = $page->find(
      'css',
      'input[name="field_test[0][ref][target_id]"], input[type="hidden"][name*="[ref][target_id]"]'
    );
    $this->assertNotNull($hidden, 'Expected target_id hidden field on edit form.');
    $hidden->setValue('node:' . $this->targets['beta']->id());
    $page->pressButton('Save');

    $reloaded = $this->loadNodeByTitle('EB save node');
    $this->assertEquals(
      $this->targets['beta']->id(),
      $reloaded->get('field_test')->ref
    );
  }

  /**
   * Tests empty submission stores NULL.
   */
  public function testEmptyValueStoresNull(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');
    $assert->statusCodeEquals(200);
    $this->submitForm([
      'title[0][value]' => 'EB empty node',
    ], 'Save');

    $node = $this->loadNodeByTitle('EB empty node');
    $value = $node->get('field_test')->ref ?? NULL;
    $this->assertTrue($value === NULL || $value === '');
  }

  /**
   * Tests required entity browser subfield validation.
   */
  public function testRequiredValidation(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_eb_required',
      [
        'ref_req' => [
          'name' => 'ref_req',
          'type' => 'entity_reference',
          'target_type' => 'node',
        ],
      ],
      [
        'ref_req' => [
          'label' => 'Required browser ref',
          'check_empty' => FALSE,
          'required' => TRUE,
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
    $required_field->setRequired(TRUE)->save();
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    $this->setFormDisplay('field_eb_required', [
      'ref_req' => [
        'type' => 'entity_reference_entity_browser',
        'weight' => 0,
        'label' => 'Required browser ref',
        'entity_browser' => self::BROWSER_ID,
        'open' => TRUE,
        'field_widget_display' => 'label',
        'field_widget_edit' => TRUE,
        'field_widget_remove' => TRUE,
        'field_widget_replace' => FALSE,
        'field_widget_display_settings' => [],
      ],
    ]);

    $this->drupalGet('node/add/page');
    $assert->statusCodeEquals(200);
    $this->submitForm([
      'title[0][value]' => 'Required EB node',
    ], 'Save');

    $assert->pageTextNotContains('Required EB node has been created');
  }

  /**
   * Tests field_widget_remove hides the Remove button when disabled.
   */
  public function testRemoveButtonRespectsSetting(): void {
    $assert = $this->assertSession();
    $alpha = $this->targets['alpha'];

    $this->setFormDisplay('field_test', [
      'ref' => [
        'type' => 'entity_reference_entity_browser',
        'weight' => 0,
        'label' => 'Node browser',
        'entity_browser' => self::BROWSER_ID,
        'open' => TRUE,
        'field_widget_display' => 'label',
        'field_widget_edit' => TRUE,
        'field_widget_remove' => FALSE,
        'field_widget_replace' => FALSE,
        'field_widget_display_settings' => [],
      ],
    ]);

    $node = Node::create([
      'type' => 'page',
      'title' => 'EB no remove node',
      'field_test' => [
        'ref' => $alpha->id(),
      ],
    ]);
    $node->save();

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Browser Target Alpha');
    // #access on remove_button is gated by field_widget_remove.
    $assert->elementNotExists('css', '.remove-button');
  }

}
