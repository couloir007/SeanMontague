<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;
use Drupal\geolocation\MapFeatureBase;

/**
 * Provides map tilt.
 */
#[MapFeature(
  id: 'map_disable_tilt',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Disable Map Tilt'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Disable 45° tilted perspective view available for certain locations.'),
  type: 'google_maps'
)]
class GoogleMapTilt extends MapFeatureBase {

}
