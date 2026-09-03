<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;

/**
 * Provides Zoom control element.
 */
#[MapFeature(
  id: 'control_zoom',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Map Control - Zoom'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Add button to toggle map type.'),
  type: 'google_maps'
)]
class GoogleControlZoom extends GoogleControlElementBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    $settings = parent::getDefaultSettings();
    $settings['style'] = 'LARGE';

    return $settings;
  }

}
