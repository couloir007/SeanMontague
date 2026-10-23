<?php

namespace Drupal\Tests\geolocation_leaflet\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\views\Tests\ViewTestData;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Leaflet marker clustering on an AJAX view.
 *
 * @group geolocation
 *
 * @see https://www.drupal.org/project/geolocation/issues/3568462
 */
#[RunTestsInSeparateProcesses]
class LeafletClustererAjaxTest extends WebDriverTestBase {

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
  ];

  /**
   * Term isolating this test's nodes from the demo content.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static array $testViews = ['geolocation_leaflet_ajax_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->get('views.views_data')->clear();
    ViewTestData::createTestViews(get_class($this), ['geolocation_leaflet_test_views']);

    // geolocation_demo installs 100 nodes with random coordinates. Tagging
    // this test's nodes with their own term lets the view filter them out. The
    // id is hardcoded in that filter, so the two have to match.
    $this->term = Term::create([
      'tid' => 999,
      'vid' => 'geolocation_demo_taxonomy',
      'name' => 'Clusterer AJAX test',
    ]);
    $this->term->save();

    $storage = \Drupal::entityTypeManager()->getStorage('node');
    foreach ([[52, 47], [53, 48], [54, 49]] as $delta => $coordinates) {
      $storage->create([
        'title' => 'Location ' . ($delta + 1),
        'type' => 'geolocation_default_article',
        'field_geolocation_demo_single' => [
          'lat' => $coordinates[0],
          'lng' => $coordinates[1],
        ],
        'field_geolocation_demo_taxonomy' => ['target_id' => $this->term->id()],
      ])->save();
    }
  }

  /**
   * Inspects what is actually attached to the live Leaflet map.
   *
   * A reload builds a fresh feature with a fresh cluster group, so reading the
   * feature would describe only the new group and miss orphaned ones still
   * rendering on the map.
   *
   * @return array
   *   Keys: clusterGroups, clusteredMarkers, markerPaneChildren.
   */
  protected function inspectMap(): array {
    $javascript = <<<JS
(function () {
  const out = { clusterGroups: 0, clusteredMarkers: 0, markerPaneChildren: 0 };
  out.markerPaneChildren = document.querySelectorAll(".leaflet-marker-pane > *").length;
  if (typeof Drupal.geolocation === "undefined" || typeof L.MarkerClusterGroup === "undefined") {
    return JSON.stringify(out);
  }
  for (const map of Drupal.geolocation.maps.initializedMaps.values()) {
    if (!map.leafletMap) {
      continue;
    }
    map.leafletMap.eachLayer((layer) => {
      if (layer instanceof L.MarkerClusterGroup) {
        out.clusterGroups += 1;
        out.clusteredMarkers += layer.getLayers().filter((m) => m instanceof L.Marker).length;
      }
    });
  }
  return JSON.stringify(out);
})()
JS;

    return (array) json_decode((string) $this->getSession()->evaluateScript($javascript), TRUE);
  }

  /**
   * Tests that clustered markers are cleared when the view reloads via AJAX.
   */
  public function testClustererAjaxUpdate(): void {
    $this->drupalGet('geolocation-leaflet-ajax-test');

    $this->assertNotEmpty(
      $this->assertSession()->waitForElementVisible('css', '.geolocation-map-container .leaflet-marker-pane'),
      'Leaflet map initialized.'
    );
    $this->assertJsCondition('document.querySelectorAll(".leaflet-marker-pane > *").length > 0');

    $this->assertSession()->responseContains('Location 1');
    $this->assertSession()->responseContains('Location 3');

    $before = $this->inspectMap();
    $this->assertSame(1, $before['clusterGroups'], 'One cluster group before filtering.');
    $this->assertSame(3, $before['clusteredMarkers'], 'All three markers clustered before filtering.');

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
    $this->assertJsCondition('document.querySelectorAll(".geolocation-map-container.ajax-loading").length === 0');

    $html = $this->getSession()->getPage()->getHtml();
    $this->assertStringContainsString('Location 1', $html);
    $this->assertStringNotContainsString('Location 3', $html);

    $after = $this->inspectMap();

    $this->assertSame(1, $after['clusterGroups'], 'Stale cluster group left attached to the map after AJAX.');
    $this->assertSame(1, $after['clusteredMarkers'], 'Stale markers still clustered on the map after AJAX.');
    $this->assertSame(1, $after['markerPaneChildren'], 'Stale marker icons still rendered after AJAX.');
  }

}
