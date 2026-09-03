<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;

/**
 * Provides MapType control element.
 */
#[MapFeature(
  id: 'control_rotate',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Map Control - Rotate'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Add rotation control.'),
  type: 'google_maps'
)]
class GoogleControlRotate extends GoogleControlElementBase {

}
