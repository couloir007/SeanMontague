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

    $enabled_tile_layers = [];
    foreach ($render_array['#settings']['tile_layers'] ?? [] as $tile_layer_provider_id => $tile_layer_provider_data) {
      $tileLayer = $this->tileLayerProviderManager->createInstance($tile_layer_provider_id, $tile_layer_provider_data['settings'] ?? []);
      foreach ($tile_layer_provider_data['layers'] ?? [] as $tile_layer_id => $tile_layer_data) {
        if (!($tile_layer_data['enabled'] ?? FALSE)) {
          continue;
        }
        $enabled_tile_layers[$tile_layer_id] = $tileLayer->getLabel($tile_layer_id);
      }
    }

    if (!$enabled_tile_layers) {
      return $render_array;
    }

    $render_array['#controls'][$this->pluginId]['control_tile_layers'] = [
      '#type' => 'details',
      '#title' => t('Tile layers'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#attributes' => [
        'class' => [
          'fieldset',
        ],
      ],
    ];

    foreach ($enabled_tile_layers as $enabled_tile_layer_id => $enabled_tile_layer_label) {
      $render_array['#controls'][$this->pluginId]['control_tile_layers'][$enabled_tile_layer_id] = [
        '#type' => 'checkbox',
        '#title' => $enabled_tile_layer_label,
        '#checked' => TRUE,
        '#name' => $enabled_tile_layer_id,
      ];
    }

    return $render_array;
  }

}
