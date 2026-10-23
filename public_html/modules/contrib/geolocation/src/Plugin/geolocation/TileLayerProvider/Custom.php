<?php

namespace Drupal\geolocation\Plugin\geolocation\TileLayerProvider;

use Drupal\geolocation\Attribute\TileLayerProvider;
use Drupal\geolocation\TileLayerProviderBase;
use Drupal\geolocation\TileLayerProviderInterface;

/**
 * Provides a freely configurable tile layer.
 */
#[TileLayerProvider(id: 'geolocation_tile_custom',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Custom tile layer'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Any tile server supporting the {z}/{x}/{y} scheme.'))]
class Custom extends TileLayerProviderBase implements TileLayerProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings = [], ?array $context = NULL): array {
    return [
      'url' => [
        '#type' => 'textfield',
        '#title' => $this->t('Tile URL'),
        '#maxlength' => 1024,
        '#default_value' => $settings['url'] ?? '',
        '#description' => $this->t('Placeholders %z, %x and %y are replaced per tile, %s by a subdomain.', [
          '%z' => '{z}',
          '%x' => '{x}',
          '%y' => '{y}',
          '%s' => '{s}',
        ]),
      ],
      'attribution' => [
        '#type' => 'textfield',
        '#title' => $this->t('Attribution'),
        '#maxlength' => 1024,
        '#default_value' => $settings['attribution'] ?? '',
        '#description' => $this->t('Shown in the map attribution. Most tile services require this.'),
      ],
      'max_zoom' => [
        '#type' => 'number',
        '#title' => $this->t('Maximum zoom'),
        '#default_value' => $settings['max_zoom'] ?? NULL,
        '#min' => 0,
        '#max' => 22,
      ],
      'overlay' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Overlay'),
        '#default_value' => $settings['overlay'] ?? FALSE,
        '#description' => $this->t('Stack this layer above the base map instead of replacing it.'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTileLayerUrl(string $tile_layer_option_id = 'default', array $settings = [], ?array $context = NULL): string {
    return $this->configuration['url'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getAttribution(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): string {
    return $this->configuration['attribution'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $render_array, string $tile_layer_option_id = 'default', array $settings = [], array $context = []): array {
    if ($this->configuration['max_zoom'] ?? FALSE) {
      $settings['max_zoom'] = $this->configuration['max_zoom'];
    }

    $settings['overlay'] = $this->configuration['overlay'] ?? FALSE;

    return parent::alterMap($render_array, $tile_layer_option_id, $settings, $context);
  }

}
