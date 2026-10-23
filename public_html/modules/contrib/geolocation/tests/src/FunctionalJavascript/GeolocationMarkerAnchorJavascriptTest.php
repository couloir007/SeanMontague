<?php

namespace Drupal\Tests\geolocation\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\views\Entity\View;
use Drupal\views\Tests\ViewTestData;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the "Marker Scroll-to-ID" and "Marker Zoom By Anchor" layer features.
 *
 * @group geolocation
 */
#[RunTestsInSeparateProcesses]
class GeolocationMarkerAnchorJavascriptTest extends GeolocationJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
    'field',
    'views',
    'geolocation',
    'geolocation_leaflet',
    'geolocation_test_views',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static array $testViews = ['geolocation_leaflet_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    FieldStorageConfig::create([
      'field_name' => 'field_geolocation',
      'entity_type' => 'node',
      'type' => 'geolocation',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_geolocation',
      'label' => 'Geolocation',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();

    EntityViewDisplay::load('node.article.default')
      ->setComponent('field_geolocation', [
        'type' => 'geolocation_latlng',
        'weight' => 1,
      ])
      ->save();

    $this->container->get('views.views_data')->clear();

    ViewTestData::createTestViews(get_class($this), ['geolocation_test_views']);

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    foreach ([1 => [52, 47], 2 => [53, 48], 3 => [54, 49]] as $id => [$lat, $lng]) {
      $node_storage->create([
        'id' => $id,
        'title' => 'Location ' . $id,
        'type' => 'article',
        'field_geolocation' => ['lat' => $lat, 'lng' => $lng],
      ])->save();
    }

    // Zoom to markers by anchor, clicking them afterwards, and show popups.
    $view = View::load('geolocation_leaflet_test');
    $displays = $view->get('display');
    $features = &$displays['default']['display_options']['style']['options']['map_provider_settings']['data_layers']['geolocation_default_layer:default']['settings']['features'];
    $features['marker_zoom_by_anchor'] = [
      'enabled' => TRUE,
      'weight' => 0,
      'settings' => [
        'marker_zoom_anchor_id' => 'anchor-{{ title }}',
        'click_marker' => TRUE,
      ],
    ];
    $features['leaflet_marker_popup']['enabled'] = TRUE;
    unset($features);
    $view->set('display', $displays);
    $view->save();
  }

  /**
   * Tests a link click moving and zooming to its marker and opening the popup.
   */
  public function testLinkZoomsToMarkerAndOpensPopup(): void {
    $this->drupalGet('geolocation-leaflet-test');
    $session = $this->getSession();

    $this->assertTrue($session->wait(10000, 'Drupal.geolocation.maps.initializedMaps.size === 1 && [...Drupal.geolocation.maps.initializedMaps.values()][0].dataLayers.get("default")?.markers.length === 3'), 'Map loaded.');

    // A link a site builder would add elsewhere on the page, below the fold.
    $session->executeScript(<<<'JS'
      const spacer = document.createElement('div');
      spacer.style.height = '3000px';
      document.body.append(spacer);

      const link = document.createElement('a');
      link.id = 'zoom-link';
      link.className = 'geolocation-marker-zoom';
      link.href = '#anchor-Location2';
      link.textContent = 'Zoom';
      document.body.append(link);

      window.geolocationTestMap = [...Drupal.geolocation.maps.initializedMaps.values()][0];
      window.geolocationTestMap.leafletMap.setView([0, 0], 2, {animate: false});
      window.scrollTo(0, document.body.scrollHeight);
      JS);

    $this->getSession()->getPage()->clickLink('Zoom');

    $this->assertTrue($session->wait(5000, <<<'JS'
      (() => {
        const map = window.geolocationTestMap;
        const center = map.leafletMap.getCenter();
        return Math.abs(center.lat - 53) < 0.001
          && Math.abs(center.lng - 48) < 0.001
          && Math.abs(map.wrapper.getBoundingClientRect().top) < 5;
      })()
      JS), 'The map scrolled into view and centered on the marker.');
    $this->assertGreaterThan(2, $session->evaluateScript('window.geolocationTestMap.leafletMap.getZoom()'), 'The map zoomed in on the marker.');
    $popup = $this->assertSession()->waitForElementVisible('css', '.leaflet-popup-content');
    $this->assertNotEmpty($popup, 'The marker popup opened.');
    $this->assertStringContainsString('Location 2', $popup->getText());
    $this->assertSame('', $session->evaluateScript('window.location.hash'), 'The link was not followed.');
  }

}
