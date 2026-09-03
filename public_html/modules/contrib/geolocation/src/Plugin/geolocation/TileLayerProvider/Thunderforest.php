<?php

namespace Drupal\geolocation\Plugin\geolocation\TileLayerProvider;

use Drupal\geolocation\Attribute\TileLayerProvider;
use Drupal\geolocation\TileLayerProviderBase;
use Drupal\geolocation\TileLayerProviderInterface;

/**
 * Provides Thunderforest tile layers.
 */
#[TileLayerProvider(id: 'geolocation_tile_thunderforest',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Thunderforest'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://www.thunderforest.com/. Requires API key.'))]
class Thunderforest extends TileLayerProviderBase implements TileLayerProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings = [], ?array $context = NULL): array {
    return [
      'apikey' => [
        '#type' => 'textfield',
        '#title' => $this->t('API key'),
        '#default_value' => $settings['apikey'] ?? '',
        '#description' => $this->t('Get your @key here <a href="@url">@provider</a>.', [
          '@key' => $this->t('API Key'),
          '@url' => 'https://www.thunderforest.com/',
          '@provider' => 'Thunderforest',
        ]),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): array {
    return [
      'max_zoom' => 22,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTileLayerUrl(string $tile_layer_option_id = 'default', array $settings = [], ?array $context = NULL): string {
    return 'https://{s}.tile.thunderforest.com/' . $this->getOptionTitleById($tile_layer_option_id) . '/{z}/{x}/{y}.png?apikey=' . $this->configuration['apikey'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAttribution(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): string {
    return '&copy; <a href="https://www.thunderforest.com/">Thunderforest</a>';
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'cycle' => [
        'name' => $this->t('OpenCycleMap'),
        'description' => $this->t('Online <a href="@url" target="_blank">Description</a>', ['@url' => 'https://www.thunderforest.com/maps/cycle/']),
      ],
      'transport' => [
        'name' => $this->t('Transport'),
        'description' => $this->t('Online <a href="@url" target="_blank">Description</a>', ['@url' => 'https://www.thunderforest.com/maps/transport/']),
      ],
      'transport-dark' => [
        'name' => $this->t('Transport Dark'),
        'description' => $this->t('Online <a href="@url" target="_blank">Description</a>', ['@url' => 'https://www.thunderforest.com/maps/transport-dark/']),
      ],
      'spinal-map' => [
        'name' => $this->t('Spinal Map'),
        'description' => $this->t('Online <a href="@url" target="_blank">Description</a>', ['@url' => 'https://www.thunderforest.com/maps/spinal-map/']),
      ],
      'landscape' => [
        'name' => $this->t('Landscape'),
        'description' => $this->t('Online <a href="@url" target="_blank">Description</a>', ['@url' => 'https://www.thunderforest.com/maps/landscape/']),
      ],
      'outdoors' => [
        'name' => $this->t('Outdoors'),
        'description' => $this->t('Online <a href="@url" target="_blank">Description</a>', ['@url' => 'https://www.thunderforest.com/maps/outdoors/']),
      ],
      'pioneer' => [
        'name' => $this->t('Pioneer'),
        'description' => $this->t('Online <a href="@url" target="_blank">Description</a>', ['@url' => 'https://www.thunderforest.com/maps/pioneer/']),
      ],
      'mobile-atlas' => [
        'name' => $this->t('Mobile Atlas'),
        'description' => $this->t('Online <a href="@url" target="_blank">Description</a>', ['@url' => 'https://www.thunderforest.com/maps/mobile-atlas/']),
      ],
      'neighbourhood' => [
        'name' => $this->t('Neighbourhood'),
        'description' => $this->t('Online <a href="@url" target="_blank">Description</a>', ['@url' => 'https://www.thunderforest.com/maps/neighbourhood/']),
      ],
    ];
  }

}
