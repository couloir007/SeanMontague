<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;
use Drupal\geolocation\MapFeatureBase;

/**
 * Provides traffic layer.
 */
#[MapFeature(
  id: 'google_maps_layer_bicycling',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Bicycling layer'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Allows you to add real-time bicycling information (where supported) to your maps.'),
  type: 'google_maps'
)]
class GoogleLayerBicycling extends MapFeatureBase {

}
