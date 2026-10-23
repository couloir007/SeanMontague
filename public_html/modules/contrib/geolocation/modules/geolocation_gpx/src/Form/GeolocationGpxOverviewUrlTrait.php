<?php

namespace Drupal\geolocation_gpx\Form;

use Drupal\Core\Url;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Provides the URL of the GPX overview for forms to return to.
 *
 * The GPX entity has no canonical link, so every form has to state where to
 * go once it is done.
 */
trait GeolocationGpxOverviewUrlTrait {

  /**
   * Get URL of the GPX overview.
   *
   * @return \Drupal\Core\Url
   *   Overview of all GPX entities, or the content overview as fallback if the
   *   optional GPX view is not present.
   */
  protected function overviewUrl(): Url {
    try {
      \Drupal::service('router.route_provider')->getRouteByName('view.geolocation_gpx.page_1');
    }
    catch (RouteNotFoundException) {
      return Url::fromRoute('system.admin_content');
    }

    return Url::fromRoute('view.geolocation_gpx.page_1');
  }

}
