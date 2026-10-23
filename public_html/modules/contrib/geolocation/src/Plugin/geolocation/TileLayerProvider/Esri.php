<?php

namespace Drupal\geolocation\Plugin\geolocation\TileLayerProvider;

use Drupal\geolocation\Attribute\TileLayerProvider;
use Drupal\geolocation\TileLayerProviderBase;
use Drupal\geolocation\TileLayerProviderInterface;

/**
 * Provides Esri tile layers.
 */
#[TileLayerProvider(id: 'geolocation_tile_esri',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Esri'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://www.esri.com/. ArcGIS Online basemaps.'))]
class Esri extends TileLayerProviderBase implements TileLayerProviderInterface {

  /**
   * Tile URL per layer option.
   *
   * @var string[]
   */
  protected array $urls = [
    'world_street_map' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}',
    'world_topo_map' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}',
    'world_imagery' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
    'world_terrain' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Terrain_Base/MapServer/tile/{z}/{y}/{x}',
    'world_shaded_relief' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Shaded_Relief/MapServer/tile/{z}/{y}/{x}',
    'world_physical' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Physical_Map/MapServer/tile/{z}/{y}/{x}',
    'ocean_basemap' => 'https://server.arcgisonline.com/ArcGIS/rest/services/Ocean/World_Ocean_Base/MapServer/tile/{z}/{y}/{x}',
    'natgeo_world_map' => 'https://server.arcgisonline.com/ArcGIS/rest/services/NatGeo_World_Map/MapServer/tile/{z}/{y}/{x}',
    'world_gray_canvas' => 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): array {
    $defaults = [
      'world_street_map' => [
        'max_zoom' => 19,
      ],
      'world_topo_map' => [
        'max_zoom' => 19,
      ],
      'world_imagery' => [
        'max_zoom' => 19,
      ],
      'world_terrain' => [
        'max_zoom' => 13,
      ],
      'world_shaded_relief' => [
        'max_zoom' => 13,
      ],
      'world_physical' => [
        'max_zoom' => 8,
      ],
      'ocean_basemap' => [
        'max_zoom' => 13,
      ],
      'natgeo_world_map' => [
        'max_zoom' => 16,
      ],
      'world_gray_canvas' => [
        'max_zoom' => 16,
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
    return 'Tiles &copy; Esri';
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'world_street_map' => [
        'name' => $this->t('World Street Map'),
      ],
      'world_topo_map' => [
        'name' => $this->t('World Topo Map'),
      ],
      'world_imagery' => [
        'name' => $this->t('World Imagery'),
      ],
      'world_terrain' => [
        'name' => $this->t('World Terrain'),
      ],
      'world_shaded_relief' => [
        'name' => $this->t('World Shaded Relief'),
      ],
      'world_physical' => [
        'name' => $this->t('World Physical'),
      ],
      'ocean_basemap' => [
        'name' => $this->t('Ocean Basemap'),
      ],
      'natgeo_world_map' => [
        'name' => $this->t('National Geographic World Map'),
      ],
      'world_gray_canvas' => [
        'name' => $this->t('World Gray Canvas'),
      ],
    ];
  }

}
