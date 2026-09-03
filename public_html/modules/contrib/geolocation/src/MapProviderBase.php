<?php

namespace Drupal\geolocation;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide Map Provider Base class.
 *
 * @package Drupal\geolocation
 */
abstract class MapProviderBase extends PluginBase implements MapProviderInterface, ContainerFactoryPluginInterface {

  /**
   * JS Scripts to load.
   *
   * @var string[]
   */
  protected array $scripts = [];

  /**
   * CSS Stylesheets to load.
   *
   * @var string[]
   */
  protected array $stylesheets = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected MapFeatureManager $mapFeatureManager,
    protected ModuleHandlerInterface $moduleHandler,
    protected FileSystemInterface $fileSystem,
    protected DataLayerProviderManager $dataLayerProviderManager,
    protected TileLayerProviderManager $tileLayerProviderManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $class_name = (new \ReflectionClass($this))->getShortName();

    $module_path = $this->moduleHandler->getModule($this->getPluginDefinition()['provider'])->getPath();

    if (file_exists($this->fileSystem->realpath($module_path) . '/css/MapProvider/' . $class_name . '.css')) {
      $this->stylesheets[] = base_path() . $module_path . '/css/MapProvider/' . $class_name . '.css';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): MapProviderInterface {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.geolocation.mapfeature'),
      $container->get('module_handler'),
      $container->get('file_system'),
      $container->get('plugin.manager.geolocation.datalayerprovider'),
      $container->get('plugin.manager.geolocation.tilelayerprovider'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [
      'conditional_initialization' => 'no',
      'conditional_description' => t('Clicking this button will embed a map.'),
      'conditional_label' => t('Show map'),
      'conditional_viewport_threshold' => 0.8,
      'map_features' => [],
      'data_layers' => [
        'geolocation_default_layer:default' => [
          'enabled' => TRUE,
        ],
      ],
      'tile_layers' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings(array $settings): array {
    $default_settings = $this->getDefaultSettings();
    $settings = array_replace_recursive($default_settings, $settings);

    foreach ($settings as $key => $setting) {
      if (!isset($default_settings[$key])) {
        unset($settings[$key]);
      }
    }

    foreach ($this->mapFeatureManager->getMapFeaturesByMapType($this->getPluginId()) as $feature_id => $feature_definition) {
      if (!empty($settings['map_features'][$feature_id]['enabled'])) {
        $feature = $this->mapFeatureManager->getMapFeature($feature_id);
        if ($feature) {
          if (empty($settings['map_features'][$feature_id]['settings'])) {
            $settings['map_features'][$feature_id]['settings'] = $feature->getSettings([], $this);
          }
          else {
            $settings['map_features'][$feature_id]['settings'] = $feature->getSettings($settings['map_features'][$feature_id]['settings'], $this);
          }
        }
        else {
          unset($settings['map_features'][$feature_id]);
        }
      }
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsSummary(array $settings): array {
    $summary = [
      $this->t('Map provider: %map_provider', ['%map_provider' => $this->getPluginDefinition()['name']]),
    ];
    foreach ($this->mapFeatureManager->getMapFeaturesByMapType($this->getPluginId()) as $feature_id => $feature_definition) {
      if (!empty($settings['map_features'][$feature_id]['enabled'])) {
        $feature = $this->mapFeatureManager->getMapFeature($feature_id);
        if ($feature) {
          if (!empty($settings['map_features'][$feature_id]['settings'])) {
            $feature_settings = $settings['map_features'][$feature_id]['settings'];
          }
          else {
            $feature_settings = $feature->getSettings([], $this);
          }
          $summary = array_merge(
            $summary,
            $feature->getSettingsSummary($feature_settings, $this)
          );
        }
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], array $context = []): array {
    $states_prefix_parents = $parents;
    $states_prefix = array_shift($states_prefix_parents) . '[' . implode('][', $states_prefix_parents) . ']';

    $form = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('%map_provider Settings', ['%map_provider' => $this->pluginDefinition['name']]),
      '#description' => $this->t('Additional map settings provided by %map_provider', ['%map_provider' => $this->pluginDefinition['name']]),
      '#tree' => TRUE,
      '#parents' => $parents,
    ];

    $form['conditional_initialization'] = [
      '#type' => 'select',
      '#options' => [
        'no' => $this->t('No'),
        'button' => $this->t('Yes, show button'),
        'viewport' => $this->t('Yes, when visible in the viewport'),
        'programmatically' => $this->t('Yes, on custom code'),
      ],
      '#default_value' => $settings['conditional_initialization'],
      '#title' => $this->t('Conditional initialization'),
      '#description' => $this->t('Delay map initialization on specific conditions. This is required for GDPR / DSGVO / CMP compliance! <br /> Call `Drupal.geolocation.maps.initializeDelayed();` to trigger.'),
    ];

    $form['conditional_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Conditional Description'),
      '#default_value' => $settings['conditional_description'],
      '#size' => 60,
      '#states' => [
        'visible' => [
          ':input[name="' . $states_prefix . '[conditional_initialization]"]' => ['value' => 'button'],
        ],
      ],
    ];

    $form['conditional_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Conditional Button Label'),
      '#default_value' => $settings['conditional_label'],
      '#size' => 60,
      '#states' => [
        'visible' => [
          ':input[name="' . $states_prefix . '[conditional_initialization]"]' => ['value' => 'button'],
        ],
      ],
    ];

    $form['conditional_viewport_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Threshold'),
      '#description' => $this->t('Percentage of the map on screen to initialize the map. 1.0 = 100% on screen, 0.1 = 10% on screen.'),
      '#default_value' => $settings['conditional_viewport_threshold'],
      '#min' => 0.1,
      '#max' => 1,
      '#step' => 0.1,
      '#states' => [
        'visible' => [
          'select[name="' . $states_prefix . '[conditional_initialization]"]' => ['value' => 'viewport'],
        ],
      ],
    ];

    if ($this->mapFeatureManager->getMapFeaturesByMapType($this->getPluginId())) {
      $form['map_features'] = [
        '#type' => 'details',
        '#title' => $this->t('Map Features'),
        '#weight' => 2,
        'form' => $this->mapFeatureManager->getOptionsForm($settings['map_features'], array_merge($parents, ['map_features']), $this),
      ];
    }

    $form['data_layers'] = [
      '#type' => 'details',
      '#title' => $this->t('Data Layers & Features'),
      '#weight' => 3,
      'form' => $this->dataLayerProviderManager->getOptionsForm(
        $settings['data_layers'] ?? [],
        array_merge($parents, ['data_layers']),
        $this,
        $context
      ),
    ];

    $form['tile_layers'] = [
      '#type' => 'details',
      '#title' => $this->t('Additional Tile Layers'),
      '#weight' => 4,
      'form' => $this->tileLayerProviderManager->getOptionsForm(
        $settings['tile_layers'] ?? [],
          array_merge($parents, ['tile_layers']),
        $this,
        $context
      ),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRenderArray(array $render_array, array $map_settings, array $context = []): array {

    $map_settings['import_path'] = $this->getJavascriptModulePath();
    $map_settings['scripts'] = $this->scripts;
    $map_settings['stylesheets'] = $this->stylesheets;

    if (!empty($map_settings['map_features'])) {
      uasort($map_settings['map_features'], [SortArray::class, 'sortByWeightElement']);

      foreach ($map_settings['map_features'] as $feature_id => $feature_settings) {
        if (!empty($feature_settings['enabled'])) {
          $feature = $this->mapFeatureManager->getMapFeature($feature_id);
          if ($feature) {
            if (empty($feature_settings['settings'])) {
              $feature_settings['settings'] = [];
            }
            $render_array = $feature->alterMap($render_array, $feature->getSettings($feature_settings['settings']), $context, $this);
          }
        }
      }

      unset($map_settings['map_features']);
    }

    $render_array = $this->dataLayerProviderManager->alterMap($render_array, $map_settings['data_layers'], $context);
    unset($map_settings['data_layers']);

    $render_array = $this->tileLayerProviderManager->alterMap($render_array, $map_settings['tile_layers'], $context);
    unset($map_settings['tile_layers']);

    $render_array['#attached'] = BubbleableMetadata::mergeAttachments(
      $render_array['#attached'] ?? [],
      [
        'drupalSettings' => [
          'geolocation' => [
            'maps' => [
              $render_array['#id'] => &$map_settings,
            ],
          ],
        ],
      ]
    );

    return $render_array;
  }

  /**
   * {@inheritdoc}
   */
  public static function getControlPositions(): array {
    return [];
  }

  /**
   * Get the path to load JS module.
   *
   * @return string|null
   *   JS Module path.
   */
  public function getJavascriptModulePath() : ?string {
    $class_name = (new \ReflectionClass($this))->getShortName();

    $module_path = $this->moduleHandler->getModule($this->getPluginDefinition()['provider'])->getPath();

    if (!file_exists($this->fileSystem->realpath($module_path) . '/js/MapProvider/' . $class_name . '.js')) {
      return NULL;
    }

    return base_path() . $module_path . '/js/MapProvider/' . $class_name . '.js';
  }

}
