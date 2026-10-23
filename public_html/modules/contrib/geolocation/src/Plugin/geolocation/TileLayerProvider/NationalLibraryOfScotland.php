<?php

namespace Drupal\geolocation\Plugin\geolocation\TileLayerProvider;

use Drupal\geolocation\Attribute\TileLayerProvider;
use Drupal\geolocation\TileLayerProviderBase;
use Drupal\geolocation\TileLayerProviderInterface;

/**
 * Provides National Library of Scotland tile layers.
 */
#[TileLayerProvider(id: 'geolocation_tile_nls',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('National Library of Scotland'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://maps.nls.uk/. Historic maps of Great Britain, requires a MapTiler API key.'))]
class NationalLibraryOfScotland extends TileLayerProviderBase implements TileLayerProviderInterface {

  /**
   * Tile URL per layer option.
   *
   * @var string[]
   */
  protected array $urls = [
    'osgb63k1885' => 'https://api.maptiler.com/tiles/uk-osgb63k1885/{z}/{x}/{y}.jpg',
    'osgb1888' => 'https://api.maptiler.com/tiles/uk-osgb1888/{z}/{x}/{y}.jpg',
    'osgb10k1888' => 'https://api.maptiler.com/tiles/uk-osgb10k1888/{z}/{x}/{y}.jpg',
    'osgb1919' => 'https://api.maptiler.com/tiles/uk-osgb1919/{z}/{x}/{y}.jpg',
    'osgb25k1937' => 'https://api.maptiler.com/tiles/uk-osgb25k1937/{z}/{x}/{y}.jpg',
    'osgb63k1955' => 'https://api.maptiler.com/tiles/uk-osgb63k1955/{z}/{x}/{y}.jpg',
    'oslondon1k1893' => 'https://api.maptiler.com/tiles/uk-oslondon1k1893/{z}/{x}/{y}.jpg',
  ];

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings = [], ?array $context = NULL): array {
    return [
      'key' => [
        '#type' => 'textfield',
        '#title' => $this->t('MapTiler API key'),
        '#default_value' => $settings['key'] ?? '',
        '#description' => $this->t('Get your @key here <a href="@url">@provider</a>.', [
          '@key' => $this->t('MapTiler API key'),
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
      'min_zoom' => 1,
      'max_zoom' => 18,
      'bounds' => [[49.6, -12], [61.7, 3]],
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
    return '<a href="https://geo.nls.uk/maps/">National Library of Scotland Historic Maps</a>';
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'osgb63k1885' => [
        'name' => $this->t('OS 1:63k, 1885'),
      ],
      'osgb1888' => [
        'name' => $this->t('OS 1:1M, 1888'),
      ],
      'osgb10k1888' => [
        'name' => $this->t('OS 1:10k, 1888'),
      ],
      'osgb1919' => [
        'name' => $this->t('OS 1:1M, 1919'),
      ],
      'osgb25k1937' => [
        'name' => $this->t('OS 1:25k, 1937'),
      ],
      'osgb63k1955' => [
        'name' => $this->t('OS 1:63k, 1955'),
      ],
      'oslondon1k1893' => [
        'name' => $this->t('OS London 1:1k, 1893'),
      ],
    ];
  }

}
