<?php

namespace Drupal\geolocation\Plugin\geolocation\TileLayerProvider;

use Drupal\geolocation\Attribute\TileLayerProvider;
use Drupal\geolocation\TileLayerProviderBase;
use Drupal\geolocation\TileLayerProviderInterface;

/**
 * Provides TomTom tile layers.
 */
#[TileLayerProvider(id: 'geolocation_tile_tomtom',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('TomTom'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://developer.tomtom.com/. Requires API key.'))]
class TomTom extends TileLayerProviderBase implements TileLayerProviderInterface {

  /**
   * Tile URL per layer option.
   *
   * @var string[]
   */
  protected array $urls = [
    'basic' => 'https://{s}.api.tomtom.com/map/1/tile/basic/main/{z}/{x}/{y}.png',
    'hybrid' => 'https://{s}.api.tomtom.com/map/1/tile/hybrid/main/{z}/{x}/{y}.png',
    'labels' => 'https://{s}.api.tomtom.com/map/1/tile/labels/main/{z}/{x}/{y}.png',
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
          '@url' => 'https://developer.tomtom.com/',
          '@provider' => 'TomTom',
        ]),
      ],
      'use_current_language' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Use current user language'),
        '#description' => $this->t('Label the tiles in the interface language. TomTom falls back to its own neutral naming for languages it does not carry.'),
        '#default_value' => $settings['use_current_language'] ?? FALSE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): array {
    $defaults = [
      'basic' => [
        'max_zoom' => 22,
      ],
      'hybrid' => [
        'max_zoom' => 22,
      ],
      'labels' => [
        'max_zoom' => 22,
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

    if ($this->configuration['key'] ?? FALSE) {
      $url .= (str_contains($url, '?') ? '&' : '?') . 'key=' . $this->configuration['key'];
    }

    if ($this->configuration['use_current_language'] ?? FALSE) {
      $language = $this->languageCode();
      $url .= (str_contains($url, '?') ? '&' : '?') . 'language=' . $language;
    }

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttribution(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): string {
    return '<a href="https://tomtom.com" target="_blank">&copy; 1992 - 2024 TomTom.</a> ';
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'basic' => [
        'name' => $this->t('Basic'),
      ],
      'hybrid' => [
        'name' => $this->t('Hybrid'),
      ],
      'labels' => [
        'name' => $this->t('Labels'),
      ],
    ];
  }

}
