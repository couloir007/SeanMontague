<?php

namespace Drupal\geolocation\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Utility\Token;
use Drupal\geolocation\MapFeatureInterface;
use Drupal\geolocation\MapProviderInterface;
use Drupal\geolocation\TileLayerProviderManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Recenter control element.
 */
#[MapFeature(
  id: 'control_tile_layers',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Map Control - Tile Layers'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Shows list of toggleable tile layers.'),
  type: 'all',
)]
class ControlTileLayers extends ControlCustomElementBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ModuleHandlerInterface $moduleHandler,
    FileSystemInterface $fileSystem,
    Token $token,
    LibraryDiscoveryInterface $libraryDiscovery,
    protected TileLayerProviderManager $tileLayerProviderManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $moduleHandler, $fileSystem, $token, $libraryDiscovery);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): MapFeatureInterface {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('file_system'),
      $container->get('token'),
      $container->get('library.discovery'),
      $container->get('plugin.manager.geolocation.tilelayerprovider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $render_array, array $feature_settings = [], array $context = [], ?MapProviderInterface $mapProvider = NULL): array {
    $render_array = parent::alterMap($render_array, $feature_settings, $context, $mapProvider);

    $tile_layers = $this->tileLayerProviderManager->getEnabledLayers($render_array['#settings']['tile_layers'] ?? [], $context);
    if (!$tile_layers) {
      return $render_array;
    }

    $base_layers = array_filter($tile_layers, fn ($tile_layer) => !$tile_layer['overlay']);
    $overlays = array_filter($tile_layers, fn ($tile_layer) => $tile_layer['overlay']);

    $render_array['#controls'][$this->pluginId]['control_tile_layers'] = [
      '#type' => 'details',
      '#title' => $this->t('Tile layers'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#attributes' => [
        'class' => [
          'fieldset',
        ],
      ],
    ];
    $control = &$render_array['#controls'][$this->pluginId]['control_tile_layers'];

    // Base layers exclude each other, the topmost one starts out active.
    $active_base_layer_id = array_key_last($base_layers);
    foreach ($base_layers as $tile_layer_id => $tile_layer) {
      $control[$tile_layer_id] = [
        '#type' => 'radio',
        '#title' => $tile_layer['label'],
        '#name' => $this->pluginId . '_base_layer',
        '#return_value' => $tile_layer_id,
        '#value' => $active_base_layer_id,
        '#attributes' => [
          'data-tile-layer-id' => $tile_layer_id,
        ],
      ];
    }

    foreach ($overlays as $tile_layer_id => $tile_layer) {
      $control[$tile_layer_id] = [
        '#type' => 'checkbox',
        '#title' => $tile_layer['label'],
        '#checked' => TRUE,
        '#name' => $tile_layer_id,
        '#attributes' => [
          'data-tile-layer-id' => $tile_layer_id,
        ],
      ];
    }

    return $render_array;
  }

}
