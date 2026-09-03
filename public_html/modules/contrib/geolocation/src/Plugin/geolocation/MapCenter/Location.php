<?php

namespace Drupal\geolocation\Plugin\geolocation\MapCenter;

use Drupal\geolocation\Attribute\MapCenter;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\geolocation\LocationManager;
use Drupal\geolocation\MapCenterBase;
use Drupal\geolocation\MapCenterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Location based map center.
 */
#[MapCenter(
  id: 'location_plugins',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Location Plugins'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Select a location plugin.')
)]
class Location extends MapCenterBase implements MapCenterInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ModuleHandlerInterface $moduleHandler,
    FileSystemInterface $fileSystem,
    protected LocationManager $locationManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $moduleHandler, $fileSystem);
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
      $container->get('file_system'),
      $container->get('plugin.manager.geolocation.location')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(?string $option_id = NULL, array $settings = [], array $context = []): array {
    $location_plugin_id = $option_id;

    if (!$this->locationManager->hasDefinition($location_plugin_id)) {
      return [];
    }

    $form = [];

    $location_plugin = $this->locationManager->createInstance($location_plugin_id);
    $location_options = $location_plugin->getAvailableLocationOptions($context);

    if (!$location_options) {
      return [];
    }

    if (!empty($settings['location_option_id'])) {
      $location_option_id = $settings['location_option_id'];
    }
    else {
      $location_option_id = key($location_options);
    }

    if (count($location_options) == 1) {
      $form['location_option_id'] = [
        '#type' => 'value',
        '#value' => $location_option_id,
      ];
    }
    else {
      $options = [];
      foreach ($location_options as $location_option_id => $location_option_label) {
        $options[$location_option_id] = $location_option_label;
      }
      $form['location_option_id'] = [
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => $location_option_id,
      ];
    }

    $location_plugin = $this->locationManager->createInstance($location_plugin_id);

    return array_merge_recursive($form, $location_plugin->getSettingsForm($location_option_id, $settings, $context));
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableMapCenterOptions(array $context = []): array {
    $options = [];

    foreach ($this->locationManager->getDefinitions() as $location_plugin_id => $location_plugin_definition) {
      $location_plugin = $this->locationManager->createInstance($location_plugin_id);
      $location_options = $location_plugin->getAvailableLocationOptions($context);

      if (!$location_options) {
        continue;
      }
      $options[$location_plugin_id] = $location_plugin_definition['name'];
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $render_array, string $center_option_id, int $weight, array $center_option_settings = [], array $context = []): array {
    if (!$this->locationManager->hasDefinition($center_option_id)) {
      return $render_array;
    }

    $location = $this->locationManager->createInstance($center_option_id);

    if (!empty($center_option_settings['location_option_id'])) {
      $location_id = $center_option_settings['location_option_id'];
    }
    else {
      $location_id = $center_option_id;
    }

    $render_array['#centre'] = $location->getCoordinates($location_id, $center_option_settings, $context);

    $render_array['#attached'] = BubbleableMetadata::mergeAttachments($render_array['#attached'] ?? [], [
      'drupalSettings' => [
        'geolocation' => [
          'maps' => [
            $render_array['#id'] => [
              'mapCenter' => [
                'location_plugins_' . $location_id => [
                  'weight' => $weight,
                  'import_path' => $this->getJavascriptModulePath(),
                  'settings' => [
                    'success' => (bool) $location,
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ]);

    return $render_array;
  }

}
