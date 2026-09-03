<?php

namespace Drupal\Tests\geolocation_leaflet\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\geolocation\GeocoderManager;

/**
 * Tests the new entity API for the geolocation field type.
 *
 * @group geolocation
 */
class NominatimTest extends KernelTestBase {

  /**
   * Geocoder manager.
   *
   * @var \Drupal\geolocation\GeocoderManager
   */
  protected GeocoderManager $geocoderManager;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = [
    'system',
    'geolocation',
    'geolocation_leaflet',
  ];

  /**
   * Test Latitude.
   *
   * @var float
   */
  protected float $latitudeXiningRailwayStation = 36.6207609;

  /**
   * Test Longitude.
   *
   * @var float
   */
  protected float $longitudeXiningRailwayStation = 101.813039;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['system', 'geolocation']);

    $this->geocoderManager = \Drupal::service('plugin.manager.geolocation.geocoder');
  }

  /**
   * Test Geocoding.
   */
  public function testGeocoding(): void {
    $nominatim = $this->geocoderManager->getGeocoder('nominatim');

    $result = $nominatim->geocode('西宁站');

    $this->assertNotEmpty($result, "Geocoding Xining Railway Station gives result.");
    $this->assertEquals($this->latitudeXiningRailwayStation, $result['location']['lat'], "Latitude Xining Railway Station");
    $this->assertEquals($this->longitudeXiningRailwayStation, $result['location']['lng'], "Longitude Xining Railway Station");
  }

  /**
   * Test reverse Geocoding.
   */
  public function testReverseGeocoding(): void {
    $nominatim = $this->geocoderManager->getGeocoder('nominatim');

    $result = $nominatim->reverseGeocode($this->latitudeXiningRailwayStation, $this->longitudeXiningRailwayStation);

    $this->assertNotEmpty($result, "Reverse Geocoding Xining Railway Station gives result.");
    $this->assertEquals('京藏高速', $result['atomics']['road'], 'Correct road for Xining Railway Station found by coordinates.');
  }

}
