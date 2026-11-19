<?php

namespace Drupal\trail_mapper_planner\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\trail_mapper_planner\Controller\PlannerController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Builds the five planner routes for every area in config.
 */
class PlannerRoutes implements ContainerInjectionInterface {

  public function __construct(protected ConfigFactoryInterface $configFactory) {}

  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'));
  }

  public function routes(): RouteCollection {
    $collection = new RouteCollection();
    $areas = $this->configFactory->get('trail_mapper_planner.settings')->get('areas') ?? [];

    $routes = [
      'page' => ['', 'page', 'access content'],
      'network' => ['/network.json', 'network', 'access content'],
      'profile' => ['/profile', 'profile', 'access content'],
      'gpx' => ['/gpx', 'gpx', 'access content'],
      'refresh' => ['/refresh', 'refresh', 'administer site configuration'],
    ];

    foreach ($areas as $area => $settings) {
      foreach ($routes as $name => [$suffix, $method, $permission]) {
        $collection->add("trail_mapper_planner.$area.$name", new Route(
          '/' . $settings['path'] . '/map' . $suffix,
          [
            '_controller' => PlannerController::class . '::' . $method,
            'area' => $area,
          ],
          ['_permission' => $permission],
        ));
      }
    }
    return $collection;
  }

}
