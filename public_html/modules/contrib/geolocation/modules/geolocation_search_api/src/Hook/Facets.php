<?php

namespace Drupal\geolocation_search_api\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for the Facets integration.
 */
class Facets {

  /**
   * Implements hook_facets_search_api_query_type_mapping_alter().
   *
   * The hook is not told the data type, the map widget only offers itself for
   * geolocation fields.
   *
   * @see \Drupal\geolocation_search_api\Plugin\facets\widget\GeolocationMapWidget::supportsFacet()
   */
  #[Hook('facets_search_api_query_type_mapping_alter')]
  public function facetsSearchApiQueryTypeMappingAlter(string $backend_plugin_id, array &$query_types): void {
    $query_types['geolocation_boundary'] = 'geolocation_boundary';
  }

}
