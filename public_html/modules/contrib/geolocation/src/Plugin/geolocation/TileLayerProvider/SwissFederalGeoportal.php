<?php

namespace Drupal\geolocation\Plugin\geolocation\TileLayerProvider;

use Drupal\geolocation\Attribute\TileLayerProvider;
use Drupal\geolocation\TileLayerProviderBase;
use Drupal\geolocation\TileLayerProviderInterface;

/**
 * Provides Swiss Federal Geoportal tile layers.
 */
#[TileLayerProvider(id: 'geolocation_tile_swisstopo',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Swiss Federal Geoportal'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://www.geo.admin.ch/. Official Swiss base maps.'))]
class SwissFederalGeoportal extends TileLayerProviderBase implements TileLayerProviderInterface {

  /**
   * Tile URL per layer option.
   *
   * @var string[]
   */
  protected array $urls = [
    'national_map_color' => 'https://wmts.geo.admin.ch/1.0.0/ch.swisstopo.pixelkarte-farbe/default/current/3857/{z}/{x}/{y}.jpeg',
    'national_map_grey' => 'https://wmts.geo.admin.ch/1.0.0/ch.swisstopo.pixelkarte-grau/default/current/3857/{z}/{x}/{y}.jpeg',
    'swissimage' => 'https://wmts.geo.admin.ch/1.0.0/ch.swisstopo.swissimage/default/current/3857/{z}/{x}/{y}.jpeg',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): array {
    $defaults = [
      'national_map_color' => [
        'min_zoom' => 2,
        'max_zoom' => 18,
        'bounds' => [[45.398181, 5.140242], [48.230651, 11.47757]],
      ],
      'national_map_grey' => [
        'min_zoom' => 2,
        'max_zoom' => 18,
        'bounds' => [[45.398181, 5.140242], [48.230651, 11.47757]],
      ],
      'swissimage' => [
        'min_zoom' => 2,
        'max_zoom' => 19,
        'bounds' => [[45.398181, 5.140242], [48.230651, 11.47757]],
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
    return '&copy; <a href="https://www.swisstopo.admin.ch/">swisstopo</a>';
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'national_map_color' => [
        'name' => $this->t('National Map Color'),
      ],
      'national_map_grey' => [
        'name' => $this->t('National Map Grey'),
      ],
      'swissimage' => [
        'name' => $this->t('SWISSIMAGE'),
      ],
    ];
  }

}
