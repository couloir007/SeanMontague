<?php

namespace Drupal\geolocation;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MapCenter Base.
 *
 * @package Drupal\geolocation
 */
abstract class MapCenterBase extends PluginBase implements MapCenterInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected ModuleHandlerInterface $moduleHandler,
    protected FileSystemInterface $fileSystem,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): MapCenterInterface {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('file_system')
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
  public function getSettings(array $settings): array {
    $default_settings = $this->getDefaultSettings();

    return array_replace_recursive($default_settings, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(?string $option_id = NULL, array $settings = [], array $context = []): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateSettingsForm(array $values, FormStateInterface $form_state): void {}

  /**
   * {@inheritdoc}
   */
  public function getAvailableMapCenterOptions(array $context = []): array {
    return [
      $this->getPluginId() => $this->getPluginDefinition()['name'],
    ];
  }

  /**
   * Get the path to load JS module.
   *
   * @return string|null
   *   JS Module path.
   */
  protected function getJavascriptModulePath() : ?string {
    $class_name = (new \ReflectionClass($this))->getShortName();

    $module_path = $this->moduleHandler->getModule($this->getPluginDefinition()['provider'])->getPath();

    if (!file_exists($this->fileSystem->realpath($module_path) . '/js/MapCenter/' . $class_name . '.js')) {
      return NULL;
    }

    return base_path() . $module_path . '/js/MapCenter/' . $class_name . '.js';
  }

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $render_array, string $center_option_id, int $weight, array $center_option_settings = [], array $context = []): array {
    $center_data = [
      'weight' => $weight,
      'settings' => $this->getSettings($center_option_settings),
    ];

    if ($import_path = $this->getJavascriptModulePath()) {
      $center_data['import_path'] = $import_path;
    }
    else {
      $center_data['static'] = TRUE;
    }

    $render_array['#attached'] = BubbleableMetadata::mergeAttachments(
      $render_array['#attached'] ?? [],
      [
        'drupalSettings' => [
          'geolocation' => [
            'maps' => [
              $render_array['#id'] => [
                'mapCenter' => [
                  $this->getPluginId() => $center_data,
                ],
              ],
            ],
          ],
        ],
      ]
    );

    return $render_array;
  }

}
