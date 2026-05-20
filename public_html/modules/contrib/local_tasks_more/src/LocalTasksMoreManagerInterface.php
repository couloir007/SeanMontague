<?php

declare(strict_types=1);

namespace Drupal\local_tasks_more;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;

/**
 * The local tasks more manager interface.
 */
interface LocalTasksMoreManagerInterface {

  /**
   * Alter local tasks plugins.
   *
   * @param array $local_tasks
   *   The array of local tasks plugin definitions, keyed by plugin ID.
   *
   * @see hook_local_tasks_alter()
   */
  public function localTasksAlter(array &$local_tasks): void;

  /**
   * Alter local tasks displayed on the page before they are rendered.
   *
   * @param array $data
   *   An associative array containing list of (up to 2) tab levels that
   *   contain a list of tabs keyed by their href, each one being
   *   an associative array as described above.
   * @param string $route_name
   *   The route name of the page.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $cacheability
   *   The cacheability metadata for the current route's local tasks.
   *
   * @see hook_menu_local_tasks_alter()
   */
  public function menuLocalTasksAlter(array &$data, string $route_name, RefinableCacheableDependencyInterface &$cacheability): void;

  /**
   * Prepares variables for single local task link templates.
   *
   * @param array $variables
   *   An associative array containing:
   *   - element: A render element containing:
   *     - #link: A menu link array with 'title', 'url', and (optionally)
   *       'localized_options' keys.
   *     - #active: A boolean indicating whether the local task is active.
   *
   * @see template_preprocess_menu_local_task()
   */
  public function preprocessMenuLocalTask(array &$variables): void;

  /**
   * Retrieves local tasks for the application.
   *
   * @return array
   *   The local tasks for the application.
   */
  public function getLocalTasks(): array;

  /**
   * Retrieves the base route for a specific route name, if it exists.
   *
   * @param string $route_name
   *   The name of the route for which to retrieve the base route.
   *
   * @return string|null
   *   The base route associated with the given route name, or NULL if not found.
   */
  public function getBaseRoute(string $route_name): ?string;

  /**
   * Retrieves the base routes for local tasks.
   *
   * @return array
   *   An array of route names and their corresponding base routes
   */
  public function getBaseRoutes(): array;

}
