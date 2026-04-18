<?php

namespace Drupal\schemadotorg_demo_admin\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Schema.org Demo Admin routes.
 */
class SchemaDotOrgDemoAdminSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    $routes = $collection->all();
    foreach ($routes as $route_name => $route) {
      // Set search view and help routes to use the admin theme.
      if (str_starts_with($route_name, 'search.view')
        || str_starts_with($route_name, 'search.help')) {
        $route->setOption('_admin_route', TRUE);
      }
    }

    $admin_routes = [
      // Set view profile to be an admin route because user profiles
      // are internal only.
      'entity.user.canonical',
    ];
    foreach ($admin_routes as $admin_route) {
      if (isset($routes[$admin_route])) {
        $routes[$admin_route]->setOption('_admin_route', TRUE);
      }
    }
  }

}
