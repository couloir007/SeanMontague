<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Plugin\geolocation\MapFeature\ControlElementBase;

/**
 * Class ControlMapFeatureBase.
 */
abstract class GoogleControlElementBase extends ControlElementBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    $settings = parent::getDefaultSettings();
    $settings['position'] = 'RIGHT_CENTER';
    $settings['behavior'] = 'default';

    return $settings;
  }

}
