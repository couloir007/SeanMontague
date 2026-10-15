<?php

namespace Drupal\Tests\config_views\Functional;

use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\BrowserTestBase;
use Drupal\views\Entity\View;

/**
 * Test views selection handler.
 *
 * @group config_views
 */
class ViewsSelectionHandlerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'filter',
    'user',
    'views',
    'block',
    'path',
    'block_content',
    'config_views',
    'field_ui',
    'views_ui',
    'config_views_test',
  ];

  /**
   * An administrative user to configure the test environment.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create and log in an administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer blocks',
      'access administration pages',
      'administer menu',
      'administer blocks',
      'administer site configuration',
      'administer permissions',
      'administer views',
      'administer content types',
      'administer display modes',
      'administer nodes',
      'create article content',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests views selection handler.
   */
  public function testViewsSelectionHandler() {

    // Roles list should filter out the 'Hide me'.
    $this->drupalGet('node/add/article');
    $this->assertSession()->elementTextContains('css', '.field--name-field-roles', 'Show me');
    $this->assertSession()->elementTextNotContains('css', '.field--name-field-roles', 'Hide me');

    // Change to hide the 'Show me' role instead.
    $view = View::load('restricted_roles');
    $display = $view->get('display');
    $display['default']['display_options']['filters']['id']['value'] = 'show_me';
    $view->set('display', $display);
    $view->save();
    drupal_flush_all_caches();

    // Now expect to see the 'Hide me' role, and not see the 'Show me' role.
    $this->drupalGet('node/add/article');
    $this->assertSession()->elementTextNotContains('css', '.field--name-field-roles', 'Show me');
    $this->assertSession()->elementTextContains('css', '.field--name-field-roles', 'Hide me');
  }

  /**
   * Tests reference labels and IDs that use different entity property names.
   */
  public function testReferenceableEntitiesWithCustomKeys() {
    foreach (['first_format' => 'First format', 'second_format' => 'Second format'] as $id => $label) {
      FilterFormat::create([
        'format' => $id,
        'name' => $label,
      ])->save();
    }

    View::create([
      'id' => 'reference_formats',
      'label' => 'Reference formats',
      'base_table' => 'config_filter_format',
      'base_field' => 'format',
      'display' => [
        'default' => [
          'id' => 'default',
          'display_plugin' => 'default',
          'display_options' => [
            'fields' => [
              'name' => [
                'id' => 'name',
                'table' => 'config_filter_format',
                'field' => 'name',
                'plugin_id' => 'standard',
              ],
            ],
          ],
        ],
        'entity_reference_1' => [
          'id' => 'entity_reference_1',
          'display_plugin' => 'entity_reference',
          'display_options' => [
            'style' => [
              'type' => 'entity_reference',
              'options' => ['search_fields' => ['name' => 'name']],
            ],
            'row' => ['type' => 'entity_reference'],
          ],
        ],
      ],
    ])->save();

    $selection = $this->container->get('plugin.manager.entity_reference_selection')->createInstance('config_views', [
      'target_type' => 'filter_format',
      'view' => [
        'view_name' => 'reference_formats',
        'display_name' => 'entity_reference_1',
        'arguments' => [],
      ],
    ]);
    $entities = $selection->getReferenceableEntities();
    $this->assertSame('First format', $entities['filter_format']['first_format']);
    $this->assertSame('Second format', $entities['filter_format']['second_format']);
  }

}
