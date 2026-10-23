<?php

namespace Drupal\geolocation\Plugin\geolocation\TileLayerProvider;

use Drupal\geolocation\Attribute\TileLayerProvider;
use Drupal\geolocation\TileLayerProviderBase;
use Drupal\geolocation\TileLayerProviderInterface;

/**
 * Provides Azure Maps tile layers.
 */
#[TileLayerProvider(id: 'geolocation_tile_azuremaps',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Azure Maps'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://azure.microsoft.com/products/azure-maps/. Requires subscription key.'))]
class AzureMaps extends TileLayerProviderBase implements TileLayerProviderInterface {

  /**
   * Tile URL per layer option.
   *
   * @var string[]
   */
  protected array $urls = [
    'imagery' => 'https://atlas.microsoft.com/map/tile?api-version=2.0&tilesetId=microsoft.imagery&x={x}&y={y}&zoom={z}',
    'base_road' => 'https://atlas.microsoft.com/map/tile?api-version=2.0&tilesetId=microsoft.base.road&x={x}&y={y}&zoom={z}',
    'base_dark_grey' => 'https://atlas.microsoft.com/map/tile?api-version=2.0&tilesetId=microsoft.base.darkgrey&x={x}&y={y}&zoom={z}',
    'base_hybrid_road' => 'https://atlas.microsoft.com/map/tile?api-version=2.0&tilesetId=microsoft.base.hybrid.road&x={x}&y={y}&zoom={z}',
    'terra_main' => 'https://atlas.microsoft.com/map/tile?api-version=2.0&tilesetId=microsoft.terra.main&x={x}&y={y}&zoom={z}',
  ];

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings = [], ?array $context = NULL): array {
    return [
      'subscription_key' => [
        '#type' => 'textfield',
        '#title' => $this->t('Subscription key'),
        '#default_value' => $settings['subscription_key'] ?? '',
        '#description' => $this->t('Get your @key here <a href="@url">@provider</a>.', [
          '@key' => $this->t('Subscription key'),
          '@url' => 'https://azure.microsoft.com/products/azure-maps/',
          '@provider' => 'Azure Maps',
        ]),
      ],
      'use_current_language' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Use current user language'),
        '#description' => $this->t('Label the tiles in the interface language. Azure Maps falls back to its own neutral naming for languages it does not carry.'),
        '#default_value' => $settings['use_current_language'] ?? FALSE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): array {
    $defaults = [
      'imagery' => [
        'max_zoom' => 19,
      ],
      'base_road' => [
        'max_zoom' => 22,
      ],
      'base_dark_grey' => [
        'max_zoom' => 22,
      ],
      'base_hybrid_road' => [
        'max_zoom' => 22,
        'overlay' => TRUE,
      ],
      'terra_main' => [
        'max_zoom' => 19,
      ],
    ];

    return $defaults[static::getOptionTitleById($tile_layer_option_id)] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTileLayerUrl(string $tile_layer_option_id = 'default', array $settings = [], ?array $context = NULL): string {
    $url = $this->urls[static::getOptionTitleById($tile_layer_option_id)] ?? reset($this->urls);

    if ($this->configuration['subscription_key'] ?? FALSE) {
      $url .= (str_contains($url, '?') ? '&' : '?') . 'subscription-key=' . $this->configuration['subscription_key'];
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
    return 'See https://aka.ms/attribution for attribution requirements.';
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'imagery' => [
        'name' => $this->t('Imagery'),
      ],
      'base_road' => [
        'name' => $this->t('Base Road'),
      ],
      'base_dark_grey' => [
        'name' => $this->t('Base Dark Grey'),
      ],
      'base_hybrid_road' => [
        'name' => $this->t('Base Hybrid Road'),
      ],
      'terra_main' => [
        'name' => $this->t('Terra'),
      ],
    ];
  }

}
