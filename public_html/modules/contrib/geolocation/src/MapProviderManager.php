<?php

namespace Drupal\geolocation;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Search plugin manager.
 *
 * @method MapProviderInterface createInstance($plugin_id, array $configuration = [])
 */
class MapProviderManager extends DefaultPluginManager {

  use LoggerChannelTrait;

  /**
   * Constructs an MapProviderManager object.
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
    parent::__construct('Plugin/geolocation/MapProvider', $namespaces, $module_handler, 'Drupal\geolocation\MapProviderInterface', 'Drupal\geolocation\Attribute\MapProvider');
    $this->alterInfo('geolocation_mapprovider_info');
    $this->setCacheBackend($cache_backend, 'geolocation_mapprovider');
  }

  /**
   * Get map provider.
   *
   * @param string $id
   *   ID.
   * @param array $configuration
   *   Configuration.
   *
   * @return \Drupal\geolocation\MapProviderInterface|null
   *   Map provider.
   */
  public function getMapProvider(string $id, array $configuration = []): ?MapProviderInterface {
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
   * Get default settings.
   *
   * @param string $id
   *   Map provider ID.
   *
   * @return array|null
   *   Settings.
   */
  public function getMapProviderDefaultSettings(string $id): ?array {
    $definitions = $this->getDefinitions();
    if (empty($definitions[$id])) {
      return NULL;
    }

    /** @var \Drupal\geolocation\MapProviderInterface $classname */
    $classname = $definitions[$id]['class'];

    return $classname::getDefaultSettings();
  }

  /**
   * Option available map providers.
   *
   * @return array
   *   Options.
   */
  public function getMapProviderOptions(): array {
    $options = [];
    foreach ($this->getDefinitions() as $id => $definition) {
      $options[$id] = $definition['name'];
    }

    return $options;
  }

  /**
   * Return settings array for map provider after select change.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current From State.
   *
   * @return array|null
   *   Settings form.
   */
  public static function addSettingsFormAjax(array $form, FormStateInterface $form_state): ?array {
    $triggering_element_parents = $form_state->getTriggeringElement()['#array_parents'];

    $settings_element_parents = $triggering_element_parents;
    array_pop($settings_element_parents);
    $settings_element_parents[] = 'map_provider_settings';

    return NestedArray::getValue($form, $settings_element_parents);
  }

}
