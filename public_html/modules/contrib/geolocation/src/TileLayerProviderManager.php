<?php

namespace Drupal\geolocation;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Search plugin manager.
 *
 * @method TileLayerProviderInterface createInstance($plugin_id, array $configuration = [])
 */
class TileLayerProviderManager extends DefaultPluginManager {

  use LoggerChannelTrait;
  use StringTranslationTrait;

  /**
   * Constructs an TileLayerProviderManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/geolocation/TileLayerProvider', $namespaces, $module_handler, 'Drupal\geolocation\TileLayerProviderInterface', 'Drupal\geolocation\Attribute\TileLayerProvider');
    $this->alterInfo('geolocation_tilelayerprovider_info');
    $this->setCacheBackend($cache_backend, 'geolocation_tilelayerprovider');
  }

  /**
   * Get tile layer provider definitions.
   *
   * @return array
   *   Data layer provider definitions.
   */
  public function getTileLayerProviderDefinitions(): array {
    $tileLayers = [];
    $definitions = $this->getDefinitions();
    foreach ($definitions as $tileLayerProviderId => $tileLayerProviderDefinition) {
      $tileLayers[$tileLayerProviderId] = $tileLayerProviderDefinition;
    }

    return $tileLayers;
  }

  /**
   * Get options form.
   *
   * @param array $settings
   *   Settings.
   * @param array $parents
   *   Form Parents.
   * @param \Drupal\geolocation\MapProviderInterface|null $map_provider
   *   Map provider.
   * @param array $context
   *   Context.
   *
   * @return array
   *   Options form render array.
   */
  public function getOptionsForm(array $settings, array $parents = [], ?MapProviderInterface $map_provider = NULL, array $context = []): array {
    $tile_layer_providers = $this->getTileLayerProviderDefinitions();
    if (!$tile_layer_providers) {
      return [];
    }

    $tile_layers_form = [
      '#type' => 'container',
      'description' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Select additional tile layers.'),
      ],
      '#parents' => $parents,
    ];

