<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;

/**
 * Provides Camera control element.
 */
#[MapFeature(
  id: 'control_camera',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Map Control - Camera'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Add combined pan, zoom, tilt and rotate control. Supersedes the separate zoom and rotate controls.'),
  type: 'google_maps'
)]
class GoogleControlCamera extends GoogleControlElementBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    $settings = parent::getDefaultSettings();
    $settings['position'] = 'INLINE_START_BLOCK_END';

    return $settings;
  }

}
