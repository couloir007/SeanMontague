<?php

namespace Drupal\Tests\geolocation_leaflet\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\views\Tests\ViewTestData;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the AJAX refreshes a dynamic common map sends.
 *
 * @group geolocation
 *
 * @see https://www.drupal.org/project/geolocation/issues/3619670
 */
#[RunTestsInSeparateProcesses]
class LeafletDynamicMapAjaxTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
    'field',
    'taxonomy',
    'views',
    'views_test_config',
    'geolocation',
    'geolocation_demo',
    'geolocation_leaflet',
    'geolocation_leaflet_test_views',
    'geolocation_test_map_feature',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static array $testViews = ['geolocation_leaflet_ajax_test'];

  /**
   * Longer than the views_refresh_delay of the view plus a round-trip.
   */
  protected const SETTLE_TIME = 5000;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->get('views.views_data')->clear();
    ViewTestData::createTestViews(get_class($this), ['geolocation_leaflet_test_views']);

    // The view only lists nodes tagged with term 999, leaving out the random
    // demo content.
    $term = Term::create([
      'tid' => 999,
      'vid' => 'geolocation_demo_taxonomy',
      'name' => 'Dynamic map AJAX test',
    ]);
    $term->save();

    $storage = \Drupal::entityTypeManager()->getStorage('node');
    foreach ([[52, 47], [53, 48], [54, 49]] as $delta => $coordinates) {
      $storage->create([
        'title' => 'Location ' . ($delta + 1),
        'type' => 'geolocation_default_article',
        'field_geolocation_demo_single' => [
          'lat' => $coordinates[0],
          'lng' => $coordinates[1],
        ],
        'field_geolocation_demo_taxonomy' => ['target_id' => $term->id()],
      ])->save();
    }
  }

  /**
   * Counts the views AJAX requests the page sent since it was loaded.
   *
   * Read from the resource timing buffer, so requests sent before this test
   * got hold of the page count as well.
   *
   * @return int
   *   Number of requests.
   */
  protected function countViewsAjaxRequests(): int {
    return (int) $this->getSession()->evaluateScript(
      'performance.getEntriesByType("resource").filter((entry) => entry.name.includes("/views/ajax")).length'
    );
  }

  /**
   * Opens the view and waits for the map to be initialized.
   */
  protected function openMap(): void {
    $this->drupalGet('geolocation-leaflet-ajax-test');

    $this->assertNotEmpty(
      $this->assertSession()->waitForElementVisible('css', '.geolocation-map-container .leaflet-marker-pane'),
      'Leaflet map initialized.'
    );
    $this->assertJsCondition('Drupal.geolocation.maps.initializedMaps.size === 1');
  }

  /**
   * Waits long enough for any pending refresh to be sent and answered.
   */
  protected function waitForMapToSettle(): void {
    $this->getSession()->wait(self::SETTLE_TIME);
    $this->assertJsCondition('jQuery.active === 0');
  }

  /**
   * Centering the map on its own results is no reason to refresh them.
   */
  public function testNoRefreshWithoutViewportChange(): void {
    $this->openMap();
    $this->waitForMapToSettle();

    $this->assertSame(0, $this->countViewsAjaxRequests(), 'Fitting the map to its results refreshed the view.');
  }

  /**
   * Moving the map refreshes the view once, not again for the rebuilt map.
   */
  public function testSingleRefreshPerViewportChange(): void {
    $this->openMap();
    $this->waitForMapToSettle();
    $before = $this->countViewsAjaxRequests();

    // Straight on the Leaflet map, as a gesture would, bypassing the
    // Geolocation API that marks its own moves as programmatic.
    $this->getSession()->executeScript('
      const map = Drupal.geolocation.maps.initializedMaps.values().next().value;
      map.leafletMap.setZoom(map.leafletMap.getZoom() + 1);
    ');
    $this->waitForMapToSettle();

    $this->assertSame(1, $this->countViewsAjaxRequests() - $before, 'A single viewport change caused a chain of view refreshes.');
  }

  /**
   * Refreshing the view carries the map over instead of building a new one.
   */
  public function testMapKeptOnRefresh(): void {
    $this->openMap();
    $this->waitForMapToSettle();

    $this->getSession()->executeScript('window.mapBeforeRefresh = Drupal.geolocation.maps.initializedMaps.values().next().value;');

    // Narrow the boundary filter down to Location 1 only.
    $this->submitForm(
      [
        'field_geolocation_demo_single_boundary[lat_north_east]' => '52.5',
        'field_geolocation_demo_single_boundary[lng_north_east]' => '47.5',
        'field_geolocation_demo_single_boundary[lat_south_west]' => '51.5',
        'field_geolocation_demo_single_boundary[lng_south_west]' => '46.5',
      ],
      'Apply',
      'views-exposed-form-geolocation-leaflet-ajax-test-page-1'
    );
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextNotContains('Location 3');

    $this->assertJsCondition('(() => {
      const maps = Drupal.geolocation.maps.initializedMaps;
      return maps.size === 1 && maps.values().next().value === window.mapBeforeRefresh;
    })()', 10000, 'The refresh replaced the map instead of carrying it over.');
    $this->assertJsCondition('window.mapBeforeRefresh.dataLayers.get("default")?.markers.length === 1', 10000, 'The carried over map does not show the refreshed results.');
  }

  /**
   * A map feature moving the map, like geocoding, refreshes the view.
   */
  public function testRefreshOnMapFeatureMove(): void {
    \Drupal::configFactory()->getEditable('views.view.geolocation_leaflet_ajax_test')
      ->set('display.attachment_1.display_options.style.options.map_provider_settings.map_features.test_control_move', [
        'enabled' => TRUE,
        'weight' => 0,
        'settings' => [
          'position' => 'topleft',
          'north' => 52.5,
          'east' => 47.5,
          'south' => 51.5,
          'west' => 46.5,
        ],
      ])
      ->save();

    $this->openMap();
    $this->waitForMapToSettle();
    $this->assertSession()->pageTextContains('Location 3');

    $this->assertSession()->elementExists('css', '.geolocation-map-container .test-move')->click();

    $this->assertJsCondition('!document.body.textContent.includes("Location 3")', 10000, 'Moving the map by a map feature did not refresh the view.');
    $this->assertSession()->pageTextContains('Location 1');
    $this->assertSession()->pageTextNotContains('Location 2');
  }

}
