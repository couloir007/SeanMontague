<?php

namespace Drupal\geolocation_geometry_test_js\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Hook implementations for geolocation_geometry_test_js module.
 */
class TestHooks {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * Constructs a new TestHooks object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {
    if (!$this->routeMatch->getRouteName()) {
      return;
    }

    if ($this->routeMatch->getParameter('view_id') === 'geolocation_countries') {
      $attachments['#attached']['library'][] = 'geolocation_geometry_test_js/test_behavior';
    }
  }

}
