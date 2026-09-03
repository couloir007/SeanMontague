<?php

namespace Drupal\geolocation_leaflet\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;
use Drupal\geolocation\MapFeatureBase;

/**
 * Provides disabled interaction.
 */
#[MapFeature(
  id: 'leaflet_disable_user_interaction',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Disable User Interaction'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Disable direct user interaction like zooming or panning'),
  type: 'leaflet'
)]
class LeafletDisableUserInteraction extends MapFeatureBase {

}
