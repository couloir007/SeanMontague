<?php

namespace Drupal\geolocation\Plugin\geolocation\TileLayerProvider;

use Drupal\geolocation\Attribute\TileLayerProvider;
use Drupal\geolocation\TileLayerProviderBase;
use Drupal\geolocation\TileLayerProviderInterface;

/**
 * Provides USGS National Map tile layers.
 */
#[TileLayerProvider(id: 'geolocation_tile_usgs',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('USGS National Map'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://basemap.nationalmap.gov/. Basemaps of the United States Geological Survey.'))]
class UsgsNationalMap extends TileLayerProviderBase implements TileLayerProviderInterface {

  /**
   * Tile URL per layer option.
   *
   * @var string[]
   */
  protected array $urls = [
    'topo' => 'https://basemap.nationalmap.gov/arcgis/rest/services/USGSTopo/MapServer/tile/{z}/{y}/{x}',
    'imagery' => 'https://basemap.nationalmap.gov/arcgis/rest/services/USGSImageryOnly/MapServer/tile/{z}/{y}/{x}',
    'imagery_topo' => 'https://basemap.nationalmap.gov/arcgis/rest/services/USGSImageryTopo/MapServer/tile/{z}/{y}/{x}',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): array {
    return [
      'max_zoom' => 20,
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
    return 'Tiles courtesy of the <a href="https://usgs.gov/">U.S. Geological Survey</a>';
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'topo' => [
        'name' => $this->t('US Topo'),
      ],
      'imagery' => [
        'name' => $this->t('US Imagery'),
      ],
      'imagery_topo' => [
        'name' => $this->t('US Imagery Topo'),
      ],
    ];
  }

}
