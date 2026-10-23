<?php

namespace Drupal\geolocation\Plugin\geolocation\TileLayerProvider;

use Drupal\geolocation\Attribute\TileLayerProvider;
use Drupal\geolocation\TileLayerProviderBase;
use Drupal\geolocation\TileLayerProviderInterface;

/**
 * Provides Jawg Maps tile layers.
 */
#[TileLayerProvider(id: 'geolocation_tile_jawg',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Jawg Maps'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://www.jawg.io/. Requires access token.'))]
class Jawg extends TileLayerProviderBase implements TileLayerProviderInterface {

  /**
   * Languages Jawg renders labels in.
   *
   * @var string[]
   */
  protected const LANGUAGES = ['de', 'en', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'ru', 'zh'];

  /**
   * Tile URL per layer option.
   *
   * @var string[]
   */
  protected array $urls = [
    'streets' => 'https://tile.jawg.io/jawg-streets/{z}/{x}/{y}.png',
    'terrain' => 'https://tile.jawg.io/jawg-terrain/{z}/{x}/{y}.png',
    'lagoon' => 'https://tile.jawg.io/jawg-lagoon/{z}/{x}/{y}.png',
    'sunny' => 'https://tile.jawg.io/jawg-sunny/{z}/{x}/{y}.png',
    'dark' => 'https://tile.jawg.io/jawg-dark/{z}/{x}/{y}.png',
    'light' => 'https://tile.jawg.io/jawg-light/{z}/{x}/{y}.png',
    'matrix' => 'https://tile.jawg.io/jawg-matrix/{z}/{x}/{y}.png',
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
          '@url' => 'https://www.jawg.io/lab/',
          '@provider' => 'Jawg Maps',
        ]),
      ],
      'use_current_language' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Use current user language'),
        '#description' => $this->t('Label the tiles in the interface language, where Jawg carries it: @languages.', [
          '@languages' => implode(', ', static::LANGUAGES),
        ]),
        '#default_value' => $settings['use_current_language'] ?? FALSE,
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
      $url .= (str_contains($url, '?') ? '&' : '?') . 'access-token=' . $this->configuration['access_token'];
    }

    if ($this->configuration['use_current_language'] ?? FALSE) {
      $language = $this->languageCode();

      // Jawg serves these ten and nothing else, so anything outside them is
      // left to its own default rather than asked for.
      if (in_array($language, static::LANGUAGES, TRUE)) {
        $url .= (str_contains($url, '?') ? '&' : '?') . 'lang=' . $language;
      }
    }

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttribution(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): string {
    return '<a href="https://jawg.io" title="Tiles Courtesy of Jawg Maps">&copy; Jawg</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'streets' => [
        'name' => $this->t('Streets'),
      ],
      'terrain' => [
        'name' => $this->t('Terrain'),
      ],
      'lagoon' => [
        'name' => $this->t('Lagoon'),
      ],
      'sunny' => [
        'name' => $this->t('Sunny'),
      ],
      'dark' => [
        'name' => $this->t('Dark'),
      ],
      'light' => [
        'name' => $this->t('Light'),
      ],
      'matrix' => [
        'name' => $this->t('Matrix'),
      ],
    ];
  }

}
