<?php

namespace Drupal\geolocation\Plugin\geolocation\TileLayerProvider;

use Drupal\geolocation\Attribute\TileLayerProvider;
use Drupal\geolocation\TileLayerProviderBase;
use Drupal\geolocation\TileLayerProviderInterface;

/**
 * Provides Mapbox tile layers.
 */
#[TileLayerProvider(id: 'geolocation_tile_mapbox',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Mapbox'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://www.mapbox.com/. Requires access token.'))]
class MapBox extends TileLayerProviderBase implements TileLayerProviderInterface {

  /**
   * Tile URL per layer option.
   *
   * @var string[]
   */
  protected array $urls = [
    'streets' => 'https://api.mapbox.com/styles/v1/mapbox/streets-v12/tiles/256/{z}/{x}/{y}',
    'outdoors' => 'https://api.mapbox.com/styles/v1/mapbox/outdoors-v12/tiles/256/{z}/{x}/{y}',
    'light' => 'https://api.mapbox.com/styles/v1/mapbox/light-v11/tiles/256/{z}/{x}/{y}',
    'dark' => 'https://api.mapbox.com/styles/v1/mapbox/dark-v11/tiles/256/{z}/{x}/{y}',
    'satellite' => 'https://api.mapbox.com/styles/v1/mapbox/satellite-v9/tiles/256/{z}/{x}/{y}',
    'satellite_streets' => 'https://api.mapbox.com/styles/v1/mapbox/satellite-streets-v12/tiles/256/{z}/{x}/{y}',
  ];

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings = [], ?array $context = NULL): array {
    return [
      'access_token' => [
        '#type' => 'textfield',
        '#title' => $this->t('Access token'),
        '#default_value' => $settings['access_token'] ?? '',
        '#description' => $this->t('Get your @key here <a href="@url">@provider</a>.', [
          '@key' => $this->t('Access token'),
          '@url' => 'https://www.mapbox.com/',
          '@provider' => 'Mapbox',
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

    if ($this->configuration['access_token'] ?? FALSE) {
      $url .= (str_contains($url, '?') ? '&' : '?') . 'access_token=' . $this->configuration['access_token'];
    }

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttribution(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): string {
    return '&copy; <a href="https://www.mapbox.com/about/maps/">Mapbox</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'streets' => [
        'name' => $this->t('Streets'),
      ],
      'outdoors' => [
        'name' => $this->t('Outdoors'),
      ],
      'light' => [
        'name' => $this->t('Light'),
      ],
      'dark' => [
        'name' => $this->t('Dark'),
      ],
      'satellite' => [
        'name' => $this->t('Satellite'),
      ],
      'satellite_streets' => [
        'name' => $this->t('Satellite Streets'),
      ],
    ];
  }

}
