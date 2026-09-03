<?php

namespace Drupal\geolocation_leaflet\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;
use Drupal\geolocation\Plugin\geolocation\MapFeature\GeolocationFieldWidgetMapConnector;

/**
 * Provides Recenter control element.
 */
#[MapFeature(
  id: 'leaflet_geometry_widget_map_connector',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Leaflet Geometry Widget Map Connector'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Internal use.'),
  type: 'all',
  hidden: TRUE,
)]
class LeafletGeometryWidgetMapConnector extends GeolocationFieldWidgetMapConnector {

  /**
   * {@inheritdoc}
   */
  protected array $scripts = [
    'https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js',
  ];

  /**
   * {@inheritdoc}
   */
  protected array $stylesheets = [
    'https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css',
  ];

}
