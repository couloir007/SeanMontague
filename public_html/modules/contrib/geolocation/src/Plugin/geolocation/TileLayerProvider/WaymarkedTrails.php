<?php

namespace Drupal\geolocation\Plugin\geolocation\TileLayerProvider;

use Drupal\geolocation\Attribute\TileLayerProvider;
use Drupal\geolocation\TileLayerProviderBase;
use Drupal\geolocation\TileLayerProviderInterface;

/**
 * Provides Waymarked Trails tile layers.
 */
#[TileLayerProvider(id: 'geolocation_tile_waymarkedtrails',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Waymarked Trails'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://waymarkedtrails.org/. Route overlays for outdoor activities.'))]
class WaymarkedTrails extends TileLayerProviderBase implements TileLayerProviderInterface {

  /**
   * Tile URL per layer option.
   *
   * @var string[]
   */
  protected array $urls = [
    'hiking' => 'https://tile.waymarkedtrails.org/hiking/{z}/{x}/{y}.png',
    'cycling' => 'https://tile.waymarkedtrails.org/cycling/{z}/{x}/{y}.png',
    'mtb' => 'https://tile.waymarkedtrails.org/mtb/{z}/{x}/{y}.png',
    'slopes' => 'https://tile.waymarkedtrails.org/slopes/{z}/{x}/{y}.png',
    'riding' => 'https://tile.waymarkedtrails.org/riding/{z}/{x}/{y}.png',
    'skating' => 'https://tile.waymarkedtrails.org/skating/{z}/{x}/{y}.png',
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
    return 'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors | Map style: &copy; <a href="https://waymarkedtrails.org">waymarkedtrails.org</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)';
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'hiking' => [
        'name' => $this->t('Hiking'),
      ],
      'cycling' => [
        'name' => $this->t('Cycling'),
      ],
      'mtb' => [
        'name' => $this->t('Mountain biking'),
      ],
      'slopes' => [
        'name' => $this->t('Slopes'),
      ],
      'riding' => [
        'name' => $this->t('Riding'),
      ],
      'skating' => [
        'name' => $this->t('Skating'),
      ],
    ];
  }

}
