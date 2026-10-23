<?php

namespace Drupal\geolocation\Plugin\geolocation\TileLayerProvider;

use Drupal\geolocation\Attribute\TileLayerProvider;
use Drupal\geolocation\TileLayerProviderBase;
use Drupal\geolocation\TileLayerProviderInterface;

/**
 * Provides OpenSnowMap tile layers.
 */
#[TileLayerProvider(id: 'geolocation_tile_opensnowmap',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('OpenSnowMap'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://www.opensnowmap.org/. Ski piste overlay.'))]
class OpenSnowMap extends TileLayerProviderBase implements TileLayerProviderInterface {

  /**
   * Tile URL per layer option.
   *
   * @var string[]
   */
  protected array $urls = [
    'pistes' => 'https://tiles.opensnowmap.org/pistes/{z}/{x}/{y}.png',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): array {
    return [
      'min_zoom' => 9,
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
    return 'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &amp; ODbL, &copy; <a href="https://www.opensnowmap.org/iframes/data.html">www.opensnowmap.org</a> <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>';
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'pistes' => [
        'name' => $this->t('Pistes'),
      ],
    ];
  }

}
