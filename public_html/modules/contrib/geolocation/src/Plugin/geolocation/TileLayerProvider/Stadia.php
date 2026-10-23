<?php

namespace Drupal\geolocation\Plugin\geolocation\TileLayerProvider;

use Drupal\geolocation\Attribute\TileLayerProvider;
use Drupal\geolocation\TileLayerProviderBase;
use Drupal\geolocation\TileLayerProviderInterface;

/**
 * Provides Stadia Maps tile layers.
 */
#[TileLayerProvider(id: 'geolocation_tile_stadia',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Stadia Maps'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://stadiamaps.com/. Requires API key unless the domain is allowed in the Stadia account.'))]
class Stadia extends TileLayerProviderBase implements TileLayerProviderInterface {

  /**
   * Tile URL per layer option.
   *
   * @var string[]
   */
  protected array $urls = [
    'alidade_smooth' => 'https://tiles.stadiamaps.com/tiles/alidade_smooth/{z}/{x}/{y}.png',
    'alidade_smooth_dark' => 'https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/{z}/{x}/{y}.png',
    'alidade_satellite' => 'https://tiles.stadiamaps.com/tiles/alidade_satellite/{z}/{x}/{y}.jpg',
    'outdoors' => 'https://tiles.stadiamaps.com/tiles/outdoors/{z}/{x}/{y}.png',
    'osm_bright' => 'https://tiles.stadiamaps.com/tiles/osm_bright/{z}/{x}/{y}.png',
    'stamen_toner' => 'https://tiles.stadiamaps.com/tiles/stamen_toner/{z}/{x}/{y}.png',
    'stamen_toner_lite' => 'https://tiles.stadiamaps.com/tiles/stamen_toner_lite/{z}/{x}/{y}.png',
    'stamen_watercolor' => 'https://tiles.stadiamaps.com/tiles/stamen_watercolor/{z}/{x}/{y}.jpg',
    'stamen_terrain' => 'https://tiles.stadiamaps.com/tiles/stamen_terrain/{z}/{x}/{y}.png',
    'stamen_toner_labels' => 'https://tiles.stadiamaps.com/tiles/stamen_toner_labels/{z}/{x}/{y}.png',
    'stamen_terrain_labels' => 'https://tiles.stadiamaps.com/tiles/stamen_terrain_labels/{z}/{x}/{y}.png',
  ];

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings = [], ?array $context = NULL): array {
    return [
      'api_key' => [
        '#type' => 'textfield',
        '#title' => $this->t('API key'),
        '#default_value' => $settings['api_key'] ?? '',
        '#description' => $this->t('Get your @key here <a href="@url">@provider</a>.', [
          '@key' => $this->t('API key'),
          '@url' => 'https://client.stadiamaps.com/',
          '@provider' => 'Stadia Maps',
        ]),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): array {
    $defaults = [
      'alidade_smooth' => [
        'max_zoom' => 20,
      ],
      'alidade_smooth_dark' => [
        'max_zoom' => 20,
      ],
      'alidade_satellite' => [
        'max_zoom' => 20,
      ],
      'outdoors' => [
        'max_zoom' => 20,
      ],
      'osm_bright' => [
        'max_zoom' => 20,
      ],
      'stamen_toner' => [
        'max_zoom' => 20,
      ],
      'stamen_toner_lite' => [
        'max_zoom' => 20,
      ],
      'stamen_watercolor' => [
        'min_zoom' => 1,
        'max_zoom' => 16,
      ],
      'stamen_terrain' => [
        'max_zoom' => 18,
      ],
      'stamen_toner_labels' => [
        'max_zoom' => 20,
        'overlay' => TRUE,
      ],
      'stamen_terrain_labels' => [
        'max_zoom' => 18,
        'overlay' => TRUE,
      ],
    ];

    return $defaults[static::getOptionTitleById($tile_layer_option_id)] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTileLayerUrl(string $tile_layer_option_id = 'default', array $settings = [], ?array $context = NULL): string {
    $url = $this->urls[static::getOptionTitleById($tile_layer_option_id)] ?? reset($this->urls);

    if ($this->configuration['api_key'] ?? FALSE) {
      $url .= (str_contains($url, '?') ? '&' : '?') . 'api_key=' . $this->configuration['api_key'];
    }

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttribution(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): string {
    return '&copy; <a href="https://stadiamaps.com/">Stadia Maps</a> &copy; <a href="https://openmaptiles.org/">OpenMapTiles</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'alidade_smooth' => [
        'name' => $this->t('Alidade Smooth'),
      ],
      'alidade_smooth_dark' => [
        'name' => $this->t('Alidade Smooth Dark'),
      ],
      'alidade_satellite' => [
        'name' => $this->t('Alidade Satellite'),
      ],
      'outdoors' => [
        'name' => $this->t('Outdoors'),
      ],
      'osm_bright' => [
        'name' => $this->t('OSM Bright'),
      ],
      'stamen_toner' => [
        'name' => $this->t('Stamen Toner'),
      ],
      'stamen_toner_lite' => [
        'name' => $this->t('Stamen Toner Lite'),
      ],
      'stamen_watercolor' => [
        'name' => $this->t('Stamen Watercolor'),
      ],
      'stamen_terrain' => [
        'name' => $this->t('Stamen Terrain'),
      ],
      'stamen_toner_labels' => [
        'name' => $this->t('Stamen Toner Labels'),
      ],
      'stamen_terrain_labels' => [
        'name' => $this->t('Stamen Terrain Labels'),
      ],
    ];
  }

}
