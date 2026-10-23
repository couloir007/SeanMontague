<?php

namespace Drupal\Tests\geolocation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Test geocoders.
 *
 * @group geolocation
 */
#[RunTestsInSeparateProcesses]
class GeolocationGeocoderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'geolocation',
    'geolocation_google_maps',
    'geolocation_google_static_maps',
    'geolocation_leaflet',
    'geolocation_yandex',
    'geolocation_here',
    'geolocation_baidu',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig([
      'geolocation_google_maps',
      'geolocation_leaflet',
      'geolocation_yandex',
      'geolocation_here',
      'geolocation_baidu',
    ]);
  }

  /**
   * Test all geocoders to be instantiable and callable.
   */
  public function testGeocoders(): void {
    /** @var \Drupal\geolocation\GeocoderManager $geocoderManager */
    $geocoderManager = \Drupal::service('plugin.manager.geolocation.geocoder');
    $geocoderIds = $geocoderManager->getDefinitions();

    $this->assertNotEmpty($geocoderIds, 'Geocoder plugins found.');

    foreach ($geocoderIds as $geocoderId => $definition) {
      $geocoder = $geocoderManager->getGeocoder($geocoderId);
      $this->assertNotEmpty($geocoder, "Geocoder $geocoderId instantiated.");

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
