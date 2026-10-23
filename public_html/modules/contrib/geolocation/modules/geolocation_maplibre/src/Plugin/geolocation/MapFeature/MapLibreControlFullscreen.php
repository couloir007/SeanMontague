<?php

namespace Drupal\geolocation_maplibre\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;
use Drupal\geolocation\Plugin\geolocation\MapFeature\ControlElementBase;

/**
 * Provides the MapLibre fullscreen control.
 */
#[MapFeature(
  id: 'maplibre_control_fullscreen',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Map Control - Fullscreen'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Add a button to show the map fullscreen.'),
  type: 'maplibre'
)]
class MapLibreControlFullscreen extends ControlElementBase {

}
