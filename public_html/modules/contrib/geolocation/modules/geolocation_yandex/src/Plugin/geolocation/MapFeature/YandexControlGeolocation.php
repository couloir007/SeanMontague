<?php

namespace Drupal\geolocation_yandex\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;
use Drupal\geolocation\Plugin\geolocation\MapFeature\ControlElementBase;

/**
 * Provides Geolocation control element.
 */
#[MapFeature(
  id: 'yandex_control_geolocation',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Map Control - Geolocation'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Add geolocation to the map.'),
  type: 'yandex'
)]
class YandexControlGeolocation extends ControlElementBase {

}
