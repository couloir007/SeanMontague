<?php

namespace Drupal\geolocation\Plugin\geolocation\TileLayerProvider;

use Drupal\geolocation\Attribute\TileLayerProvider;
use Drupal\geolocation\TileLayerProviderBase;
use Drupal\geolocation\TileLayerProviderInterface;

/**
 * Provides MapTiler tile layers.
 */
#[TileLayerProvider(id: 'geolocation_tile_maptiler',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('MapTiler'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://www.maptiler.com/. Requires API key.'))]
class MapTiler extends TileLayerProviderBase implements TileLayerProviderInterface {

  /**
   * Tile URL per layer option.
   *
   * @var string[]
   */
  protected array $urls = [
    'streets' => 'https://api.maptiler.com/maps/streets-v2/256/{z}/{x}/{y}.png',
    'basic' => 'https://api.maptiler.com/maps/basic-v2/256/{z}/{x}/{y}.png',
    'bright' => 'https://api.maptiler.com/maps/bright-v2/256/{z}/{x}/{y}.png',
    'outdoor' => 'https://api.maptiler.com/maps/outdoor-v2/256/{z}/{x}/{y}.png',
    'topo' => 'https://api.maptiler.com/maps/topo-v2/256/{z}/{x}/{y}.png',
    'winter' => 'https://api.maptiler.com/maps/winter-v2/256/{z}/{x}/{y}.png',
    'satellite' => 'https://api.maptiler.com/maps/satellite/256/{z}/{x}/{y}.jpg',
    'hybrid' => 'https://api.maptiler.com/maps/hybrid/256/{z}/{x}/{y}.jpg',
    'toner' => 'https://api.maptiler.com/maps/toner-v2/256/{z}/{x}/{y}.png',
    'dataviz' => 'https://api.maptiler.com/maps/dataviz/256/{z}/{x}/{y}.png',
    'ocean' => 'https://api.maptiler.com/maps/ocean/256/{z}/{x}/{y}.png',
  ];

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings = [], ?array $context = NULL): array {
    return [
      'key' => [
        '#type' => 'textfield',
        '#title' => $this->t('API key'),
        '#default_value' => $settings['key'] ?? '',
        '#description' => $this->t('Get your @key here <a href="@url">@provider</a>.', [
          '@key' => $this->t('API key'),
          '@url' => 'https://www.maptiler.com/cloud/',
          '@provider' => 'MapTiler',
        ]),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): array {
    return [
      'max_zoom' => 21,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTileLayerUrl(string $tile_layer_option_id = 'default', array $settings = [], ?array $context = NULL): string {
    $url = $this->urls[static::getOptionTitleById($tile_layer_option_id)] ?? reset($this->urls);

    if ($this->configuration['key'] ?? FALSE) {
      $url .= (str_contains($url, '?') ? '&' : '?') . 'key=' . $this->configuration['key'];
    }

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttribution(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): string {
    return '&copy; <a href="https://www.maptiler.com/copyright/">MapTiler</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'streets' => [
        'name' => $this->t('Streets'),
      ],
      'basic' => [
        'name' => $this->t('Basic'),
      ],
      'bright' => [
        'name' => $this->t('Bright'),
      ],
      'outdoor' => [
        'name' => $this->t('Outdoor'),
      ],
      'topo' => [
        'name' => $this->t('Topo'),
      ],
      'winter' => [
        'name' => $this->t('Winter'),
      ],
      'satellite' => [
        'name' => $this->t('Satellite'),
      ],
      'hybrid' => [
        'name' => $this->t('Hybrid'),
      ],
      'toner' => [
        'name' => $this->t('Toner'),
      ],
      'dataviz' => [
        'name' => $this->t('Dataviz'),
      ],
      'ocean' => [
        'name' => $this->t('Ocean'),
      ],
    ];
  }

}
