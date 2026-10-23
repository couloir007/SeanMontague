<?php

namespace Drupal\geolocation\Plugin\geolocation\TileLayerProvider;

use Drupal\geolocation\Attribute\TileLayerProvider;
use Drupal\geolocation\TileLayerProviderBase;
use Drupal\geolocation\TileLayerProviderInterface;

/**
 * Provides TopPlusOpen tile layers.
 */
#[TileLayerProvider(id: 'geolocation_tile_topplusopen',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('TopPlusOpen'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://gdz.bkg.bund.de/. Worldwide map by the German Federal Agency for Cartography and Geodesy.'))]
class TopPlusOpen extends TileLayerProviderBase implements TileLayerProviderInterface {

  /**
   * Tile URL per layer option.
   *
   * @var string[]
   */
  protected array $urls = [
    'color' => 'https://sgx.geodatenzentrum.de/wmts_topplus_open/tile/1.0.0/web/default/WEBMERCATOR/{z}/{y}/{x}.png',
    'grey' => 'https://sgx.geodatenzentrum.de/wmts_topplus_open/tile/1.0.0/web_grau/default/WEBMERCATOR/{z}/{y}/{x}.png',
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
    return 'Map data: &copy; <a href="https://www.bkg.bund.de">Bundesamt für Kartographie und Geodäsie</a>';
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'color' => [
        'name' => $this->t('Color'),
      ],
      'grey' => [
        'name' => $this->t('Grey'),
      ],
    ];
  }

}
