<?php

namespace Drupal\geolocation\Plugin\geolocation\TileLayerProvider;

use Drupal\geolocation\Attribute\TileLayerProvider;
use Drupal\geolocation\TileLayerProviderBase;
use Drupal\geolocation\TileLayerProviderInterface;

/**
 * Provides ÖPNVKarte tile layers.
 */
#[TileLayerProvider(id: 'geolocation_tile_opnvkarte',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('ÖPNVKarte'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://www.öpnvkarte.de/. Public transport oriented map.'))]
class OpnvKarte extends TileLayerProviderBase implements TileLayerProviderInterface {

  /**
   * Tile URL per layer option.
   *
   * @var string[]
   */
  protected array $urls = [
    'default' => 'https://tileserver.memomaps.de/tilegen/{z}/{x}/{y}.png',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): array {
    return [
      'max_zoom' => 18,
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
    return 'Map <a href="https://memomaps.de/">memomaps.de</a> <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'default' => [
        'name' => $this->t('ÖPNVKarte'),
      ],
    ];
  }

}
