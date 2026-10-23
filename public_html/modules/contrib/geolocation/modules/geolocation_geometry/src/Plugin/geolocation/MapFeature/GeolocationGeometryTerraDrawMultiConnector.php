<?php

namespace Drupal\geolocation_geometry\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;

/**
 * Connects one map of the multi geometry Terra Draw widget to one field item.
 */
#[MapFeature(
  id: 'geolocation_geometry_terra_draw_multi_connector',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation Geometry Terra Draw Multi Connector'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Internal use.'),
  type: 'all',
  hidden: TRUE,
)]
class GeolocationGeometryTerraDrawMultiConnector extends GeolocationGeometryTerraDrawConnectorBase {}
