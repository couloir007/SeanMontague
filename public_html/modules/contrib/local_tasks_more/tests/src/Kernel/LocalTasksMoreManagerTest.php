<?php

declare(strict_types=1);

namespace Drupal\Tests\local_tasks_more\Kernel;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Menu\LocalTaskManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\local_tasks_more\LocalTasksMoreManagerInterface;

/**
 * Test Local Tasks More manager.
 *
 * @group local_tasks_more
 */
class LocalTasksMoreManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'local_tasks_more'];

  /**
   * The local task plugin manager.
   */
  protected LocalTaskManagerInterface $localTaskManager;

  /**
   * The local task more manager.
   */
  protected LocalTasksMoreManagerInterface $localTasksMoreManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->localTaskManager = $this->container->get('plugin.manager.menu.local_task');
    $this->localTasksMoreManager = $this->container->get('local_tasks_more.manager');
  }

  /**
   * Test Local Tasks More manager.
   */
  public function testManager(): void {
    // Check altering local tasks plugins.
    $this->config('local_tasks_more.settings')
      ->set('alter_local_tasks', [
        [
          'plugin_id' => 'entity.node.delete_form',
          'status' => FALSE,
        ],
        [
          'plugin_id' => 'convert_bundles.entities:entity.node.convert_bundles',
          'title' => 'Convert',
        ],
      ])->save();
    $local_tasks = [
      'entity.node.delete_form' => [
        'title' => 'Delete',
      ],
      'convert_bundles.entities:entity.node.convert_bundles' => [
        'title' => 'Convert Bundles',
      ],
    ];
    $this->localTasksMoreManager->localTasksAlter($local_tasks);
    $this->assertArrayNotHasKey('entity.node.delete_form', $local_tasks);
    $this->assertEquals('Convert', (string) $local_tasks['convert_bundles.entities:entity.node.convert_bundles']['title']);

    // Check retrieving local tasks for the application.
    $expected_local_tasks = [
      'entity.node.canonical' => [
        'route_name' => 'entity.node.canonical',
        'title' => 'View',
        'base_route' => 'entity.node.canonical',
        'weight' => 0,
        'children' => [],
      ],
      'entity.node.edit_form' => [
        'route_name' => 'entity.node.edit_form',
        'title' => 'Edit',
        'base_route' => 'entity.node.canonical',
        'weight' => 0,
        'children' => [],
      ],
      'entity.node.delete_form' => [
        'route_name' => 'entity.node.delete_form',
        'title' => 'Delete',
        'base_route' => 'entity.node.canonical',
        'weight' => 10,
        'children' => [],
      ],
      'entity.node.version_history' => [
        'route_name' => 'entity.node.version_history',
        'title' => 'Revisions',
        'base_route' => 'entity.node.canonical',
        'weight' => 20,
        'children' => [],
      ],
    ];
    $actual_local_tasks = $this->localTasksMoreManager->getLocalTasks();
    $this->convertRenderMarkupToStrings($actual_local_tasks);
    $this->assertEquals($expected_local_tasks, $actual_local_tasks['entity.node.canonical']);

    // Check retrieving the base route for a specific route name, if it exists.
    $this->assertEquals(
      'entity.node.canonical',
      $this->localTasksMoreManager->getBaseRoute('entity.node.version_history')
    );
    $this->assertEquals(
      'entity.node_type.edit_form',
      $this->localTasksMoreManager->getBaseRoute('entity.node_type.edit_form')
    );

    // Check retrieving the base routes for local tasks.
    $expected_base_routes = [
      'entity.node.canonical' => 'entity.node.canonical',
      'entity.node.delete_form' => 'entity.node.canonical',
      'entity.node.edit_form' => 'entity.node.canonical',
      'entity.node.version_history' => 'entity.node.canonical',
      'entity.node_type.collection' => 'entity.node_type.collection',
      'entity.node_type.edit_form' => 'entity.node_type.edit_form',
    ];
    $actual_base_routes = $this->localTasksMoreManager->getBaseRoutes();
    $this->assertEquals($expected_base_routes, $actual_base_routes);
  }

  /**
   * Convert all render(able) markup into strings.
   *
   * This method is used to prevent objects from being serialized on form's
   * that are using #ajax callbacks or rebuilds.
   *
   * @param array $elements
   *   An associative array of elements.
   */
  protected function convertRenderMarkupToStrings(array &$elements): void {
    foreach ($elements as $key => &$value) {
      if (is_array($value)) {
        $this->convertRenderMarkupToStrings($value);
      }
      elseif ($value instanceof MarkupInterface) {
        $elements[$key] = (string) $value;
      }
    }
  }

}
