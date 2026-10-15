<?php

namespace Drupal\Tests\config_views\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\rest\Entity\RestResourceConfig;
use Drupal\views\Entity\View;

/**
 * Tests listing saved REST resource configurations.
 *
 * @group config_views
 */
class RestResourceListingTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'field', 'node', 'serialization', 'rest', 'views', 'config_views'];

  /**
   * Tests that saved REST resources appear in configuration Views.
   */
  public function testSavedRestResourceListing() {
    $this->installConfig(['system', 'views']);
    $storage = $this->container->get('entity_type.manager')->getStorage('rest_resource_config');
    $this->assertSame([], $storage->loadMultiple());
    RestResourceConfig::create([
      'id' => 'entity.node',
      'plugin_id' => 'entity:node',
      'granularity' => 'resource',
      'configuration' => [
        'methods' => ['GET'],
        'formats' => ['json'],
        'authentication' => ['cookie'],
      ],
    ])->save();
    $view = View::create([
      'id' => 'rest_resources',
      'label' => 'REST resources',
      'base_table' => 'config_rest_resource',
      'base_field' => 'id',
      'display' => [
        'default' => [
          'id' => 'default',
          'display_plugin' => 'default',
          'display_options' => [
            'fields' => [
              'id' => [
                'id' => 'id',
                'table' => 'config_rest_resource',
                'field' => 'id',
                'plugin_id' => 'standard',
              ],
            ],
          ],
        ],
      ],
    ])->getExecutable();
    $view->execute();
    $this->assertCount(1, $view->result);
    $this->assertSame('entity.node', $view->result[0]->id);
  }

}
