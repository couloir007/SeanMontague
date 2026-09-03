<?php

namespace Drupal\Tests\geolocation_yandex\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\geolocation\MapProviderInterface;

/**
 * Tests the Yandex JavaScript functionality.
 *
 * @group geolocation
 */
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

    $result = $this->assertSession()->waitForElementVisible('css', '.geolocation-map-container .ymaps3--map-container .ymaps3--marker:last-child .ymaps3--default-marker__icon', 5000);
    $this->assertNotEmpty($result, "Marker element present.");

    $this->assertSession()->elementExists('css', '.ymaps3--marker:last-child .ymaps3--default-marker__popup.ymaps3--default-marker__hider');

    $result->click();

    $this->assertSession()->elementExists('css', '.ymaps3--marker:last-child .ymaps3--default-marker__popup:not(.ymaps3--default-marker__hider)');
  }

}
