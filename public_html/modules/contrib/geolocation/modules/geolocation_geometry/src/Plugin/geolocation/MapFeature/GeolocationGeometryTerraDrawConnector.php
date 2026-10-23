<?php

namespace Drupal\geolocation_geometry\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;

/**
 * Connects the Terra Draw geometry widget to the map it draws on.
 */
#[MapFeature(
  id: 'geolocation_geometry_terra_draw_connector',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation Geometry Terra Draw Connector'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Internal use.'),
  type: 'all',
  hidden: TRUE,
)]
class GeolocationGeometryTerraDrawConnector extends GeolocationGeometryTerraDrawConnectorBase {}
