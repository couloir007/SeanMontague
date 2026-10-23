<?php

namespace Drupal\Tests\geolocation_leaflet\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\geolocation\MapProviderInterface;
use Drupal\views\Entity\View;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the leaflet JavaScript functionality.
 *
 * @group geolocation
 */
#[RunTestsInSeparateProcesses]
class GeolocationLeafletJavascriptTest extends WebDriverTestBase {

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
    'geolocation_leaflet',
    'geolocation_leaflet_demo',
  ];

  /**
   * Map provider ID.
   *
   * @var string
   */
  protected string $mapProviderId = 'leaflet';

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

    $view = View::load('geolocation_demo_leaflet_common_map');

    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['map_provider_id'] = $this->mapProviderId;
    $display['display_options']['style']['options']['map_provider_settings'] = $this->mapProvider->getSettings([]);
    $display['display_options']['style']['options']['map_provider_settings']['map_features'] = [];

    $display['display_options']['style']['options']['map_provider_settings']['data_layers'] = [];
    $display['display_options']['style']['options']['map_provider_settings']['data_layers']['geolocation_default_layer:default'] = [];
    $display['display_options']['style']['options']['map_provider_settings']['data_layers']['geolocation_default_layer:default']['settings'] = [];
    $display['display_options']['style']['options']['map_provider_settings']['data_layers']['geolocation_default_layer:default']['enabled'] = 1;
    $display['display_options']['style']['options']['map_provider_settings']['data_layers']['geolocation_default_layer:default']['weight'] = 1;

    $view->save();
  }

  /**
   * Tests CARTO tile layers.
   */
  public function testCartoDbTileLayer(): void {
    $view = View::load('geolocation_demo_leaflet_common_map');
    $display = &$view->getDisplay('default');

    $display['display_options']['style']['options']['map_provider_settings']['tile_layers'] = [
      'geolocation_tile_cartodb' => [
        'settings' => ['key' => 'test_key'],
        'layers' => [
          'geolocation_tile_cartodb:voyager' => ['enabled' => 1],
        ],
      ],
    ];
    $view->save();

    $this->drupalGet('geolocation-demo/leaflet-commonmap');

    $result = $this->assertSession()->waitForElement('css', 'img.leaflet-tile[src*="basemaps.cartocdn.com/rastertiles/voyager"][src*="key=test_key"]');
    $this->assertNotEmpty($result, 'CARTO tiles requested with the API key.');
  }

  /**
   * Tests a base tile layer with an overlay on top.
   */
  public function testTileLayerWithOverlay(): void {
    $view = View::load('geolocation_demo_leaflet_common_map');
    $display = &$view->getDisplay('default');

    $display['display_options']['style']['options']['map_provider_settings']['tile_layers'] = [
      'geolocation_tile_openstreetmap' => [
        'settings' => [],
        'layers' => [
          'geolocation_tile_openstreetmap:de' => ['enabled' => 1],
        ],
      ],
      'geolocation_tile_openseamap' => [
        'settings' => [],
        'layers' => [
          'geolocation_tile_openseamap:seamark' => ['enabled' => 1],
        ],
      ],
    ];
    $view->save();

    $this->drupalGet('geolocation-demo/leaflet-commonmap');

    $this->assertNotEmpty(
      $this->assertSession()->waitForElement('css', 'img.leaflet-tile[src*="tile.openstreetmap.de"]'),
      'Base tile layer loaded.'
    );
    $this->assertNotEmpty(
      $this->assertSession()->waitForElement('css', 'img.leaflet-tile[src*="tiles.openseamap.org"]'),
      'Overlay tile layer loaded on top.'
    );
    $this->assertEmpty(
      $this->getSession()->getPage()->find('css', 'img.leaflet-tile[src*="tile.openstreetmap.org"]'),
      'Configured base layer replaces the default one.'
    );
  }

  /**
   * Tests switching base layers through the tile layer control.
   */
  public function testTileLayerControl(): void {
    /** @var \Drupal\geolocation\MapFeatureManager $mapFeatureManager */
    $mapFeatureManager = \Drupal::service('plugin.manager.geolocation.mapfeature');

    $view = View::load('geolocation_demo_leaflet_common_map');
    $display = &$view->getDisplay('default');

    $display['display_options']['style']['options']['map_provider_settings']['map_features']['control_tile_layers'] = [
      'enabled' => TRUE,
      'settings' => $mapFeatureManager->getMapFeature('control_tile_layers')->getSettings([]),
    ];
    $display['display_options']['style']['options']['map_provider_settings']['tile_layers'] = [
      'geolocation_tile_openstreetmap' => [
        'settings' => [],
        'layers' => [
          'geolocation_tile_openstreetmap:mapnik' => ['enabled' => 1, 'weight' => -2],
          'geolocation_tile_openstreetmap:de' => ['enabled' => 1, 'weight' => -1],
        ],
      ],
      'geolocation_tile_openseamap' => [
        'settings' => [],
        'layers' => [
          'geolocation_tile_openseamap:seamark' => ['enabled' => 1, 'weight' => 0],
        ],
      ],
    ];
    $view->save();

    $this->drupalGet('geolocation-demo/leaflet-commonmap');

    // The map loads every configured tile layer and only drops the inactive
    // base layers again once the control's onMapReady() has run. A map is
    // registered as initialized after its features got readied, so waiting for
    // that is what tells the unloading apart from having not happened yet.
    $this->assertJsCondition('Drupal.geolocation.maps.initializedMaps.size > 0');

    // The topmost base layer starts out active, the other one is unloaded.
    $this->assertNotEmpty(
      $this->assertSession()->waitForElement('css', 'img.leaflet-tile[src*="tile.openstreetmap.de"]'),
      'Active base layer loaded.'
    );
    $this->assertNotEmpty(
      $this->assertSession()->waitForElement('css', 'img.leaflet-tile[src*="tiles.openseamap.org"]'),
      'Overlay loaded next to the base layer.'
    );
    $this->assertEmpty(
      $this->getSession()->getPage()->find('css', 'img.leaflet-tile[src*="tile.openstreetmap.org"]'),
      'Inactive base layer not loaded.'
    );

    // Switching the base layer replaces it and keeps the overlay.
    $this->getSession()->getPage()->find('css', '.geolocation-map-control.control_tile_layers summary')->click();
    $this->getSession()->getPage()->find('css', 'input[data-tile-layer-id="geolocation_tile_openstreetmap:mapnik"]')->click();

    $this->assertNotEmpty(
      $this->assertSession()->waitForElement('css', 'img.leaflet-tile[src*="tile.openstreetmap.org"]'),
      'Selected base layer loaded.'
    );
    $this->assertNotEmpty(
      $this->getSession()->getPage()->find('css', 'img.leaflet-tile[src*="tiles.openseamap.org"]'),
      'Overlay still loaded.'
    );
  }

  /**
   * Tests the CommonMap style.
   */
  public function testMarkerClusterer(): void {
    /** @var \Drupal\geolocation\LayerFeatureManager $layerFeatureManager */
    $layerFeatureManager = \Drupal::service('plugin.manager.geolocation.layerfeature');

    $layerFeature = $layerFeatureManager->getLayerFeature('leaflet_marker_clusterer');

    $view = View::load('geolocation_demo_leaflet_common_map');
    $display = &$view->getDisplay('default');

    $display['display_options']['pager']['type'] = 'none';
    $display['display_options']['pager']['options'] = [];
    $display['display_options']['style']['options']['map_provider_settings']['zoom'] = '1';
    $display['display_options']['style']['options']['map_provider_settings']['data_layers']['geolocation_default_layer:default']['settings']['features']['leaflet_marker_clusterer'] = [
      'enabled' => TRUE,
      'settings' => $layerFeature->getSettings([]),
    ];
    $view->save();

    $this->drupalGet('geolocation-demo/leaflet-commonmap');

    $result = $this->assertSession()->waitForElementVisible('css', 'div.leaflet-marker-icon.marker-cluster');
    $this->assertNotEmpty($result);
  }

}
