<?php

namespace Drupal\geolocation_yandex\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;
use Drupal\geolocation\Plugin\geolocation\MapFeature\ControlElementBase;

/**
 * Provides Zoom control element.
 */
#[MapFeature(
  id: 'yandex_control_zoom',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Map Control - Zoom'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Add buttons to zoom the map.'),
  type: 'yandex'
)]
class YandexControlZoom extends ControlElementBase {

}
