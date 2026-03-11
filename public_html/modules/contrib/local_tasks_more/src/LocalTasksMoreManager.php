<?php

declare(strict_types=1);

namespace Drupal\local_tasks_more;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Menu\LocalTaskManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * The local tasks more manager.
 */
class LocalTasksMoreManager implements LocalTasksMoreManagerInterface {
  use StringTranslationTrait;

  /**
   * Track if we are getting the un-altered local tasks.
   */
  protected bool $unAltered = FALSE;

  /**
   * Constructs a LocalTasksMoreManager object.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected CacheBackendInterface $cacheBackend,
    protected CacheTagsInvalidatorInterface $cacheTagsInvalidator,
    protected LocalTaskManagerInterface $localTaskManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function localTasksAlter(array &$local_tasks): void {
    if ($this->unAltered) {
      return;
    }

    $alter_local_tasks = $this->configFactory
      ->get('local_tasks_more.settings')
      ->get('alter_local_tasks');
    foreach ($alter_local_tasks as $alter_local_task) {
      $alter_plugin_id = $alter_local_task['plugin_id'];
      if (!isset($local_tasks[$alter_plugin_id])) {
        continue;
      }

      if (isset($alter_local_task['status'])
        && $alter_local_task['status'] === FALSE) {
        unset($local_tasks[$alter_plugin_id]);
      }
      else {
        unset(
          $alter_local_task['plugin_id'],
          $alter_local_task['status'],
        );
        if (isset($alter_local_task['title'])) {
          $alter_local_task['title'] = new TranslatableMarkup($alter_local_task['title']);
        }
        $local_tasks[$alter_plugin_id] = $alter_local_task
          + $local_tasks[$alter_plugin_id];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function menuLocalTasksAlter(array &$data, string $route_name, RefinableCacheableDependencyInterface &$cacheability): void {
    if (!isset($data['tabs'])
      || !$this->hasLocalTasksMore($route_name)) {
      return;
    }

    $levels = [
      'primary',
      'secondary',
    ];

    // The show more icon, title, and URL.
    $more_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"><path fill="#000000" d="M8.053 8.355c.193-.195.193-.517 0-.711l-3.26-3.289c-.193-.195-.192-.514.002-.709l1.371-1.371c.194-.194.512-.193.706.001l5.335 5.369c.195.195.195.515 0 .708l-5.335 5.37c-.194.192-.512.193-.706.002l-1.371-1.371c-.194-.195-.195-.514-.002-.709l3.26-3.29z"/></svg>';
    // Note: Using Xss::filter to prevent XSS if the SVG was customizable.
    $more_title = Markup::create(Xss::filter($more_icon, ['svg', 'path']));
    $more_url = Url::fromRoute('<none>', [], ['fragment' => 'more']);

    foreach ($data['tabs'] as $level => $tabs) {
      // Get visible and sorted tabs.
      uasort($tabs, '\Drupal\Component\Utility\SortArray::sortByWeightProperty');
      foreach ($tabs as $tab_route_name => $tab) {
        /** @var \Drupal\Core\Access\AccessResultInterface $access */
        $access = $tab['#access'];
        if (!$access->isAllowed()) {
          unset($tabs[$tab_route_name]);
        }
      }

      // Get local tasks level (primary or secondary) configuration.
      $config = $this->configFactory
        ->get('local_tasks_more.settings')
        ->get($levels[$level]);
      // Limit: The number of links visible before the show more link.
      $limit = (int) $config['limit'];
      // Threshold: The number of links past the limit to trigger the display of the more link.
      $threshold = (int) $config['threshold'];

      // If the number of visible links is more than the limit + threshold,
      // don't display the more/less toggle.
      if (count($tabs) <= ($limit + $threshold)) {
        continue;
      }

      $index = 0;
      foreach ($tabs as $tab_route_name => $tab) {
        if ($index >= $limit
          && empty($tab['#active'])) {
          $data['tabs'][$level][$tab_route_name]['#local_tasks_more'] = 'tab';
        }
        $index++;
      }

      // Add the toggle as the last local task.
      $data['tabs'][$level]['local_tasks_more'] = [
        '#theme' => 'menu_local_task',
        '#link' => [
          'title' => $more_title,
          'url' => $more_url,
          'localized_options' => [],
        ],
        '#access' => TRUE,
        '#weight' => 1000,
        '#local_tasks_more' => 'toggle',
        '#level' => $levels[$level],
        '#attached' => ['library' => ['local_tasks_more/local_tasks_more']],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessMenuLocalTask(array &$variables): void {
    $element = $variables['element'];
    $local_tasks_more = $element['#local_tasks_more'] ?? NULL;
    if (empty($local_tasks_more)) {
      return;
    }

    if ($local_tasks_more === 'toggle') {
      // Tab attributes.
      $variables['attributes']['class'][] = 'local-tasks-more-toggle';
      $variables['attributes']['class'][] = 'local-tasks-more-toggle-' . $element['#level'];
      $variables['attributes']['data-local-tasks-more'] = $element['#level'];
      // Link attributes.
      $variables['link']['#attributes']['title'] = $this->t('Show more…');
      $variables['link']['#attributes']['role'] = 'button';
      $variables['link']['#attributes']['aria-expanded'] = 'false';
    }
    else {
      // Tab attributes.
      $variables['attributes']['class'][] = 'local-tasks-more-' . $local_tasks_more;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalTasks(): array {
    // Get un-altered definitions.
    $definitions = $this->getDefinitions();

    // Get cleaned up local tasks definitions.
    $keys = [
      'title' => 'title',
      'route_name' => 'route_name',
      'base_route' => 'base_route',
      'parent_id' => 'parent_id',
      'weight' => 'weight',
    ];
    foreach ($definitions as $plugin_id => $task_info) {
      $task_info = array_intersect_key($task_info, $keys);
      $task_info = array_filter($task_info, fn ($property) => !is_null($property));
      $task_info['weight'] = $task_info['weight'] ?? 0;
      $definitions[$plugin_id] = $task_info;
    }

    $local_tasks = [];
    $parents = [];
    foreach ($definitions as $plugin_id => $task_info) {
      $base_route = $task_info['base_route'] ?? NULL;
      if ($base_route) {
        $local_tasks[$base_route] = $local_tasks[$base_route] ?? [];
        $local_tasks[$base_route][$plugin_id] = $task_info + ['children' => []];

        $parents[$plugin_id] = $base_route;
      }
    }

    foreach ($definitions as $plugin_id => $task_info) {
      $parent_id = $task_info['parent_id'] ?? NULL;
      $base_route = $parents[$parent_id] ?? NULL;
      if ($parent_id && $base_route) {
        $local_tasks[$base_route][$parent_id][$plugin_id] = $task_info;
      }
    }

    // Sort local tasks.
    ksort($local_tasks);
    foreach ($local_tasks as &$primary_tasks) {
      uasort($primary_tasks, '\Drupal\Component\Utility\SortArray::sortByWeightElement');
      foreach ($primary_tasks as &$primary_task_info) {
        $secondary_task_info =& $primary_task_info['children'];
        uasort($secondary_task_info, '\Drupal\Component\Utility\SortArray::sortByWeightElement');
      }
    }

    // Remove micro weights.
    // @see \Drupal\Core\Menu\LocalTaskManager::getDefinitions
    foreach ($local_tasks as &$primary_tasks) {
      foreach ($primary_tasks as &$primary_task_info) {
        $primary_task_info['weight'] = (int) $primary_task_info['weight'];
        foreach ($primary_task_info['children'] as &$secondary_task_info) {
          $secondary_task_info['weight'] = (int) $secondary_task_info['weight'];
        };
      }
    }
    return $local_tasks;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseRoute(string $route_name): ?string {
    $base_routes = $this->getBaseRoutes();
    return $base_routes[$route_name] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseRoutes(): array {
    $cid = 'local_task_more_base_router';
    if ($cache = $this->cacheBackend->get($cid)) {
      return $cache->data;
    }

    $base_routes = [];
    $parents = [];
    $definitions = $this->getDefinitions();
    foreach ($definitions as $plugin_id => $task_info) {
      $route_name = $task_info['route_name'] ?? NULL;
      $base_route = $task_info['base_route'] ?? NULL;
      if ($route_name && $base_route) {
        $base_routes[$route_name] = $base_route;
        $parents[$plugin_id] = $base_route;
      }
    }

    foreach ($definitions as $task_info) {
      $route_name = $task_info['route_name'] ?? NULL;
      $parent_id = $task_info['parent_id'] ?? NULL;
      $base_route = $parents[$parent_id] ?? NULL;
      if ($route_name && $base_route) {
        $base_routes[$route_name] = $base_route;
      }
    }

    ksort($base_routes);

    $this->cacheBackend->set('local_task_more_base_router', $base_routes, Cache::PERMANENT, ['local_task']);
    return $base_routes;
  }

  /**
   * Get unaltered local task definitions.
   *
   * @return array
   *   The unaltered local task definitions.
   */
  protected function getDefinitions(): array {
    // Get unaltered definitions by setting the flag and invalidate
    // the 'local_task' cache tag.
    $this->unAltered = TRUE;
    $this->cacheTagsInvalidator->invalidateTags(['local_task']);
    $definitions = $this->localTaskManager->getDefinitions();
    $this->unAltered = FALSE;

    // Rebuild altered local task definitions.
    $this->cacheTagsInvalidator->invalidateTags(['local_task']);
    $this->localTaskManager->getDefinitions();

    return $definitions;
  }

  /**
   * Check if the given route name has local tasks more/less link.
   *
   * @param string $route_name
   *   The route name to check.
   *
   * @return bool
   *   TRUE if the route has local tasks more/less link, FALSE otherwise.
   */
  protected function hasLocalTasksMore(string $route_name): bool {
    $base_route = $this->getBaseRoute($route_name);
    if (!$base_route) {
      return FALSE;
    }

    $base_routes = $this->configFactory
      ->get('local_tasks_more.settings')
      ->get('base_routes');
    return in_array($base_route, $base_routes);
  }

}
