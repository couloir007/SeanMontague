<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;

/**
 * Provides Zoom control element.
 */
#[MapFeature(
  id: 'control_zoom',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Map Control - Zoom'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Add buttons to zoom in and out.'),
  type: 'google_maps'
)]
class GoogleControlZoom extends GoogleControlElementBase {

}
