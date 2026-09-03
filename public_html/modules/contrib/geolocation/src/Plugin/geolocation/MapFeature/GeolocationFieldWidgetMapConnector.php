<?php

namespace Drupal\geolocation\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;
use Drupal\geolocation\MapFeatureBase;

/**
 * Provides Recenter control element.
 */
#[MapFeature(
  id: 'geolocation_field_widget_map_connector',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('GeolocationFieldWidgetMapConnector'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Internal use.'),
  type: 'all',
  hidden: TRUE,
)]
class GeolocationFieldWidgetMapConnector extends MapFeatureBase {}