    foreach ($tile_layer_providers as $tile_layer_provider_id => $tile_layer_provider_definition) {
      $tile_layer_provider = $this->createInstance($tile_layer_provider_id, $tile_layer_provider_definition);

      $tile_layers_form[$tile_layer_provider_id] = [
        '#type' => 'details',
        '#title' => $tile_layer_provider->getPluginDefinition()['name'],
      ];

      $tile_layers_form[$tile_layer_provider_id]['settings'] = $tile_layer_provider->getSettingsForm(
        $settings[$tile_layer_provider_id]['settings'] ?? [],
        $context
      );

      $tile_layers_form[$tile_layer_provider_id]['layers'] = [
        '#type' => 'table',
        '#weight' => 100,
        '#caption' => $this->t('Select tile layers and arrange them bottom to top.'),
        '#header' => [
          $this->t('Enable'),
          $this->t('Layer'),
          $this->t('Weight'),
        ],
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'geolocation-tile-layer-option-weight',
          ],
        ],
      ];

      foreach ($tile_layer_provider->getLayerOptions($context) as $tile_layer_option_id => $tile_layer_info) {
        $tile_layer_id = $tile_layer_provider_id . ':' . $tile_layer_option_id;

        $tile_layer_enable_id = Html::getUniqueId($tile_layer_provider_id . '_' . $tile_layer_id . '_enabled');

        $tile_layers_form[$tile_layer_provider_id]['layers'][$tile_layer_id] = [
          '#attributes' => [
            'class' => [
              'draggable',
            ],
          ],
          'enabled' => [
            '#attributes' => [
              'id' => $tile_layer_enable_id,
            ],
            '#type' => 'checkbox',
            '#disabled' => !($tile_layer_info['toggleable'] ?? TRUE),
            '#default_value' => ($tile_layer_info['toggleable'] ?? TRUE) ? ($settings[$tile_layer_provider_id]['layers'][$tile_layer_id]['enabled'] ?? FALSE) : TRUE,
            '#wrapper_attributes' => ['style' => 'vertical-align: top'],
          ],
          'layer' => [
            'label' => [
              '#type' => 'label',
              '#title' => $tile_layer_info['name'],
              '#suffix' => $tile_layer_info['description'] ?? '',
            ],
            'settings' => [],
          ],
          '#weight' => $settings[$tile_layer_provider_id]['layers'][$tile_layer_id]['weight'] ?? $tile_layer_info['default_weight'] ?? 0,
          'weight' => [
            '#type' => 'weight',
            '#title' => $this->t('Weight for @option', ['@option' => $tile_layer_info['name']]),
            '#title_display' => 'invisible',
            '#size' => 4,
            '#default_value' => $settings[$tile_layer_provider_id]['layers'][$tile_layer_id]['weight'] ?? $tile_layer_info['default_weight'] ?? 0,
            '#attributes' => ['class' => ['geolocation-tile-layer-option-weight']],
          ],
        ];

        $tile_layer_form = $tile_layer_provider->getLayerSettingsForm(
          $tile_layer_option_id,
          $settings[$tile_layer_provider_id]['layers'][$tile_layer_id]['settings'] ?? [],
          $context
        );

        if (!empty($tile_layer_form)) {
          $tile_layer_form['#states'] = [
            'visible' => [
              ':input[id="' . $tile_layer_enable_id . '"]' => ['checked' => TRUE],
            ],
          ];
          $tile_layer_form['#type'] = 'item';

          $tile_layers_form[$tile_layer_provider_id]['layers'][$tile_layer_id]['layer']['settings'] = $tile_layer_form;
        }
      }
    }

    uasort($tile_layers_form, [SortArray::class, 'sortByWeightProperty']);

    return $tile_layers_form;
  }

  /**
   * Alter map render array.
   *
   * @param array $render_array
   *   Render array.
   * @param array $layers
   *   Layers.
   * @param array $context
   *   Context.
   *
   * @return array
   *   Render array.
   */
  public function alterMap(array $render_array, array $layers = [], array $context = []): array {
    foreach ($this->getEnabledLayers($layers, $context) as $tile_layer_id => $tile_layer) {
      $render_array = $tile_layer['provider']->alterMap($render_array, $tile_layer_id, $tile_layer['settings'], $context);
    }

    return $render_array;
  }

  /**
   * Get enabled tile layers of all providers, sorted bottom to top.
   *
   * @param array $layers
   *   Layer settings per tile layer provider.
   * @param array $context
   *   Context.
   *
   * @return array
   *   Enabled layers keyed by tile layer ID, each with the instantiated
   *   "provider", its "settings", the "label" and whether it is an "overlay".
   */
  public function getEnabledLayers(array $layers = [], array $context = []): array {
    uasort($layers, [SortArray::class, 'sortByWeightProperty']);

    $enabled_layers = [];
    foreach ($layers as $tile_layer_provider_id => $tile_layer_provider_settings) {
      if (!$this->hasDefinition($tile_layer_provider_id)) {
        continue;
      }
      $tile_layer_provider = $this->createInstance($tile_layer_provider_id, $tile_layer_provider_settings['settings'] ?? []);

      foreach ($tile_layer_provider_settings['layers'] ?? [] as $tile_layer_id => $tile_layer_settings) {
        if (!($tile_layer_settings['enabled'] ?? FALSE)) {
          continue;
        }

        $default_settings = $tile_layer_provider::getDefaultSettings($tile_layer_id, $tile_layer_settings, $context);

        $enabled_layers[$tile_layer_id] = [
          'provider' => $tile_layer_provider,
          'settings' => $tile_layer_settings,
          'label' => $tile_layer_provider->getLabel($tile_layer_id, $tile_layer_settings, $context),
          'overlay' => (bool) ($tile_layer_settings['overlay'] ?? $default_settings['overlay'] ?? FALSE),
          '#weight' => (int) ($tile_layer_settings['weight'] ?? $default_settings['weight'] ?? 0),
        ];
      }
    }

    uasort($enabled_layers, [SortArray::class, 'sortByWeightProperty']);

    return $enabled_layers;
  }

}
