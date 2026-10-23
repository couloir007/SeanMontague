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
   * Tile URL per layer option.
   *
   * @var string[]
   */
  protected array $urls = [
    'cycle' => 'https://{s}.tile.thunderforest.com/cycle/{z}/{x}/{y}.png',
    'transport' => 'https://{s}.tile.thunderforest.com/transport/{z}/{x}/{y}.png',
    'transport-dark' => 'https://{s}.tile.thunderforest.com/transport-dark/{z}/{x}/{y}.png',
    'spinal-map' => 'https://{s}.tile.thunderforest.com/spinal-map/{z}/{x}/{y}.png',
    'landscape' => 'https://{s}.tile.thunderforest.com/landscape/{z}/{x}/{y}.png',
    'outdoors' => 'https://{s}.tile.thunderforest.com/outdoors/{z}/{x}/{y}.png',
    'pioneer' => 'https://{s}.tile.thunderforest.com/pioneer/{z}/{x}/{y}.png',
    'mobile-atlas' => 'https://{s}.tile.thunderforest.com/mobile-atlas/{z}/{x}/{y}.png',
    'neighbourhood' => 'https://{s}.tile.thunderforest.com/neighbourhood/{z}/{x}/{y}.png',
  ];

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
          '@key' => $this->t('API key'),
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
    $url = $this->urls[static::getOptionTitleById($tile_layer_option_id)] ?? reset($this->urls);

    if ($this->configuration['apikey'] ?? FALSE) {
      $url .= (str_contains($url, '?') ? '&' : '?') . 'apikey=' . $this->configuration['apikey'];
    }

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttribution(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): string {
    return '&copy; <a href="https://www.thunderforest.com/">Thunderforest</a>, map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'cycle' => [
        'name' => $this->t('OpenCycleMap'),
      ],
      'transport' => [
        'name' => $this->t('Transport'),
      ],
      'transport-dark' => [
        'name' => $this->t('Transport Dark'),
      ],
      'spinal-map' => [
        'name' => $this->t('Spinal Map'),
      ],
      'landscape' => [
        'name' => $this->t('Landscape'),
      ],
      'outdoors' => [
        'name' => $this->t('Outdoors'),
      ],
      'pioneer' => [
        'name' => $this->t('Pioneer'),
      ],
      'mobile-atlas' => [
        'name' => $this->t('Mobile Atlas'),
      ],
      'neighbourhood' => [
        'name' => $this->t('Neighbourhood'),
      ],
    ];
  }

}
