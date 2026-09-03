<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;

/**
 * Provides StreetView control element.
 */
#[MapFeature(
  id: 'control_streetview',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Map Control - StreetView'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Add button to toggle map type.'),
  type: 'google_maps'
)]
class GoogleControlStreetView extends GoogleControlElementBase {

}
