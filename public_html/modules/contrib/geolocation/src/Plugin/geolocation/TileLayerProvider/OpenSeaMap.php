<?php

namespace Drupal\geolocation\Plugin\geolocation\TileLayerProvider;

use Drupal\geolocation\Attribute\TileLayerProvider;
use Drupal\geolocation\TileLayerProviderBase;
use Drupal\geolocation\TileLayerProviderInterface;

/**
 * Provides OpenSeaMap tile layers.
 */
#[TileLayerProvider(id: 'geolocation_tile_openseamap',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('OpenSeaMap'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://www.openseamap.org/. Nautical seamark overlay.'))]
class OpenSeaMap extends TileLayerProviderBase implements TileLayerProviderInterface {

  /**
   * Tile URL per layer option.
   *
   * @var string[]
   */
  protected array $urls = [
    'seamark' => 'https://tiles.openseamap.org/seamark/{z}/{x}/{y}.png',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): array {
    return [
      'max_zoom' => 18,
      'overlay' => TRUE,
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
    return 'Map data: &copy; <a href="https://www.openseamap.org">OpenSeaMap</a> contributors';
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'seamark' => [
        'name' => $this->t('Seamarks'),
      ],
    ];
  }

}
