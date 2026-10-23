<?php

namespace Drupal\geolocation\Plugin\geolocation\TileLayerProvider;

use Drupal\geolocation\Attribute\TileLayerProvider;
use Drupal\geolocation\TileLayerProviderBase;
use Drupal\geolocation\TileLayerProviderInterface;

/**
 * Provides OpenWeatherMap tile layers.
 */
#[TileLayerProvider(id: 'geolocation_tile_openweathermap',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('OpenWeatherMap'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://openweathermap.org/. Weather overlays, requires API key.'))]
class OpenWeatherMap extends TileLayerProviderBase implements TileLayerProviderInterface {

  /**
   * Tile URL per layer option.
   *
   * @var string[]
   */
  protected array $urls = [
    'clouds' => 'https://tile.openweathermap.org/map/clouds_new/{z}/{x}/{y}.png',
    'precipitation' => 'https://tile.openweathermap.org/map/precipitation_new/{z}/{x}/{y}.png',
    'pressure' => 'https://tile.openweathermap.org/map/pressure_new/{z}/{x}/{y}.png',
    'wind' => 'https://tile.openweathermap.org/map/wind_new/{z}/{x}/{y}.png',
    'temperature' => 'https://tile.openweathermap.org/map/temp_new/{z}/{x}/{y}.png',
  ];

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings = [], ?array $context = NULL): array {
    return [
      'api_key' => [
        '#type' => 'textfield',
        '#title' => $this->t('API key'),
        '#default_value' => $settings['api_key'] ?? '',
        '#description' => $this->t('Get your @key here <a href="@url">@provider</a>.', [
          '@key' => $this->t('API key'),
          '@url' => 'https://openweathermap.org/api',
          '@provider' => 'OpenWeatherMap',
        ]),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): array {
    return [
      'max_zoom' => 19,
      'opacity' => 0.5,
      'overlay' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTileLayerUrl(string $tile_layer_option_id = 'default', array $settings = [], ?array $context = NULL): string {
    $url = $this->urls[static::getOptionTitleById($tile_layer_option_id)] ?? reset($this->urls);

    if ($this->configuration['api_key'] ?? FALSE) {
      $url .= (str_contains($url, '?') ? '&' : '?') . 'appid=' . $this->configuration['api_key'];
    }

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttribution(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): string {
    return 'Map data &copy; <a href="https://openweathermap.org">OpenWeatherMap</a>';
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'clouds' => [
        'name' => $this->t('Clouds'),
      ],
      'precipitation' => [
        'name' => $this->t('Precipitation'),
      ],
      'pressure' => [
        'name' => $this->t('Pressure'),
      ],
      'wind' => [
        'name' => $this->t('Wind speed'),
      ],
      'temperature' => [
        'name' => $this->t('Temperature'),
      ],
    ];
  }

}
