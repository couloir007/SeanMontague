<?php

namespace Drupal\Tests\geolocation\Kernel;

use Drupal\Core\Render\Element;
use Drupal\KernelTests\KernelTestBase;
use Drupal\geolocation\TileLayerProviderManager;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests tile layer providers.
 *
 * @group geolocation
 */
#[RunTestsInSeparateProcesses]
class TileLayerProviderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'geolocation',
  ];

  /**
   * Tile layer provider manager.
   *
   * @var \Drupal\geolocation\TileLayerProviderManager
   */
  protected TileLayerProviderManager $tileLayerProviderManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->tileLayerProviderManager = \Drupal::service('plugin.manager.geolocation.tilelayerprovider');
  }

  /**
   * Test CARTO tile layers.
   */
  public function testCartoDb(): void {
    $tile_layer_provider = $this->tileLayerProviderManager->createInstance('geolocation_tile_cartodb', ['key' => 'test_key']);

    $this->assertEquals(
      'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png?key=test_key',
      $tile_layer_provider->getTileLayerUrl('geolocation_tile_cartodb:voyager'),
      'Basemap and API key in the tile layer URL.'
    );

    $this->assertStringContainsString(
      'dark_all',
      $tile_layer_provider->getTileLayerUrl('geolocation_tile_cartodb:dark_matter'),
      'Layer option selects the basemap.'
    );
  }

  /**
   * Test CARTO tile layers without an API key.
   */
  public function testCartoDbWithoutApiKey(): void {
    $tile_layer_provider = $this->tileLayerProviderManager->createInstance('geolocation_tile_cartodb');

    $this->assertEquals(
      'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png',
      $tile_layer_provider->getTileLayerUrl('geolocation_tile_cartodb:positron'),
      'Tile layer URL usable without an API key.'
    );
  }

  /**
   * Test layers to be ordered by weight across providers.
   */
  public function testLayerWeight(): void {
    $layers = [
      'geolocation_tile_openseamap' => [
        'layers' => [
          'geolocation_tile_openseamap:seamark' => ['enabled' => 1, 'weight' => -1],
        ],
      ],
      'geolocation_tile_openstreetmap' => [
        'layers' => [
          'geolocation_tile_openstreetmap:mapnik' => ['enabled' => 1, 'weight' => -3],
          'geolocation_tile_openstreetmap:de' => ['enabled' => 1, 'weight' => -2],
        ],
      ],
    ];

    $this->assertEquals(
      [
        'geolocation_tile_openstreetmap:mapnik',
        'geolocation_tile_openstreetmap:de',
        'geolocation_tile_openseamap:seamark',
      ],
      array_keys($this->tileLayerProviderManager->getEnabledLayers($layers)),
      'Layers of all providers sorted bottom to top.'
    );

    $render_array = $this->tileLayerProviderManager->alterMap(['#id' => 'test_map'], $layers);
    $tile_layers = $render_array['#attached']['drupalSettings']['geolocation']['maps']['test_map']['tile_layers'];

    $this->assertEquals(-1, $tile_layers['geolocation_tile_openseamap:seamark']['weight'], 'Weight handed to the map.');
    $this->assertTrue($tile_layers['geolocation_tile_openseamap:seamark']['overlay'], 'Overlay flagged for the map.');
  }

  /**
   * Test the tile layer control to separate base layers from overlays.
   */
  public function testTileLayerControl(): void {
    /** @var \Drupal\geolocation\MapFeatureManager $mapFeatureManager */
    $mapFeatureManager = \Drupal::service('plugin.manager.geolocation.mapfeature');
    $map_feature = $mapFeatureManager->getMapFeature('control_tile_layers');

    $render_array = $map_feature->alterMap([
      '#id' => 'test_map',
      '#settings' => [
        'tile_layers' => [
          'geolocation_tile_openstreetmap' => [
            'layers' => [
              'geolocation_tile_openstreetmap:mapnik' => ['enabled' => 1, 'weight' => -3],
              'geolocation_tile_openstreetmap:de' => ['enabled' => 1, 'weight' => -2],
            ],
          ],
          'geolocation_tile_openseamap' => [
            'layers' => [
              'geolocation_tile_openseamap:seamark' => ['enabled' => 1, 'weight' => -1],
            ],
          ],
        ],
      ],
    ], $map_feature->getSettings([]));

    $control = $render_array['#controls']['control_tile_layers']['control_tile_layers'];

    $this->assertEquals('radio', $control['geolocation_tile_openstreetmap:mapnik']['#type'], 'Base layer offered as radio.');
    $this->assertEquals('radio', $control['geolocation_tile_openstreetmap:de']['#type'], 'Base layer offered as radio.');
    $this->assertEquals('checkbox', $control['geolocation_tile_openseamap:seamark']['#type'], 'Overlay offered as checkbox.');

    $this->assertEquals(
      'geolocation_tile_openstreetmap:de',
      $control['geolocation_tile_openstreetmap:mapnik']['#value'],
      'Topmost base layer active.'
    );
    $this->assertEquals(
      $control['geolocation_tile_openstreetmap:mapnik']['#name'],
      $control['geolocation_tile_openstreetmap:de']['#name'],
      'Base layers exclude each other.'
    );
  }

  /**
   * Test every provider setting to be covered by config schema.
   */
  public function testProviderSettingsSchema(): void {
    $typed_config = \Drupal::service('config.typed');

    foreach (array_keys($this->tileLayerProviderManager->getDefinitions()) as $tile_layer_provider_id) {
      $settings_form = $this->tileLayerProviderManager->createInstance($tile_layer_provider_id)->getSettingsForm();
      if (!$settings_form) {
        continue;
      }

      $schema = $typed_config->getDefinition('plugin.plugin_configuration.geolocation.tile_layer_provider.' . $tile_layer_provider_id);
      $this->assertNotEquals('undefined', $schema['type'] ?? 'undefined', "Schema available for $tile_layer_provider_id.");

      foreach (Element::children($settings_form) as $setting) {
        $this->assertArrayHasKey(
          $setting,
          $schema['mapping']['settings']['mapping'] ?? [],
          "Setting $setting of $tile_layer_provider_id covered by config schema."
        );
      }
    }
  }

  /**
   * Test overlay layers.
   */
  public function testOverlayLayers(): void {
    $render_array = $this->tileLayerProviderManager->alterMap(['#id' => 'test_map'], [
      'geolocation_tile_openweathermap' => [
        'settings' => ['api_key' => 'test_key'],
        'layers' => [
          'geolocation_tile_openweathermap:clouds' => ['enabled' => 1],
        ],
      ],
      'geolocation_tile_cartodb' => [
        'settings' => [],
        'layers' => [
          'geolocation_tile_cartodb:voyager' => ['enabled' => 1],
        ],
      ],
    ]);

    $tile_layers = $render_array['#attached']['drupalSettings']['geolocation']['maps']['test_map']['tile_layers'];

    $clouds = $tile_layers['geolocation_tile_openweathermap:clouds'];
    $this->assertTrue($clouds['overlay'], 'Weather layer flagged as overlay.');
    $this->assertEquals(0.5, $clouds['opacity'], 'Weather layer keeps its opacity.');
    $this->assertStringContainsString('appid=test_key', $clouds['url'], 'Weather layer carries its API key.');

    $this->assertArrayNotHasKey('overlay', $tile_layers['geolocation_tile_cartodb:voyager'], 'Base layer not flagged as overlay.');
  }

  /**
   * Test tile layers attached to a map.
   */
  public function testTileLayerAttached(): void {
    $render_array = $this->tileLayerProviderManager->alterMap(['#id' => 'test_map'], [
      'geolocation_tile_cartodb' => [
        'settings' => ['key' => 'test_key'],
        'layers' => [
          'geolocation_tile_cartodb:voyager' => ['enabled' => 1],
          'geolocation_tile_cartodb:positron' => ['enabled' => 0],
        ],
      ],
    ]);

    $tile_layers = $render_array['#attached']['drupalSettings']['geolocation']['maps']['test_map']['tile_layers'];

    $this->assertArrayNotHasKey('geolocation_tile_cartodb:positron', $tile_layers, 'Disabled layer not attached.');
    $this->assertStringContainsString('key=test_key', $tile_layers['geolocation_tile_cartodb:voyager']['url'], 'Enabled layer attached with its API key.');
    $this->assertStringContainsString('CARTO', $tile_layers['geolocation_tile_cartodb:voyager']['attribution'], 'Attribution attached.');
  }

}
