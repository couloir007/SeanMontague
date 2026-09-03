<?php

namespace Drupal\geolocation_baidu\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;
use Drupal\geolocation\Plugin\geolocation\MapFeature\ControlElementBase;

/**
 * Provides map zoom control support.
 */
#[MapFeature(
  id: 'baidu_zoom_control',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Baidu Zoom control'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Add map zoom controls.'),
  type: 'baidu'
)]
class BaiduZoomControl extends ControlElementBase {}
