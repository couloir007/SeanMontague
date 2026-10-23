<?php

namespace Drupal\Tests\geolocation_yandex\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\geolocation\MapProviderInterface;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the Yandex JavaScript functionality.
 *
 * @group geolocation
 */
#[RunTestsInSeparateProcesses]
class GeolocationYandexJavascriptTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'geolocation',
    'geolocation_yandex',
    'geolocation_yandex_test',
  ];

  /**
   * Map provider ID.
   *
   * @var string
   */
  protected string $mapProviderId = 'yandex';

  /**
   * Map provider.
   *
   * @var \Drupal\geolocation\MapProviderInterface
   */
  protected MapProviderInterface $mapProvider;

  /**
   * Tests the Google Marker.
   */
  public function testMarker(): void {
    $this->drupalGet('geolocation-yandex-test-view');

    $result = $this->assertSession()->waitForElementVisible('css', '.geolocation-map-container');
    $this->assertNotEmpty($result, "Container present.");

    $result = $this->assertSession()->waitForElementVisible('css', '.geolocation-map-container .ymaps3--map-container', 5000);
    $this->assertNotEmpty($result, "Yandex map present.");

    // Only the marker background carries a box. The wrapping elements are
    // zero-sized, and __icon stays empty unless an icon preset is configured.
    $result = $this->assertSession()->waitForElementVisible('css', '.geolocation-map-container .ymaps3--map-container .ymaps3--marker .ymaps3--default-marker__background', 5000);
    $this->assertNotEmpty($result, "Marker element present.");

    // The popup is a layer feature loading its own package, so it shows up
    // after the marker it belongs to, and starts out hidden.
    $popup = $this->assertSession()->waitForElement('css', '.ymaps3--popup-marker.ymaps3--popup-marker__hide', 5000);
    $this->assertNotEmpty($popup, "Marker popup present and hidden.");

    // Every marker carries a static hint label wide enough to cover its
    // neighbours, so with a view this size no marker can be hit through the
    // DOM. Click the marker the map itself holds instead.
    $this->getSession()->executeScript("Drupal.geolocation.maps.initializedMaps.values().next().value.dataLayers.get('default').markers[0].click();");

    $popup = $this->assertSession()->waitForElementVisible('css', '.ymaps3--popup-marker:not(.ymaps3--popup-marker__hide) .ymaps3--popup-marker_container', 5000);
    $this->assertNotEmpty($popup, "Marker popup opened on click.");
  }

}
