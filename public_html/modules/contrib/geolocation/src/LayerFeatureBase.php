<?php

namespace Drupal\geolocation;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LayerFeature Base.
 *
 * @package Drupal\geolocation
 */
abstract class LayerFeatureBase extends PluginBase implements LayerFeatureInterface, ContainerFactoryPluginInterface {

  /**
   * JS scripts to load.
   *
   * @var string[]
   */
  protected array $scripts = [];

  /**
   * Stylesheets to load.
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
    protected ModuleHandlerInterface $moduleHandler,
    protected FileSystemInterface $fileSystem,
    protected Token $token,
    protected LibraryDiscoveryInterface $libraryDiscovery,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): LayerFeatureInterface {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('file_system'),
      $container->get('token'),
      $container->get('library.discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings(array $settings, ?MapProviderInterface $mapProvider = NULL): array {
    $default_settings = $this->getDefaultSettings();
    return array_replace_recursive($default_settings, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsSummary(array $settings, ?MapProviderInterface $mapProvider = NULL): array {
    $summary = [];
    $summary[] = $this->t('%feature enabled', ['%feature' => $this->getPluginDefinition()['name']]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], ?MapProviderInterface $mapProvider = NULL): array {
    $form = [];

    $form['#parents'] = $parents;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateSettingsForm(array $values, FormStateInterface $form_state, array $parents = []): void {}

  /**
   * Get JS Module path.
   *
   * @return string|null
   *   JS file path to load.
   */
  private function getJavascriptModulePath() : ?string {
    $class_name = (new \ReflectionClass($this))->getShortName();

    $module_path = $this->moduleHandler->getModule($this->getPluginDefinition()['provider'])->getPath();

    if (!file_exists($this->fileSystem->realpath($module_path) . '/js/LayerFeature/' . $class_name . '.js')) {
      return NULL;
    }

    return base_path() . $module_path . '/js/LayerFeature/' . $class_name . '.js';
  }

  /**
   * {@inheritdoc}
   */
  public function alterLayer(array $render_array, string $layer_id, array $feature_settings = [], array $context = []): array {

    $render_array['#attached'] = BubbleableMetadata::mergeAttachments($render_array['#attached'] ?? [], [
      'drupalSettings' => [
        'geolocation' => [
          'maps' => [
            $render_array['#id'] => [
              'data_layers' => [
                $layer_id => [
                  'settings' => [
                    'features' => [
                      $this->getPluginId() => [
                        'import_path' => $this->getJavascriptModulePath(),
                        'settings' => $feature_settings,
                        'scripts' => $this->scripts,
                        'stylesheets' => $this->stylesheets,
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ]);

    if (\Drupal::service('library.discovery')->getLibraryByName($this->getPluginDefinition()['provider'], 'layerfeature.' . $this->getPluginId())) {
      $render_array['#attached'] = BubbleableMetadata::mergeAttachments($render_array['#attached'], [
        'library' => [
          $this->getPluginDefinition()['provider'] . '/layerfeature.' . $this->getPluginId(),
        ],
      ]);
    }

    return $render_array;
  }

}
