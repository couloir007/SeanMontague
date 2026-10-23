<?php

namespace Drupal\geolocation_maplibre\Plugin\geolocation\BaseMap;

use Drupal\geolocation_maplibre\Attribute\BaseMap;
use Drupal\geolocation_maplibre\BaseMapBase;

/**
 * Provides the MapLibre demo base map.
 */
#[BaseMap(
  id: 'maplibre_demo',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('MapLibre Demo'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('The style MapLibre ships to demonstrate its renderer. Needs no account, but draws country outlines and coastlines only - no streets, towns or points of interest - and its labels cannot be localized. Meant for development rather than for a site.')
)]
class MapLibreDemo extends BaseMapBase {

  /**
   * {@inheritdoc}
   */
  protected array $styles = [
    'default' => 'https://demotiles.maplibre.org/style.json',
  ];

}
