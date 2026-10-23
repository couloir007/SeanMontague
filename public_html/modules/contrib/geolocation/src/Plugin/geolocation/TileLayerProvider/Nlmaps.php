<?php

namespace Drupal\geolocation\Plugin\geolocation\TileLayerProvider;

use Drupal\geolocation\Attribute\TileLayerProvider;
use Drupal\geolocation\TileLayerProviderBase;
use Drupal\geolocation\TileLayerProviderInterface;

/**
 * Provides nlmaps tile layers.
 */
#[TileLayerProvider(id: 'geolocation_tile_nlmaps',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('nlmaps'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://www.pdok.nl/. Official Dutch base maps.'))]
class Nlmaps extends TileLayerProviderBase implements TileLayerProviderInterface {

  /**
   * Tile URL per layer option.
   *
   * @var string[]
   */
  protected array $urls = [
    'standaard' => 'https://service.pdok.nl/brt/achtergrondkaart/wmts/v2_0/standaard/EPSG:3857/{z}/{x}/{y}.png',
    'pastel' => 'https://service.pdok.nl/brt/achtergrondkaart/wmts/v2_0/pastel/EPSG:3857/{z}/{x}/{y}.png',
    'grijs' => 'https://service.pdok.nl/brt/achtergrondkaart/wmts/v2_0/grijs/EPSG:3857/{z}/{x}/{y}.png',
    'water' => 'https://service.pdok.nl/brt/achtergrondkaart/wmts/v2_0/water/EPSG:3857/{z}/{x}/{y}.png',
    'luchtfoto' => 'https://service.pdok.nl/hwh/luchtfotorgb/wmts/v1_0/Actueel_ortho25/EPSG:3857/{z}/{x}/{y}.jpeg',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): array {
    return [
      'min_zoom' => 6,
      'max_zoom' => 19,
      'bounds' => [[50.5, 3.25], [54, 7.6]],
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
    return 'Kaartgegevens &copy; <a href="https://www.kadaster.nl">Kadaster</a>';
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'standaard' => [
        'name' => $this->t('Standard'),
      ],
      'pastel' => [
        'name' => $this->t('Pastel'),
      ],
      'grijs' => [
        'name' => $this->t('Grey'),
      ],
      'water' => [
        'name' => $this->t('Water'),
      ],
      'luchtfoto' => [
        'name' => $this->t('Aerial'),
      ],
    ];
  }

}
