<?php

namespace Drupal\geolocation_maplibre;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\geolocation\MapProviderInterface;

/**
 * Base map plugin manager.
 *
 * @method BaseMapInterface createInstance($plugin_id, array $configuration = [])
 */
class BaseMapManager extends DefaultPluginManager {

  use LoggerChannelTrait;
  use StringTranslationTrait;

  /**
   * Constructs a BaseMapManager object.
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
    parent::__construct('Plugin/geolocation/BaseMap', $namespaces, $module_handler, 'Drupal\geolocation_maplibre\BaseMapInterface', 'Drupal\geolocation_maplibre\Attribute\BaseMap');
    $this->alterInfo('geolocation_basemap_info');
    $this->setCacheBackend($cache_backend, 'geolocation_basemap');
  }

  /**
   * Get a base map.
   *
   * @param string $id
   *   Base map ID.
   * @param array $configuration
   *   Configuration.
   *
   * @return \Drupal\geolocation_maplibre\BaseMapInterface|null
   *   Base map, or NULL if there is no such plugin.
   */
  public function getBaseMap(string $id, array $configuration = []): ?BaseMapInterface {
    if (!$this->hasDefinition($id)) {
      return NULL;
    }

    try {
      return $this->createInstance($id, $configuration);
    }
    catch (\Exception $e) {
      $this->getLogger('geolocation')->warning($e->getMessage());
      return NULL;
    }
  }

  /**
   * Get all base maps as form options.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup>
   *   Base map names, keyed by plugin ID.
   */
  public function getBaseMapOptions(): array {
    $options = [];
    foreach ($this->getDefinitions() as $id => $definition) {
      $options[$id] = $definition['name'];
    }

    return $options;
  }

  /**
   * Let the configured base map alter the map render array.
   *
   * A map draws exactly one base map, but which one and what it needs of the
   * renderer is the plugin's business - so the map provider hands the whole
   * render array over rather than asking each plugin for pieces.
   *
   * @param array $render_array
   *   Render array.
   * @param array $settings
   *   Base map settings of the map provider, keyed by base map ID.
   * @param string|null $base_map_id
   *   Base map to render.
   * @param \Drupal\geolocation\MapProviderInterface $map_provider
   *   Map provider rendering the base map.
   *
   * @return array
   *   Render array.
   */
  public function alterMap(array $render_array, array $settings, ?string $base_map_id, MapProviderInterface $map_provider): array {
    foreach ($this->getEnabledBaseMaps($settings, $base_map_id) as $base_map_settings) {
      $render_array = $base_map_settings['base_map']->alterMap($render_array, $base_map_settings['settings'], $map_provider);
    }

    return $render_array;
  }

  /**
   * Get the base map to render, with its own settings.
   *
   * One entry, or none where the configured base map has gone missing - kept
   * as a list so that callers do not each handle the absence themselves.
   *
   * @param array $settings
   *   Base map settings, keyed by base map ID.
   * @param string|null $base_map_id
   *   Base map to render.
   *
   * @return array<string, array{base_map: \Drupal\geolocation_maplibre\BaseMapInterface, settings: array}>
   *   The base map and its settings, keyed by base map ID.
   */
  public function getEnabledBaseMaps(array $settings, ?string $base_map_id): array {
    if (!$base_map_id) {
      return [];
    }

    $base_map = $this->getBaseMap($base_map_id);
    if (!$base_map) {
      return [];
    }

    return [
      $base_map_id => [
        'base_map' => $base_map,
        'settings' => $settings[$base_map_id] ?? [],
      ],
    ];
  }

  /**
   * Get the options form for choosing and configuring a base map.
   *
   * @param array $settings
   *   Map provider settings holding "base_map_id" and "base_map_settings".
   * @param array $parents
   *   Form parents.
   * @param \Drupal\geolocation\MapProviderInterface|null $map_provider
   *   Map provider the base map will be drawn by.
   *
   * @return array
   *   Options form render array.
   */
  public function getOptionsForm(array $settings, array $parents = [], ?MapProviderInterface $map_provider = NULL): array {
    $form = [
      '#type' => 'container',
      '#parents' => $parents,
      'description' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('The map itself. Unlike tile layers, of which any number stack on a map, there is exactly one base map.'),
      ],
    ];

    $form['base_map_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Base map style'),
      '#options' => $this->getBaseMapOptions(),
      '#default_value' => $settings['base_map_id'] ?? NULL,
    ];

    // Every base map's own settings are built, and the one in use shown -
    // switching base map otherwise loses what was entered for the other.
    foreach (array_keys($this->getBaseMapOptions()) as $base_map_id) {
      $base_map = $this->getBaseMap($base_map_id);
      if (!$base_map) {
        continue;
      }

      $base_map_form = $base_map->getSettingsForm(
        $settings['base_map_settings'][$base_map_id] ?? [],
        array_merge($parents, ['base_map_settings', $base_map_id])
      );

      if (!$base_map_form) {
        continue;
      }

      $form['base_map_settings'][$base_map_id] = $base_map_form + [
        '#type' => 'fieldset',
        '#title' => $base_map->getPluginDefinition()['name'],
        '#description' => $base_map->getPluginDefinition()['description'] ?? '',
        '#states' => [
          'visible' => [
            ':input[name$="[base_map_id]"]' => ['value' => $base_map_id],
          ],
        ],
      ];
    }

    return $form;
  }

}
