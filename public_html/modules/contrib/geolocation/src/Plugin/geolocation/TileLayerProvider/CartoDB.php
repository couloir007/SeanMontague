<?php

namespace Drupal\geolocation\Plugin\geolocation\TileLayerProvider;

use Drupal\geolocation\Attribute\TileLayerProvider;
use Drupal\geolocation\TileLayerProviderBase;
use Drupal\geolocation\TileLayerProviderInterface;

/**
 * Provides CARTO tile layers.
 */
#[TileLayerProvider(id: 'geolocation_tile_cartodb',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('CARTO'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://carto.com/basemaps/. Requires API key.'))]
class CartoDB extends TileLayerProviderBase implements TileLayerProviderInterface {

  /**
   * Tile URL per layer option.
   *
   * @var string[]
   */
  protected array $urls = [
    'positron' => 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png',
    'positron_no_labels' => 'https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}.png',
    'positron_only_labels' => 'https://{s}.basemaps.cartocdn.com/light_only_labels/{z}/{x}/{y}.png',
    'dark_matter' => 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png',
    'dark_matter_no_labels' => 'https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}.png',
    'dark_matter_only_labels' => 'https://{s}.basemaps.cartocdn.com/dark_only_labels/{z}/{x}/{y}.png',
    'voyager' => 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png',
    'voyager_no_labels' => 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager_nolabels/{z}/{x}/{y}.png',
    'voyager_only_labels' => 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager_only_labels/{z}/{x}/{y}.png',
    'voyager_labels_under' => 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager_labels_under/{z}/{x}/{y}.png',
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
          '@url' => 'https://carto.com/basemaps/apikey/',
          '@provider' => 'CARTO',
        ]),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): array {
    $defaults = [
      'positron' => [
        'max_zoom' => 20,
      ],
      'positron_no_labels' => [
        'max_zoom' => 20,
      ],
      'positron_only_labels' => [
        'max_zoom' => 20,
        'overlay' => TRUE,
      ],
      'dark_matter' => [
        'max_zoom' => 20,
      ],
      'dark_matter_no_labels' => [
        'max_zoom' => 20,
      ],
      'dark_matter_only_labels' => [
        'max_zoom' => 20,
        'overlay' => TRUE,
      ],
      'voyager' => [
        'max_zoom' => 20,
      ],
      'voyager_no_labels' => [
        'max_zoom' => 20,
      ],
      'voyager_only_labels' => [
        'max_zoom' => 20,
        'overlay' => TRUE,
      ],
      'voyager_labels_under' => [
        'max_zoom' => 20,
      ],
    ];

    return $defaults[static::getOptionTitleById($tile_layer_option_id)] ?? [];
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
    return '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>';
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'positron' => [
        'name' => $this->t('Positron'),
      ],
      'positron_no_labels' => [
        'name' => $this->t('Positron, no labels'),
      ],
      'positron_only_labels' => [
        'name' => $this->t('Positron, only labels'),
      ],
      'dark_matter' => [
        'name' => $this->t('Dark Matter'),
      ],
      'dark_matter_no_labels' => [
        'name' => $this->t('Dark Matter, no labels'),
      ],
      'dark_matter_only_labels' => [
        'name' => $this->t('Dark Matter, only labels'),
      ],
      'voyager' => [
        'name' => $this->t('Voyager'),
      ],
      'voyager_no_labels' => [
        'name' => $this->t('Voyager, no labels'),
      ],
      'voyager_only_labels' => [
        'name' => $this->t('Voyager, only labels'),
      ],
      'voyager_labels_under' => [
        'name' => $this->t('Voyager, labels under'),
      ],
    ];
  }

}
