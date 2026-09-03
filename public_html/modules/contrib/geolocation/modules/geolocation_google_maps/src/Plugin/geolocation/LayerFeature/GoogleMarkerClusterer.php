<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\LayerFeature;

use Drupal\geolocation\Attribute\LayerFeature;
use Drupal\geolocation\LayerFeatureBase;

/**
 * Provides marker clusterer.
 */
#[LayerFeature(id: 'marker_clusterer',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Marker Clusterer'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Group elements on the map.'), type: 'google_maps')]
class GoogleMarkerClusterer extends LayerFeatureBase {

  /**
   * {@inheritdoc}
   */
  protected array $scripts = [
    'https://unpkg.com/@googlemaps/markerclusterer/dist/index.min.js',
  ];

}
