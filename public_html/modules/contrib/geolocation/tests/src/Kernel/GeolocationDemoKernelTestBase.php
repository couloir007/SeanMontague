<?php

namespace Drupal\Tests\geolocation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\views\ViewExecutable;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Base class for tests running against the demo views.
 *
 * Kernel tests only import a module's configuration, they do not run its
 * install hook - so the demo content type, fields and views are available here
 * without the 100 nodes geolocation_demo_install() would create.
 */
#[RunTestsInSeparateProcesses]
abstract class GeolocationDemoKernelTestBase extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'filter',
    'node',
    'taxonomy',
    'image',
    'file',
    'views',
    'geolocation',
    'geolocation_demo',
  ];

  /**
   * Configuration to import in addition to the defaults.
   *
   * @var string[]
   */
  protected static array $demoConfig = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('file');
    $this->installSchema('user', ['users_data']);

    $this->installConfig(array_merge([
      'system',
      'field',
      'node',
      'filter',
      'geolocation_demo',
    ], static::$demoConfig));

    // Views page displays check 'access content'.
    $this->setUpCurrentUser([], ['access content'], TRUE);

    $this->container->get('theme_installer')->install(['stark']);
    $this->config('system.theme')->set('default', 'stark')->save();
  }

  /**
   * Creates demo article nodes.
   *
   * @param array $nodes
   *   Node definitions, each a title keyed array of 'lat' and 'lng'.
   *
   * @return \Drupal\node\NodeInterface[]
   *   The created nodes, keyed by title.
   */
  protected function createDemoNodes(array $nodes): array {
    $storage = \Drupal::entityTypeManager()->getStorage('node');

    $created = [];
    foreach ($nodes as $title => $coordinates) {
      $node = $storage->create([
        'type' => 'geolocation_default_article',
        'title' => $title,
        'status' => 1,
        'field_geolocation_demo_single' => $coordinates,
      ]);
      $node->save();
      $created[$title] = $node;
    }

    return $created;
  }

  /**
   * Executes a view display.
   *
   * @param string $view_id
   *   View ID.
   * @param string $display_id
   *   Display ID.
   * @param array $arguments
   *   Views arguments.
   *
   * @return \Drupal\views\ViewExecutable
   *   The executed view.
   */
  protected function executeViewDisplay(string $view_id, string $display_id, array $arguments = []): ViewExecutable {
    $view = \Drupal::entityTypeManager()->getStorage('view')->load($view_id);
    $this->assertNotEmpty($view, "View $view_id available.");

    $executable = $view->getExecutable();
    $executable->setDisplay($display_id);
    $executable->setArguments($arguments);
    $executable->execute($display_id);

    return $executable;
  }

  /**
   * Renders a view display.
   *
   * @param string $view_id
   *   View ID.
   * @param string $display_id
   *   Display ID.
   * @param array $arguments
   *   Views arguments.
   *
   * @return string
   *   The rendered output.
   */
  protected function renderViewDisplay(string $view_id, string $display_id, array $arguments = []): string {
    $view = \Drupal::entityTypeManager()->getStorage('view')->load($view_id);
    $this->assertNotEmpty($view, "View $view_id available.");

    $executable = $view->getExecutable();
    $executable->setArguments($arguments);
    $build = $executable->buildRenderable($display_id, $arguments);

    return (string) $this->container->get('renderer')->renderInIsolation($build);
  }

  /**
   * Returns the titles of the rows of an executed view.
   *
   * @param \Drupal\views\ViewExecutable $executable
   *   Executed view.
   *
   * @return string[]
   *   Row titles.
   */
  protected function getResultTitles(ViewExecutable $executable): array {
    return array_map(
      fn ($row) => $row->_entity->label(),
      $executable->result
    );
  }

}
