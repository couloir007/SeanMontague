<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;

/**
 * Provides Fullscreen control element.
 */
#[MapFeature(
  id: 'control_fullscreen',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Map Control - Google Fullscreen'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Add button to toggle fullscreen.'),
  type: 'google_maps'
)]
class GoogleControlFullscreen extends GoogleControlElementBase {

}
