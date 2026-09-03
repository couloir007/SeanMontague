<?php

namespace Drupal\Tests\geolocation\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test geocoders.
 *
 * @group geolocation
 */
class GeolocationGeocoderTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field',
    'views',
    'taxonomy',
    'geolocation',
    'geolocation_demo',
    'geolocation_google_maps',
    'geolocation_google_maps_demo',
    'geolocation_google_static_maps',
    'geolocation_leaflet',
    'geolocation_yandex',
    'geolocation_here',
    'geolocation_baidu',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test MapProviders.
   */
  public function testGeocoders(): void {
    /** @var \Drupal\geolocation\GeocoderManager $geocoderManager */
    $geocoderManager = \Drupal::service('plugin.manager.geolocation.geocoder');
    $geocoderIds = $geocoderManager->getDefinitions();

    foreach ($geocoderIds as $geocoderId => $definition) {
      $geocoder = $geocoderManager->getGeocoder($geocoderId);

      $definition = $geocoder->getPluginDefinition();

      if ($definition['locationCapable'] ?? FALSE) {
        $geocoder->geocode('Isfahan');
      }

      if ($definition['reverseCapable'] ?? FALSE) {
        $geocoder->reverseGeocode(32.6575, 51.676667);
      }

    }
  }

}
