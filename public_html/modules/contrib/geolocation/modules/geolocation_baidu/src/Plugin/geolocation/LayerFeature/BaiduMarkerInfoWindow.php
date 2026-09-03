<?php

namespace Drupal\geolocation_baidu\Plugin\geolocation\LayerFeature;

use Drupal\geolocation\Attribute\LayerFeature;
use Drupal\geolocation\LayerFeatureBase;

/**
 * Provides marker infowindow.
 */
#[LayerFeature(id: 'baidu_marker_infowindow',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Marker InfoWindow'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Open InfoWindow on Marker click.'), type: 'baidu')]
class BaiduMarkerInfoWindow extends LayerFeatureBase {}
