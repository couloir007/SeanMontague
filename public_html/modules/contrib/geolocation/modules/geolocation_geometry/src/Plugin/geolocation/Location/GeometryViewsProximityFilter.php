<?php

namespace Drupal\geolocation_geometry\Plugin\geolocation\Location;

use Drupal\geolocation\Attribute\Location;
use Drupal\geolocation\Plugin\geolocation\Location\ViewsProximityFilter;

/**
 * Derive center from proximity filter.
 */
#[Location(
  id: 'geometry_views_proximity_filter',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geometry Proximity filter'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Set map center from geometry proximity filter.')
)]
class GeometryViewsProximityFilter extends ViewsProximityFilter {

  /**
   * {@inheritdoc}
   */
  protected string $filterPluginId = 'geolocation_geometry_filter_proximity';

}
