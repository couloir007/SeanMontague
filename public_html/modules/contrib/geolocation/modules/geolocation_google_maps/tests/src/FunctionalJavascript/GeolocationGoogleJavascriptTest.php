<?php

namespace Drupal\Tests\geolocation_google_maps\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\geolocation\MapProviderInterface;
use Drupal\views\Entity\View;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the GoogleMaps JavaScript functionality.
 *
 * @group geolocation
 */
#[RunTestsInSeparateProcesses]
class GeolocationGoogleJavascriptTest extends WebDriverTestBase {

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
    'geolocation_google_js_errors',
    'geolocation',
    'geolocation_google_maps',
    'geolocation_google_maps_test',
    'geolocation_google_maps_demo',
  ];

  /**
   * Map provider ID.
   *
   * @var string
   */
  protected string $mapProviderId = 'google_maps';

  /**
   * Map provider.
   *
   * @var \Drupal\geolocation\MapProviderInterface
   */
  protected MapProviderInterface $mapProvider;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->mapProvider = \Drupal::service('plugin.manager.geolocation.mapprovider')->getMapProvider($this->mapProviderId);

    $view = View::load('geolocation_demo_common_map');

    $display = &$view->getDisplay('default');

    $display['display_options']['pager']['type'] = 'none';
    $display['display_options']['pager']['options'] = [];

    $display['display_options']['style']['options']['map_provider_id'] = $this->mapProviderId;
    $display['display_options']['style']['options']['map_provider_settings'] = $this->mapProvider->getSettings([]);

    $display['display_options']['style']['options']['map_provider_settings']['zoom'] = '1';
    $display['display_options']['style']['options']['map_provider_settings']['map_features'] = [];

    $view->save();
  }

  /**
   * Tests the Google Marker.
   */
  public function testMarker(): void {
    $this->drupalGet('geolocation-demo/common-map');

    $result = $this->assertSession()->waitForElementVisible('css', '.geolocation-map-container');
    $this->assertNotEmpty($result, "Container present.");

    $googleErrorMessage = $this->assertSession()->waitForElement('css', '.gm-err-message');
    if ($googleErrorMessage) {
      $errors = $this->getSession()->evaluateScript("sessionStorage.getItem('geolocation_google_js_errors')");
      var_dump("\n" . $errors . "\n");
    }
    $this->assertEmpty($googleErrorMessage, "No Google error messages");

    $result = $this->assertSession()->elementExists('css', '.field-content span[typeof="GeoCoordinates"]');
    $this->assertNotEmpty($result, "Location field content present.");

    $result = $this->assertSession()->waitForElement('css', '.geolocation-map-container gmp-advanced-marker', 10000);
    $this->assertNotEmpty($result, "Marker element present.");
  }

  /**
   * Tests the Google Marker.
   */
  public function testMarkerInfoWindow(): void {
    /** @var \Drupal\geolocation\LayerFeatureManager $layerFeatureManager */
    $layerFeatureManager = \Drupal::service('plugin.manager.geolocation.layerfeature');

    $layerFeature = $layerFeatureManager->getLayerFeature('marker_infowindow');

    $view = View::load('geolocation_demo_common_map');
    $display = &$view->getDisplay('default');

    $display['display_options']['style']['options']['map_provider_settings']['data_layers']['geolocation_default_layer:default']['settings']['features'][$layerFeature->getPluginId()] = [
      'enabled' => TRUE,
      'settings' => $layerFeature->getSettings([]),
    ];
    $view->save();

    $this->drupalGet('geolocation-demo/common-map');

    $this->assertSession()->elementNotExists('css', 'div.gm-style-iw');

    // Markers overlap, so wait for one no other marker covers, and click it.
    $this->assertTrue($this->getSession()->wait(10000, <<<'JS'
      !![...document.querySelectorAll('.geolocation-map-container gmp-advanced-marker')].find((marker) => {
        const rect = marker.getBoundingClientRect();
        if (!marker.contains(document.elementFromPoint(rect.x + rect.width / 2, rect.y + rect.height / 2))) {
          return false;
        }
        marker.setAttribute('data-test-uncovered', '');
        return true;
      })
      JS), "Clickable marker present.");
    $this->click('.geolocation-map-container gmp-advanced-marker[data-test-uncovered]');

    $this->assertSession()->elementExists('css', 'div.gm-style-iw');
  }

  /**
   * Tests the Marker clusterer.
   */
  public function testMarkerClusterer(): void {
    /** @var \Drupal\geolocation\LayerFeatureManager $layerFeatureManager */
    $layerFeatureManager = \Drupal::service('plugin.manager.geolocation.layerfeature');

    $layerFeature = $layerFeatureManager->getLayerFeature('marker_clusterer');

    $view = View::load('geolocation_demo_common_map');
    $display = &$view->getDisplay('default');

    $display['display_options']['style']['options']['map_provider_settings']['data_layers']['geolocation_default_layer:default']['settings']['features'][$layerFeature->getPluginId()] = [
      'enabled' => TRUE,
      'settings' => $layerFeature->getSettings([]),
    ];
    $view->save();

    $this->drupalGet('geolocation-demo/common-map');

    $result = $this->assertSession()->waitForElementVisible('css', '.geolocation-map-container');
    $this->assertNotEmpty($result, "Container present.");

    $googleErrorMessage = $this->assertSession()->waitForElement('css', '.gm-err-message');
    if ($googleErrorMessage) {
      $errors = $this->getSession()->evaluateScript("sessionStorage.getItem('geolocation_google_js_errors')");
      var_dump("\n" . $errors . "\n");
    }
    $this->assertEmpty($googleErrorMessage, "No Google error messages");

    $result = $this->assertSession()->waitForElement('css', '.geolocation-map-container gmp-advanced-marker[title^="Cluster"]', 10000);
    $this->assertNotEmpty($result, "Cluster element present.");
  }

}
