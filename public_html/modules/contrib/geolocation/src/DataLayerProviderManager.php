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
 * @method DataLayerProviderInterface createInstance($plugin_id, array $configuration = [])
 */
class DataLayerProviderManager extends DefaultPluginManager {

  use LoggerChannelTrait;
  use StringTranslationTrait;

  /**
   * Layer feature manager.
   *
   * @var \Drupal\geolocation\LayerFeatureManager
   */
  protected LayerFeatureManager $layerFeatureManager;

  /**
   * Constructs an MapFeatureManager object.
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
    parent::__construct('Plugin/geolocation/DataLayerProvider', $namespaces, $module_handler, 'Drupal\geolocation\DataLayerProviderInterface', 'Drupal\geolocation\Attribute\DataLayerProvider');
    $this->alterInfo('geolocation_datalayerprovider_info');
    $this->setCacheBackend($cache_backend, 'geolocation_datalayerprovider');

    $this->layerFeatureManager = \Drupal::service('plugin.manager.geolocation.layerfeature');
  }

  /**
   * Get data layer provider definitions.
   *
   * @return array
   *   Data layer provider definitions.
   */
  public function getDataLayerProviderDefinitions(): array {
    $dataLayers = [];
    $definitions = $this->getDefinitions();
    foreach ($definitions as $dataLayerProviderId => $dataLayerProviderDefinition) {
      $dataLayers[$dataLayerProviderId] = $dataLayerProviderDefinition;
    }

    return $dataLayers;
  }

  /**
   * Get options form.
   *
   * @param array $settings
   *   Settings.
   * @param array $parents
   *   Form Parents.
   * @param ?\Drupal\geolocation\MapProviderInterface $map_provider
   *   Map provider.
   * @param array $context
   *   Context.
   *
   * @return array
   *   Options form render array.
   */
  public function getOptionsForm(array $settings, array $parents = [], ?MapProviderInterface $map_provider = NULL, array $context = []): array {
    $data_layer_providers = $this->getDataLayerProviderDefinitions();
    if (!$data_layer_providers) {
      return [];
    }

    $data_layers_form = [
      '#type' => 'table',
      '#weight' => 100,
      '#caption' => $this->t('Select additional layers of data and arrange them top to bottom.'),
      '#header' => [
        $this->t('Enable'),
        $this->t('Layer'),
        $this->t('Weight'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'geolocation-data-layer-option-weight',
        ],
      ],
      '#parents' => $parents,
    ];

    foreach ($data_layer_providers as $data_layer_provider_id => $data_layer_provider_definition) {
      $data_layer_provider = $this->createInstance($data_layer_provider_id, $data_layer_provider_definition);

      foreach ($data_layer_provider->getLayerOptions($context) as $data_layer_option_id => $data_layer_info) {
        $data_layer_id = $data_layer_provider_id . ':' . $data_layer_option_id;

        $data_layer_enable_id = Html::getUniqueId($data_layer_provider_id . '_' . $data_layer_id . '_enabled');

        $data_layer_settings = $settings[$data_layer_id]['settings'] ?? [];

        $layer_features_form = $this->layerFeatureManager->getOptionsForm(
          $settings[$data_layer_id]['settings']['features'] ?? [],
          array_merge($parents, [$data_layer_id, 'settings', 'features']),
          $map_provider
        );

        // Add #states handling.
        $layer_features_form['#attributes']['class'][] = 'js-form-wrapper';
        $layer_features_form['#attributes']['class'][] = 'form-wrapper';
        $layer_features_form['#states'] = [
          'visible' => [
            ':input[id="' . $data_layer_enable_id . '"]' => ['checked' => TRUE],
          ],
        ];

        $data_layers_form[$data_layer_id] = [
          '#attributes' => [
            'class' => [
              'draggable',
            ],
          ],
          'enabled' => [
            '#attributes' => [
              'id' => $data_layer_enable_id,
            ],
            '#disabled' => !($data_layer_info['toggleable'] ?? TRUE),
            '#type' => 'checkbox',
            '#default_value' => ($data_layer_info['toggleable'] ?? TRUE) ? ($settings[$data_layer_id]['enabled'] ?? FALSE) : TRUE,
            '#wrapper_attributes' => ['style' => 'vertical-align: top'],
          ],
          'layer' => [
            'label' => [
              '#type' => 'label',
              '#title' => $data_layer_info['name'],
              '#suffix' => $data_layer_info['description'],
            ],
            'settings' => [],
            'features' => $layer_features_form,
          ],
          '#weight' => $settings[$data_layer_id]['weight'] ?? $data_layer_info['default_weight'] ?? 0,
          'weight' => [
            '#type' => 'weight',
            '#title' => $this->t('Weight for @option', ['@option' => $data_layer_info['name']]),
            '#title_display' => 'invisible',
            '#size' => 4,
            '#default_value' => $settings[$data_layer_id]['weight'] ?? $data_layer_info['default_weight'] ?? 0,
            '#attributes' => ['class' => ['geolocation-data-layer-option-weight']],
          ],
        ];

        $data_layer_form = $data_layer_provider->getSettingsForm(
          $data_layer_option_id,
          $data_layer_settings,
          $context
        );

        if (!empty($data_layer_form)) {
          $data_layer_form['#states'] = [
            'visible' => [
              ':input[id="' . $data_layer_enable_id . '"]' => ['checked' => TRUE],
            ],
          ];
          $data_layer_form['#type'] = 'item';

          $data_layers_form[$data_layer_id]['layer']['settings'] = $data_layer_form;
        }
      }
    }

    uasort($data_layers_form, [SortArray::class, 'sortByWeightProperty']);

    return $data_layers_form;
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

    uasort($layers, [SortArray::class, 'sortByWeightProperty']);

    foreach ($layers as $layer_id => $layer_settings) {
      // Ignore if not enabled.
      if (empty($layer_settings['enabled'])) {
        continue;
      }

      [$data_layer_provider_id, $data_layer_option_id] = explode(':', $layer_id);
      if (!$this->hasDefinition($data_layer_provider_id)) {
        continue;
      }

      $data_layer_provider = $this->createInstance($data_layer_provider_id);

      $render_array = $data_layer_provider->alterMap($render_array, $data_layer_option_id, $layer_settings['settings'] ?? [], $context);
    }

    return $render_array;
  }

}
