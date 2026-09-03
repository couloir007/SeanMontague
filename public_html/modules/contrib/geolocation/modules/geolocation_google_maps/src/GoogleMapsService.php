<?php

namespace Drupal\geolocation_google_maps;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\geolocation\KeyProvider;

/**
 * Google Maps URL stuff.
 */
class GoogleMapsService {

  /**
   * Constructor.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected ModuleHandlerInterface $moduleHandler,
    protected LanguageManagerInterface $languageManager,
  ) {}

  /**
   * Google Maps url.
   *
   * @var string
   */
  public static string $googleMapsApiUrlBase = 'https://maps.googleapis.com';

  /**
   * Google Maps url from PR China.
   *
   * @var string
   */
  public static string $googleMapsApiUrlBaseChina = 'https://maps.google.cn';

  /**
   * Google Maps url from PR China.
   *
   * @var string
   */
  public static string $googleMapsApiUrlPath = '/maps/api';

  /**
   * Return all module and custom defined parameters.
   *
   * @param array $additional_parameters
   *   Additional parameters.
   *
   * @return array
   *   Parameters
   */
  public function getGoogleMapsApiParameters(array $additional_parameters = []): array {
    $config = $this->configFactory->get('geolocation_google_maps.settings');
    $geolocation_parameters = [
      'key' => KeyProvider::getKeyValue($config->get('google_map_api_key') ?? ''),
      'libraries' => ['marker'],
    ];

    $module_parameters = $this->moduleHandler->invokeAll('geolocation_google_maps_parameters') ?: [];
    $custom_parameters = $config->get('google_map_custom_url_parameters') ?: [];

    // Set the map language to site language if desired and possible.
    if ($config->get('use_current_language') && $this->moduleHandler->moduleExists('language')) {
      $custom_parameters['language'] = $this->languageManager->getCurrentLanguage()->getId();
    }

    $parameters = NestedArray::mergeDeep($geolocation_parameters, $additional_parameters, $custom_parameters, $module_parameters);

    foreach ($parameters as $key => $value) {
      if ($value === '') {
        unset($parameters[$key]);
      }
    }

    if (!empty($parameters['client'])) {
      unset($parameters['key']);
    }

    return $parameters;
  }

  /**
   * Return the fully built URL to load Google Maps API.
   *
   * @param array $additional_parameters
   *   Additional parameters.
   * @param string $path
   *   Additional Path.
   *
   * @return string
   *   Google Maps API URL
   */
  public function getGoogleMapsApiUrl(array $additional_parameters = [], string $path = ''): string {
    $config = $this->configFactory->get('geolocation_google_maps.settings');

    if (!empty($config->get('google_maps_base_url'))) {
      $google_url = $config->get('google_maps_base_url');
    }
    elseif ($config->get('china_mode')) {
      $google_url = static::$googleMapsApiUrlBaseChina;
    }
    else {
      $google_url = static::$googleMapsApiUrlBase;
    }

    $parameters = [];
    foreach ($this->getGoogleMapsApiParameters($additional_parameters) as $parameter => $value) {
      $parameters[$parameter] = is_array($value) ? implode(',', $value) : $value;
    }
    $url = Url::fromUri($google_url . static::$googleMapsApiUrlPath . $path, [
      'query' => $parameters,
      'https' => TRUE,
    ]);

    return $url->toString();
  }

}
