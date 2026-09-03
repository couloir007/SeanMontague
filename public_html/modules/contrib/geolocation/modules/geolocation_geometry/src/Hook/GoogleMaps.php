<?php

namespace Drupal\geolocation_geometry\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for geolocation_geometry module functionality.
 */
class GoogleMaps {

  /**
   * Implements hook_field_views_data().
   */
  #[Hook('geolocation_google_maps_parameters')]
  public function addParameters(): array {
    return [
      'libraries' => [
        'drawing',
      ],
    ];
  }

}
