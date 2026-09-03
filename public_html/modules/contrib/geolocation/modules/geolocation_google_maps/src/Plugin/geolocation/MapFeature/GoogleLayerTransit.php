<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;
use Drupal\geolocation\MapFeatureBase;

/**
 * Provides transit layer.
 */
#[MapFeature(
  id: 'google_maps_layer_transit',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Transit layer'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Allows you to add real-time transit information (where supported) to your maps.'),
  type: 'google_maps'
)]
class GoogleLayerTransit extends MapFeatureBase {

}
