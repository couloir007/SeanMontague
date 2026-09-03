<?php

namespace Drupal\geolocation;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Search plugin manager.
 *
 * @method GeocoderInterface createInstance($plugin_id, array $configuration = [])
 */
class GeocoderManager extends DefaultPluginManager {

  /**
   * Constructs an GeocoderManager object.
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
    parent::__construct('Plugin/geolocation/Geocoder', $namespaces, $module_handler, 'Drupal\geolocation\GeocoderInterface', 'Drupal\geolocation\Attribute\Geocoder');
    $this->alterInfo('geolocation_geocoder_info');
    $this->setCacheBackend($cache_backend, 'geolocation_geocoder');
  }

  /**
   * Return Geocoder by ID.
   */
  public function getGeocoder(string $id, array $configuration = []): ?GeocoderInterface {
    if (!$this->hasDefinition($id)) {
      return NULL;
    }

    try {
      return $this->createInstance($id, $configuration);
    }
    catch (\Exception $e) {
      \Drupal::logger('geolocation')->error($e->getMessage());
      return NULL;
    }
  }

  /**
   * Options select element.
   *
   * @param array $options
   *   Geocoder Options.
   *
   * @return array
   *   Render element.
   */
  public function geocoderOptionsSelect(array $options = []): array {
    if (empty($options)) {
      foreach ($this->getDefinitions() as $geocoder_id => $geocoder_definition) {
        if (empty($geocoder_definition['locationCapable'])) {
          continue;
        }
        $options[$geocoder_id] = $geocoder_definition['name'];
      }
    }

    return [
      '#type' => 'select',
      '#options' => $options,
      '#title' => t('Geocoder Plugin'),
      '#ajax' => [
        'callback' => [
          get_class($this), 'addGeocoderSettingsFormAjax',
        ],
        'wrapper' => 'geolocation-geocoder-plugin-settings',
        'effect' => 'fade',
      ],
    ];
  }

  /**
   * Return settings array for geocoder after select change.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current From State.
   *
   * @return array
   *   Settings form.
   */
  public static function addGeocoderSettingsFormAjax(array $form, FormStateInterface $form_state): array {
    /** @var \Drupal\geolocation\GeocoderInterface $geocoder */
    $geocoder = \Drupal::service('plugin.manager.geolocation.geocoder')->getGeocoder($form_state->getTriggeringElement()['#value']);

    return $geocoder->getOptionsForm();
  }

}
