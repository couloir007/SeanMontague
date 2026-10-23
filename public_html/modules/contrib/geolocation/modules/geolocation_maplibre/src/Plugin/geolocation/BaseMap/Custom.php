<?php

namespace Drupal\geolocation_maplibre\Plugin\geolocation\BaseMap;

use Drupal\geolocation_maplibre\Attribute\BaseMap;
use Drupal\geolocation_maplibre\BaseMapBase;

/**
 * Provides a base map from any style URL.
 */
#[BaseMap(
  id: 'custom',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Custom'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Any style document, self hosted or from a service without a plugin of its own.')
)]
class Custom extends BaseMapBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [
      'style_url' => '',
      'attribution' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = []): array {
    $settings = $this->getSettings($settings);

    return [
      'style_url' => [
        '#type' => 'url',
        '#title' => $this->t('Style URL'),
        '#default_value' => $settings['style_url'],
        '#description' => $this->t('A style document following the MapLibre style specification, with any credentials the service wants already in the URL.'),
        '#maxlength' => 1024,
      ],
      'attribution' => [
        '#type' => 'textfield',
        '#title' => $this->t('Attribution'),
        '#default_value' => $settings['attribution'],
        '#description' => $this->t('Only needed where the style document does not carry its own.'),
        '#maxlength' => 1024,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getStyleUrl(array $settings): string {
    return $this->getSettings($settings)['style_url'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAttribution(array $settings): string {
    return $this->getSettings($settings)['attribution'];
  }

}
