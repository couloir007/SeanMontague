<?php

namespace Drupal\geolocation_leaflet\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;
use Drupal\geolocation\Plugin\geolocation\MapFeature\ControlCustomElementBase;

/**
 * Provides Fullscreen control element.
 */
#[MapFeature(
  id: 'leaflet_control_fullscreen',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Map Control - Fullscreen'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Add button to toggle fullscreen.'),
  type: 'leaflet'
)]
class LeafletControlFullscreen extends ControlCustomElementBase {

  /**
   * {@inheritdoc}
   */
  protected array $scripts = [
    'https://unpkg.com/leaflet-fullscreen@1.0.2/dist/Leaflet.fullscreen.min.js',
  ];

  /**
   * {@inheritdoc}
   */
  protected array $stylesheets = [
    'https://unpkg.com/leaflet-fullscreen@1.0.2/dist/leaflet.fullscreen.css',
  ];

}
