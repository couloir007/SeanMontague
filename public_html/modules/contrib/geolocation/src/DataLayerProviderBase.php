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
 * Defines an interface for geolocation DataLayer plugins.
 */
abstract class DataLayerProviderBase extends PluginBase implements DataLayerProviderInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected LayerFeatureManager $layerFeatureManager,
    protected ModuleHandlerInterface $moduleHandler,
    protected FileSystemInterface $fileSystem,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): DataLayerProviderInterface {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.geolocation.layerfeature'),
      $container->get('module_handler'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsSummary(string $data_layer_option_id = 'default', array $settings = [], ?array $context = NULL): array {
    /* @noinspection PhpUnnecessaryLocalVariableInspection */
    $summary = [$this->getPluginDefinition()['name']];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(string $data_layer_option_id = 'default', array $settings = [], ?array $context = NULL): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'default' => [
        'name' => $this->getPluginDefinition()['name'],
        'description' => $this->getPluginDefinition()['description'],
        'toggleable' => TRUE,
        'default_weight' => 0,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerRenderData(string $data_layer_option_id = 'default', array $settings = [], ?array $context = NULL): array {
    return [
      '#type' => 'geolocation_layer',
      '#id' => $this->getLayerId($data_layer_option_id),
      '#title' => $this->getPluginDefinition()['name'],
      '#attributes' => [
        'data-geolocation-data-layer-provider' => $this->getPluginDefinition()['id'],
        'data-geolocation-data-layer-option' => $data_layer_option_id,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(string $data_layer_option_id, array $settings = [], ?array $context = NULL): string {
    return $this->getPluginDefinition()['label'] ?? $this->t('Layer');
  }

  /**
   * Get ID by option value.
   *
   * @param string $data_layer_option_id
   *   Data layer option ID.
   *
   * @return string
   *   Layer ID.
   */
  protected function getLayerId(string $data_layer_option_id = 'default'): string {
    return $this->getPluginId() . "_" . $data_layer_option_id;
  }

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $render_array, string $data_layer_option_id = 'default', array $settings = [], array $context = []): array {

    $layer_id = $this->getLayerId($data_layer_option_id);

    $render_array['#attached'] = BubbleableMetadata::mergeAttachments($render_array['#attached'] ?? [],
      [
        'drupalSettings' => [
          'geolocation' => [
            'maps' => [
              $render_array['#id'] => [
                'data_layers' => [
                  $layer_id => [
                    'import_path' => $this->getJavascriptModulePath(),
                    'settings' => $settings['settings'] ?? [],
                    'label' => $this->getLabel($data_layer_option_id, $settings, $context),
                  ],
                ],
              ],
            ],
          ],
        ],
      ]
    );

    $render_array['#layers'][$layer_id] = $this->getLayerRenderData($data_layer_option_id, $settings, $context);

    if (!empty($settings['features'])) {
      uasort($settings['features'], [SortArray::class, 'sortByWeightElement']);

      foreach ($settings['features'] as $feature_id => $feature_settings) {
        if (!empty($feature_settings['enabled'])) {
          $feature = $this->layerFeatureManager->getLayerFeature($feature_id);
          if ($feature) {
            if (empty($feature_settings['settings'])) {
              $feature_settings['settings'] = [];
            }
            $render_array = $feature->alterLayer($render_array, $layer_id, $feature->getSettings($feature_settings['settings']), $context);
          }
        }
      }
    }

    return $render_array;
  }

  /**
   * Get the path to load JS module.
   *
   * @return string
   *   JS Module path.
   */
  protected function getJavascriptModulePath() : string {
    $class_name = (new \ReflectionClass($this))->getShortName();

    $module_path = $this->moduleHandler->getModule($this->getPluginDefinition()['provider'])->getPath();

    if (!file_exists($this->fileSystem->realpath($module_path) . '/js/DataLayerProvider/' . $class_name . '.js')) {
      return base_path()
        . $this->moduleHandler->getModule('geolocation')->getPath()
        . '/js/DataLayerProvider/GeolocationDataLayer.js';
    }

    return base_path() . $module_path . '/js/DataLayerProvider/' . $class_name . '.js';
  }

}
