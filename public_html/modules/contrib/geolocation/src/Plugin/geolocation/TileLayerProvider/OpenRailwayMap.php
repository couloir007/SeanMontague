<?php

namespace Drupal\geolocation\Plugin\geolocation\TileLayerProvider;

use Drupal\geolocation\Attribute\TileLayerProvider;
use Drupal\geolocation\TileLayerProviderBase;
use Drupal\geolocation\TileLayerProviderInterface;

/**
 * Provides OpenRailwayMap tile layers.
 */
#[TileLayerProvider(id: 'geolocation_tile_openrailwaymap',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('OpenRailwayMap'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://www.openrailwaymap.org/. Railway infrastructure overlay.'))]
class OpenRailwayMap extends TileLayerProviderBase implements TileLayerProviderInterface {

  /**
   * Tile URL per layer option.
   *
   * @var string[]
   */
  protected array $urls = [
    'standard' => 'https://{s}.tiles.openrailwaymap.org/standard/{z}/{x}/{y}.png',
    'maxspeed' => 'https://{s}.tiles.openrailwaymap.org/maxspeed/{z}/{x}/{y}.png',
    'electrification' => 'https://{s}.tiles.openrailwaymap.org/electrification/{z}/{x}/{y}.png',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): array {
    return [
      'max_zoom' => 19,
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
    return 'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors | Map style: &copy; <a href="https://www.OpenRailwayMap.org">OpenRailwayMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)';
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'standard' => [
        'name' => $this->t('Standard'),
      ],
      'maxspeed' => [
        'name' => $this->t('Speed limits'),
      ],
      'electrification' => [
        'name' => $this->t('Electrification'),
      ],
    ];
  }

}
