<?php

namespace Drupal\geolocation\Plugin\geolocation\TileLayerProvider;

use Drupal\geolocation\Attribute\TileLayerProvider;
use Drupal\geolocation\TileLayerProviderBase;
use Drupal\geolocation\TileLayerProviderInterface;

/**
 * Provides Freemap.sk tile layers.
 */
#[TileLayerProvider(id: 'geolocation_tile_freemapsk',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Freemap.sk'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://www.freemap.sk/. Outdoor map of Slovakia.'))]
class FreeMapSk extends TileLayerProviderBase implements TileLayerProviderInterface {

  /**
   * Tile URL per layer option.
   *
   * @var string[]
   */
  protected array $urls = [
    'default' => 'https://{s}.freemap.sk/T/{z}/{x}/{y}.jpeg',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): array {
    return [
      'min_zoom' => 8,
      'max_zoom' => 16,
      'bounds' => [[47.204642, 15.996093], [49.830896, 22.576904]],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTileLayerUrl(string $tile_layer_option_id = 'default', array $settings = [], ?array $context = NULL): string {
    return $this->urls[static::getOptionTitleById($tile_layer_option_id)] ?? reset($this->urls);
  }

  /**
   * {@inheritdoc}
   */
  public function getAttribution(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): string {
    return '&copy; <a href="https://www.freemap.sk">Freemap.sk</a>, map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'default' => [
        'name' => $this->t('Freemap.sk'),
      ],
    ];
  }

}
