<?php

namespace Drupal\geolocation;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Search plugin manager.
 *
 * @method LayerFeatureInterface createInstance($plugin_id, array $configuration = [])
 */
class LayerFeatureManager extends DefaultPluginManager {

  use LoggerChannelTrait;
  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * Constructs a LayerFeatureManager object.
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
    parent::__construct('Plugin/geolocation/LayerFeature', $namespaces, $module_handler, 'Drupal\geolocation\LayerFeatureInterface', 'Drupal\geolocation\Attribute\LayerFeature');
    $this->alterInfo('geolocation_layerfeature_info');
    $this->setCacheBackend($cache_backend, 'geolocation_layerfeature');
  }

  /**
   * Get layer feature.
   *
   * @param string $id
   *   Feature ID.
   * @param array $configuration
   *   Configuration.
   *
   * @return ?\Drupal\geolocation\LayerFeatureInterface
   *   Feature.
   */
  public function getLayerFeature(string $id, array $configuration = []): ?LayerFeatureInterface {
    if (!$this->hasDefinition($id)) {
      return NULL;
    }

    try {
      return $this->createInstance($id, $configuration);
    }
    catch (\Exception $e) {
      $this->getLogger('geolocation')->warning("Error loading LayerFeature: " . $e->getMessage());
      return NULL;
    }
  }

  /**
   * Return LayerFeature by ID.
   *
   * @param string $type
   *   Map type.
   *
   * @return array[]
   *   Map feature list.
   */
  public function getLayerFeaturesByMapType(string $type): array {
    $definitions = $this->getDefinitions();
    $list = [];
    foreach ($definitions as $id => $definition) {
      if ($definition['type'] == $type || $definition['type'] == 'all') {
        $list[$id] = $definition;
      }
    }

    uasort($list, [self::class, 'sortByName']);

    return $list;
  }

  /**
   * Support sorting function.
   *
   * @param array $a
   *   Element entry.
   * @param array $b
   *   Element entry.
   *
   * @return int
   *   Sorting value.
   */
  public static function sortByName(array $a, array $b): int {
    return SortArray::sortByKeyString($a, $b, 'name');
  }

  /**
   * Feature options form.
   *
   * @param array $settings
   *   Settings.
   * @param array $parents
   *   Form parents.
   * @param ?\Drupal\geolocation\MapProviderInterface $map_provider
   *   Map provider.
   *
   * @return array
   *   Render array.
   */
  public function getOptionsForm(array $settings, array $parents = [], ?MapProviderInterface $map_provider = NULL): array {
    $layer_features = $this->getLayerFeaturesByMapType($map_provider->getPluginId());

    if (empty($layer_features)) {
      return [];
    }

    $layer_features_form = [
      '#type' => 'table',
      '#weight' => 100,
      '#caption' => $this->t('<p>Select features to alter the functionality of this layer.</p>'),
      '#header' => [
        $this->t('Enable'),
        $this->t('Feature'),
        $this->t('Weight'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'geolocation-layer-feature-option-weight',
        ],
      ],
      '#parents' => $parents,
    ];
    $layer_features_form['#element_validate'][] = [
      $this, 'validateLayerFeatureForms',
    ];

    foreach ($layer_features as $feature_id => $feature_definition) {
      $feature = $this->getLayerFeature($feature_id);
      if (empty($feature)) {
        continue;
      }

      if ($feature->getPluginDefinition()['hidden'] ?? FALSE) {
        continue;
      }

      $feature_enable_id = Html::getUniqueId($feature_id . '_enabled');
      $weight = $settings[$feature_id]['weight'] ?? 0;

      $feature_settings = $settings[$feature_id]['settings'] ?? [];
      if (!is_array($feature_settings)) {
        $feature_settings = [$feature_settings];
      }

      $layer_features_form[$feature_id] = [
        '#weight' => $weight,
        '#attributes' => [
          'class' => [
            'draggable',
          ],
        ],
        'enabled' => [
          '#attributes' => [
            'id' => $feature_enable_id,
          ],
          '#type' => 'checkbox',
          '#default_value' => !empty($settings[$feature_id]['enabled']),
          '#wrapper_attributes' => ['style' => 'vertical-align: top'],
        ],
        'feature' => [
          'label' => [
            '#type' => 'label',
            '#title' => $feature_definition['name'],
            '#suffix' => $feature_definition['description'],
          ],
          '#wrapper_attributes' => ['style' => 'vertical-align: top'],
        ],
        'weight' => [
          '#type' => 'weight',
          '#title' => $this->t('Weight for @option', ['@option' => $feature_definition['name']]),
          '#title_display' => 'invisible',
          '#size' => 4,
          '#default_value' => $weight,
          '#attributes' => ['class' => ['geolocation-layer-feature-option-weight']],
        ],
      ];

      $feature_form = $feature->getSettingsForm(
        $feature->getSettings($feature_settings, $map_provider),
        array_merge($parents, [$feature_id, 'settings']),
        $map_provider
      );

      if (
        $feature_form
        && Element::children($feature_form)
      ) {
        $feature_form['#states'] = [
          'visible' => [
            ':input[id="' . $feature_enable_id . '"]' => ['checked' => TRUE],
          ],
        ];
        $feature_form['#type'] = 'item';

        $layer_features_form[$feature_id]['feature']['settings'] = $feature_form;
      }
    }

    uasort($layer_features_form, [SortArray::class, 'sortByWeightProperty']);

    return $layer_features_form;
  }

  /**
   * Validate feature form.
   *
   * @param array $element
   *   Element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function validateLayerFeatureForms(array $element, FormStateInterface $form_state): void {
    $values = $form_state->getValues();

    $parents = [];
    if (!empty($element['#parents'])) {
      $parents = $element['#parents'];
      $values = NestedArray::getValue($values, $parents);
    }

    foreach ($values as $feature_id => $feature_settings) {
      if (!$feature_settings['enabled']) {
        continue;
      }

      if ($feature = $this->getLayerFeature($feature_id)) {
        $feature_parents = $parents;
        array_push($feature_parents, $feature_id, 'settings');
        $feature->validateSettingsForm(empty($feature_settings['settings']) ? [] : $feature_settings['settings'], $form_state, $feature_parents);
      }
    }
  }

}
