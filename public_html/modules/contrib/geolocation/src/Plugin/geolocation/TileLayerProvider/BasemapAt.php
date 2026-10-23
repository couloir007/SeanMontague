<?php

namespace Drupal\geolocation\Plugin\geolocation\TileLayerProvider;

use Drupal\geolocation\Attribute\TileLayerProvider;
use Drupal\geolocation\TileLayerProviderBase;
use Drupal\geolocation\TileLayerProviderInterface;

/**
 * Provides basemap.at tile layers.
 */
#[TileLayerProvider(id: 'geolocation_tile_basemapat',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('basemap.at'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://basemap.at/. Official Austrian base map.'))]
class BasemapAt extends TileLayerProviderBase implements TileLayerProviderInterface {

  /**
   * Tile URL per layer option.
   *
   * @var string[]
   */
  protected array $urls = [
    'basemap' => 'https://mapsneu.wien.gv.at/basemap/geolandbasemap/normal/google3857/{z}/{y}/{x}.png',
    'grau' => 'https://mapsneu.wien.gv.at/basemap/bmapgrau/normal/google3857/{z}/{y}/{x}.png',
    'terrain' => 'https://mapsneu.wien.gv.at/basemap/bmapgelaende/grau/google3857/{z}/{y}/{x}.jpeg',
    'surface' => 'https://mapsneu.wien.gv.at/basemap/bmapoberflaeche/grau/google3857/{z}/{y}/{x}.jpeg',
    'orthofoto' => 'https://mapsneu.wien.gv.at/basemap/bmaporthofoto30cm/normal/google3857/{z}/{y}/{x}.jpeg',
    'overlay' => 'https://mapsneu.wien.gv.at/basemap/bmapoverlay/normal/google3857/{z}/{y}/{x}.png',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): array {
    $defaults = [
      'basemap' => [
        'max_zoom' => 20,
        'bounds' => [[46.35877, 8.782379], [49.037872, 17.189532]],
      ],
      'grau' => [
        'max_zoom' => 19,
        'bounds' => [[46.35877, 8.782379], [49.037872, 17.189532]],
      ],
      'terrain' => [
        'max_zoom' => 19,
        'bounds' => [[46.35877, 8.782379], [49.037872, 17.189532]],
      ],
      'surface' => [
        'max_zoom' => 19,
        'bounds' => [[46.35877, 8.782379], [49.037872, 17.189532]],
      ],
      'orthofoto' => [
        'max_zoom' => 20,
        'bounds' => [[46.35877, 8.782379], [49.037872, 17.189532]],
      ],
      'overlay' => [
        'max_zoom' => 19,
        'bounds' => [[46.35877, 8.782379], [49.037872, 17.189532]],
        'overlay' => TRUE,
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
    return 'Datenquelle: <a href="https://www.basemap.at">basemap.at</a>';
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'basemap' => [
        'name' => $this->t('Basemap'),
      ],
      'grau' => [
        'name' => $this->t('Grey'),
      ],
      'terrain' => [
        'name' => $this->t('Terrain'),
      ],
      'surface' => [
        'name' => $this->t('Surface'),
      ],
      'orthofoto' => [
        'name' => $this->t('Orthophoto'),
      ],
      'overlay' => [
        'name' => $this->t('Labels'),
      ],
    ];
  }

}
