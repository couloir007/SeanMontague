<?php

namespace Drupal\geolocation\Plugin\geolocation\Location;

use Drupal\geolocation\Attribute\Location;
use Drupal\geolocation\LocationBase;
use Drupal\geolocation\LocationInterface;

/**
 * Fixed coordinates map center.
 *
 * PluginID for compatibility with v1.
 */
#[Location(
  id: 'fixed_value',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Fixed coordinates'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Use preset fixed values as center.')
)]
class FixedCoordinates extends LocationBase implements LocationInterface {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [
      'latitude' => 0,
      'longitude' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(?string $location_option_id = NULL, array $settings = [], $context = NULL): array {
    $settings = $this->getSettings($settings);

    return [
      'latitude' => [
        '#type' => 'textfield',
        '#title' => $this->t('Latitude'),
        '#default_value' => $settings['latitude'] ?? 0,
        '#size' => 60,
        '#maxlength' => 128,
      ],
      'longitude' => [
        '#type' => 'textfield',
        '#title' => $this->t('Longitude'),
        '#default_value' => $settings['longitude'] ?? 0,
        '#size' => 60,
        '#maxlength' => 128,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCoordinates(string $location_option_id, array $location_option_settings, $context = NULL): array {
    $settings = $this->getSettings($location_option_settings);

    return [
      'lat' => (float) $settings['latitude'],
      'lng' => (float) $settings['longitude'],
    ];
  }

}
