<?php

namespace Drupal\geolocation\Plugin\geolocation\TileLayerProvider;

use Drupal\geolocation\Attribute\TileLayerProvider;
use Drupal\geolocation\TileLayerProviderBase;
use Drupal\geolocation\TileLayerProviderInterface;

/**
 * Provides OpenStreetMap tile layers.
 */
#[TileLayerProvider(id: 'geolocation_tile_openstreetmap',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('OpenStreetMap'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://www.openstreetmap.org/.'))]
class OpenStreetMap extends TileLayerProviderBase implements TileLayerProviderInterface {

  /**
   * Tile URL per layer option.
   *
   * @var string[]
   */
  protected array $urls = [
    'mapnik' => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
    'de' => 'https://tile.openstreetmap.de/{z}/{x}/{y}.png',
    'ch' => 'https://tile.osm.ch/switzerland/{z}/{x}/{y}.png',
    'france' => 'https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png',
    'hot' => 'https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png',
    'bzh' => 'https://tile.openstreetmap.bzh/br/{z}/{x}/{y}.png',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): array {
    $defaults = [
      'mapnik' => [
        'max_zoom' => 19,
      ],
      'de' => [
        'max_zoom' => 18,
      ],
      'ch' => [
        'max_zoom' => 18,
        'bounds' => [[45, 5], [48, 11]],
      ],
      'france' => [
        'max_zoom' => 20,
      ],
      'hot' => [
        'max_zoom' => 19,
      ],
      'bzh' => [
        'max_zoom' => 19,
        'bounds' => [[46.2, -5.5], [50, 0.7]],
      ],
    ];

    return $defaults[static::getOptionTitleById($tile_layer_option_id)] ?? [];
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
    return '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'mapnik' => [
        'name' => $this->t('Mapnik'),
      ],
      'de' => [
        'name' => $this->t('Germany'),
      ],
      'ch' => [
        'name' => $this->t('Switzerland'),
      ],
      'france' => [
        'name' => $this->t('France'),
      ],
      'hot' => [
        'name' => $this->t('Humanitarian'),
      ],
      'bzh' => [
        'name' => $this->t('Breizh'),
      ],
    ];
  }

}
