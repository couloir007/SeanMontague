<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;
use Drupal\geolocation\MapFeatureBase;

/**
 * Provides marker infowindow.
 */
#[MapFeature(
  id: 'map_disable_user_interaction',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Disable User Interaction'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Disable any zooming or panning by interaction from the user.'),
  type: 'google_maps'
)]
class GoogleMapDisableUserInteraction extends MapFeatureBase {

}
