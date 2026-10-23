<?php

namespace Drupal\Tests\geolocation_maplibre\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the MapLibre GL map provider.
 *
 * @group geolocation
 */
#[RunTestsInSeparateProcesses]
class MapLibreMapTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * How long to allow for the map to come up, in milliseconds.
   *
   * MapLibre is a megabyte of Javascript in two chunks, and its style, fonts,
   * sprites and tiles come from a second service - so a cold test runner has
   * far more to fetch here than the raster providers ask for, and the ten
   * seconds a wait allows by default is not enough for it.
   */
  protected const MAP_TIMEOUT = 30000;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
    'field',
    'filter',
    'geolocation',
    'geolocation_maplibre',
    'geolocation_maplibre_test',
  ];

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

    $map_provider = \Drupal::service('plugin.manager.geolocation.mapprovider')->createInstance('maplibre');
    $map_provider_settings = $map_provider::getDefaultSettings();
    // A style served by the test site itself: no tile service, no fonts and
    // no sprites, so what these tests wait for is the map coming up rather
    // than a third party answering.
    $map_provider_settings['base_map_id'] = 'custom';
    $map_provider_settings['base_map_settings']['custom'] = [
      'style_url' => base_path() . \Drupal::service('extension.list.module')->getPath('geolocation_maplibre_test') . '/style.json',
    ];
    $map_provider_settings['map_features']['maplibre_control_scale']['enabled'] = TRUE;
    $map_provider_settings['data_layers']['geolocation_default_layer:default']['settings']['features']['maplibre_marker_popup'] = [
      'enabled' => TRUE,
      'weight' => 0,
      'settings' => [],
    ];

    EntityViewDisplay::load('node.article.default')
      ->setComponent('field_geolocation', [
        'type' => 'geolocation_map',
        'weight' => 1,
        'settings' => [
          'map_provider_id' => 'maplibre',
          'map_provider_settings' => $map_provider_settings,
          // A marker only carries content when the formatter renders some,
          // and a popup with nothing to show is never bound.
          'info_text' => [
            'value' => 'Marker content under test',
            'format' => 'plain_text',
          ],
        ],
      ])
      ->save();

    \Drupal::entityTypeManager()->getStorage('node')->create([
      'id' => 1,
      'title' => 'Location 1',
      'type' => 'article',
      'field_geolocation' => [
        'lat' => 52,
        'lng' => 47,
      ],
    ])->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if ($this->mink) {
      // js_testing_ajax_request_test wraps window.fetch and decrements its
      // counter only when the promise fulfils, so an aborted fetch leaves the
      // count permanently above zero and core's teardown then refuses to run.
      // MapLibre aborts tile fetches as a matter of course whenever the
      // viewport changes, so the count has to be cleared by hand - core's own
      // Ajax tests do the same.
      $this->getSession()->executeScript(<<<'JS'
        try {
          Array.from(window.Drupal?.geolocation?.maps?.initializedMaps?.values() ?? []).forEach((map) => {
            map.maplibreMap?.remove();
          });
        } finally {
          // Pinned rather than cleared: a request aborted after this point
          // would otherwise put the count straight back up.
          Object.defineProperty(window, "drupalActiveXhrCount", {
            configurable: true,
            get: () => 0,
            set: () => {},
          });
        }
      JS);
    }

    parent::tearDown();
  }

  /**
   * Wait for the map to have initialized.
   */
  protected function assertMapInitialized(): void {
    $this->assertJsCondition('Drupal.geolocation.maps.initializedMaps.size > 0', static::MAP_TIMEOUT);
  }

  /**
   * Tests that a MapLibre map initializes and shows its marker.
   */
  public function testMapLibreMap(): void {
    $this->drupalGet('node/1');

    $this->assertSession()->elementExists('css', '.geolocation-map-container');

    // MapLibre only paints its canvas once the style has loaded.
    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', '.geolocation-map-container canvas.maplibregl-canvas', static::MAP_TIMEOUT));
    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', '.geolocation-map-container .maplibregl-marker', static::MAP_TIMEOUT));
  }

  /**
   * Tests that shapes are drawn into the shared GeoJSON source.
   */
  public function testShapes(): void {
    $this->drupalGet('node/1');
    $this->assertMapInitialized();

    // The source reports its data asynchronously, so the probe parks its
    // result for the assertions to pick up.
    $this->getSession()->executeScript(<<<'JS'
      window.geolocationShapeProbe = null;

      (async () => {
        const map = Array.from(Drupal.geolocation.maps.initializedMaps.values())[0];

        const polygon = map.createShapePolygon(
          {
            type: "Polygon",
            coordinates: [[[7.1, 50.7], [7.2, 50.7], [7.2, 50.8], [7.1, 50.7]]],
          },
          { fillColor: "#ff0000", strokeColor: "#00ff00", strokeWidth: 3 }
        );

        const line = map.createShapeLine({ type: "LineString", coordinates: [[7.1, 50.7], [7.2, 50.8]] }, {});
        const circle = map.createCircle(map.normalizeCoordinates({ lat: 50.7, lng: 7.1 }), 1000, {});

        const source = map.maplibreMap.getSource("geolocation-shapes");
        const drawn = await source.getData();

        const polygonFeature = drawn.features.find((feature) => feature.id === polygon.maplibreFeatureId);
        const circleFeature = drawn.features.find((feature) => feature.id === circle.maplibreFeatureId);

        line.remove();
        const afterRemove = await source.getData();

        window.geolocationShapeProbe = JSON.stringify({
          layers: ["geolocation-shapes-fill", "geolocation-shapes-line"].map((id) => !!map.maplibreMap.getLayer(id)),
          count: drawn.features.length,
          // The module stores GeoJSON, so a polygon has to reach the source
          // with its coordinates untouched.
          polygonGeometry: polygonFeature.geometry,
          polygonProperties: polygonFeature.properties,
          circleType: circleFeature.geometry.type,
          circleRingLength: circleFeature.geometry.coordinates[0].length,
          countAfterRemove: afterRemove.features.length,
        });
      })();
    JS);

    $this->assertJsCondition('window.geolocationShapeProbe !== null', static::MAP_TIMEOUT);
    $result = json_decode($this->getSession()->evaluateScript('window.geolocationShapeProbe'), TRUE);

    $this->assertSame([TRUE, TRUE], $result['layers'], 'Both shape layers are on the map.');
    $this->assertSame(3, $result['count']);
    $this->assertSame([
      'type' => 'Polygon',
      'coordinates' => [[[7.1, 50.7], [7.2, 50.7], [7.2, 50.8], [7.1, 50.7]]],
    ], $result['polygonGeometry'], 'The stored GeoJSON reaches MapLibre unconverted.');
    $this->assertSame('#ff0000', $result['polygonProperties']['fillColor']);
    $this->assertSame('#00ff00', $result['polygonProperties']['strokeColor']);
    $this->assertSame(3, $result['polygonProperties']['strokeWidth']);
    $this->assertSame('Polygon', $result['circleType'], 'A circle is approximated as a polygon.');
    $this->assertSame(65, $result['circleRingLength'], 'The circle ring closes on its first position.');
    $this->assertSame(2, $result['countAfterRemove'], 'A removed shape leaves the source.');
  }

  /**
   * Tests the navigation, scale and popup features.
   */
  public function testFeatures(): void {
    $this->drupalGet('node/1');
    $this->assertMapInitialized();

    // Navigation is on by default, scale was switched on for this display.
    $this->assertSession()->elementExists('css', '.geolocation-map-container .maplibregl-ctrl-zoom-in');
    $this->assertSession()->elementExists('css', '.geolocation-map-container .maplibregl-ctrl-zoom-out');
    $this->assertSession()->elementExists('css', '.geolocation-map-container .maplibregl-ctrl-compass');
    $this->assertSession()->elementExists('css', '.geolocation-map-container .maplibregl-ctrl-scale');

    // MapLibre carries its own attribution control, so unlike Leaflet the
    // map credits its style without a feature having to be enabled.
    $this->assertSession()->elementExists('css', '.geolocation-map-container .maplibregl-ctrl-attrib');

    // The map is still settling on its centre when the page loads, so wait
    // for the camera to come to rest before treating a zoom change as the
    // button's. Tile loading is deliberately not waited on - a live source
    // keeps fetching long after the camera has stopped.
    $settled = '(() => {
      const map = Array.from(Drupal.geolocation.maps.initializedMaps.values())[0].maplibreMap;
      return !map.isMoving();
    })()';
    $this->assertJsCondition($settled, static::MAP_TIMEOUT);

    // Fitting the map to its single marker leaves it at maximum zoom, so
    // zooming out is the direction that can be observed here.
    $zoom_before = $this->getSession()->evaluateScript('Array.from(Drupal.geolocation.maps.initializedMaps.values())[0].maplibreMap.getZoom()');
    $this->click('.geolocation-map-container .maplibregl-ctrl-zoom-out');

    // Settled again and zoomed in - polling for both together avoids racing
    // the animation in either direction.
    $this->assertJsCondition(<<<JS
      (() => {
        const map = Array.from(Drupal.geolocation.maps.initializedMaps.values())[0].maplibreMap;
        return !map.isMoving() && map.getZoom() < $zoom_before;
      })()
    JS, static::MAP_TIMEOUT);

    // The marker holds the row's rendered content, which the popup shows.
    $this->assertSession()->elementNotExists('css', '.maplibregl-popup');
    $this->click('.geolocation-map-container .maplibregl-marker');
    $popup = $this->assertSession()->waitForElementVisible('css', '.maplibregl-popup .maplibregl-popup-content', static::MAP_TIMEOUT);
    $this->assertNotEmpty($popup, 'Clicking a marker opens its popup.');
    $this->assertStringContainsString('Marker content under test', $popup->getHtml());
  }

}
