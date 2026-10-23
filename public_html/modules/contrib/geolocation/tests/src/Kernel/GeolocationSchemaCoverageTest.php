<?php

namespace Drupal\Tests\geolocation\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Render\Element;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests schema and render coverage for providers and features.
 *
 * Saving the view validates the plugin settings against the config schema,
 * rendering it makes sure the plugins can actually build a map.
 *
 * @group geolocation
 */
#[RunTestsInSeparateProcesses]
class GeolocationSchemaCoverageTest extends GeolocationDemoKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'geolocation_google_maps',
    'geolocation_google_static_maps',
    'geolocation_google_maps_demo',
    'geolocation_leaflet',
    'geolocation_yandex',
    'geolocation_here',
    'geolocation_baidu',
  ];

  /**
   * {@inheritdoc}
   */
  protected static array $demoConfig = [
    'geolocation_google_maps',
    'geolocation_google_static_maps',
    'geolocation_google_maps_demo',
    'geolocation_leaflet',
    'geolocation_yandex',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // The CommonMap style only builds a map when there are rows.
    $this->createDemoNodes([
      'Schema coverage 1' => ['lat' => 52, 'lng' => 47],
      'Schema coverage 2' => ['lat' => 53, 'lng' => 48],
    ]);

    // Guard the fixture itself: without rows the style renders nothing at all
    // and every assertion below would pass vacuously.
    $this->assertStringContainsString(
      'geolocation-map-container',
      $this->renderViewDisplay('geolocation_demo_common_map', 'page_1'),
      'Fixture renders a map to begin with.'
    );
  }

  /**
   * Test MapProviders.
   */
  public function testMapProvidersDefaults(): void {
    /** @var \Drupal\geolocation\MapProviderManager $mapProviderManager */
    $mapProviderManager = \Drupal::service('plugin.manager.geolocation.mapprovider');

    $view = $this->loadView('geolocation_demo_common_map');
    foreach (array_keys($mapProviderManager->getDefinitions()) as $mapProviderId) {
      $mapProvider = $mapProviderManager->getMapProvider($mapProviderId);

      $display = &$view->getDisplay('default');
      $display['display_options']['style']['options']['map_provider_id'] = $mapProviderId;
      $display['display_options']['style']['options']['map_provider_settings'] = $mapProvider->getSettings([]);
      $view->save();

      $this->assertMapRendered('geolocation_demo_common_map', 'page_1', "Map provider $mapProviderId");
    }
  }

  /**
   * Test MapFeatures with providers.
   */
  public function testMapProvidersWithMapFeatures(): void {
    /** @var \Drupal\geolocation\MapFeatureManager $mapFeatureManager */
    $mapFeatureManager = \Drupal::service('plugin.manager.geolocation.mapfeature');

    /** @var \Drupal\geolocation\LayerFeatureManager $layerFeatureManager */
    $layerFeatureManager = \Drupal::service('plugin.manager.geolocation.layerfeature');

    /** @var \Drupal\geolocation\MapProviderManager $mapProviderManager */
    $mapProviderManager = \Drupal::service('plugin.manager.geolocation.mapprovider');

    $view = $this->loadView('geolocation_demo_common_map');
    foreach (array_keys($mapProviderManager->getDefinitions()) as $mapProviderId) {
      $mapProvider = $mapProviderManager->getMapProvider($mapProviderId);

      $display = &$view->getDisplay('default');
      $display['display_options']['style']['options']['map_provider_id'] = $mapProviderId;
      $display['display_options']['style']['options']['map_provider_settings'] = $mapProvider->getSettings([]);
      $display['display_options']['style']['options']['map_provider_settings']['map_features'] = [];
      foreach (array_keys($mapFeatureManager->getMapFeaturesByMapType($mapProviderId)) as $mapFeatureId) {
        $mapFeature = $mapFeatureManager->getMapFeature($mapFeatureId);
        $display['display_options']['style']['options']['map_provider_settings']['map_features'][$mapFeatureId] = [
          'enabled' => TRUE,
          'settings' => $mapFeature->getSettings([]),
        ];

        $view->save();

        $this->assertMapRendered('geolocation_demo_common_map', 'page_1', "Map provider $mapProviderId: map feature $mapFeatureId");
      }

      $display['display_options']['style']['options']['map_provider_settings']['data_layers'] = [];
      $display['display_options']['style']['options']['map_provider_settings']['data_layers']['geolocation_default_layer:default'] = [];
      $display['display_options']['style']['options']['map_provider_settings']['data_layers']['geolocation_default_layer:default']['settings'] = [];
      $display['display_options']['style']['options']['map_provider_settings']['data_layers']['geolocation_default_layer:default']['enabled'] = 1;
      $display['display_options']['style']['options']['map_provider_settings']['data_layers']['geolocation_default_layer:default']['weight'] = 1;
      foreach (array_keys($layerFeatureManager->getLayerFeaturesByMapType($mapProviderId)) as $layerFeatureId) {
        $layerFeature = $layerFeatureManager->getLayerFeature($layerFeatureId);
        $display['display_options']['style']['options']['map_provider_settings']['data_layers']['geolocation_default_layer:default']['settings']['features'][$layerFeatureId] = [
          'enabled' => TRUE,
          'settings' => $layerFeature->getSettings([]),
        ];

        $view->save();

        $this->assertMapRendered('geolocation_demo_common_map', 'page_1', "Map provider $mapProviderId: layer feature $layerFeatureId");
      }
    }
  }

  /**
   * Test TileLayerProviders.
   */
  public function testTileLayerProviders(): void {
    /** @var \Drupal\geolocation\TileLayerProviderManager $tileLayerProviderManager */
    $tileLayerProviderManager = \Drupal::service('plugin.manager.geolocation.tilelayerprovider');

    /** @var \Drupal\geolocation\MapProviderManager $mapProviderManager */
    $mapProviderManager = \Drupal::service('plugin.manager.geolocation.mapprovider');

    $mapProvider = $mapProviderManager->getMapProvider('leaflet');

    $view = $this->loadView('geolocation_demo_common_map');
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['map_provider_id'] = 'leaflet';
    $display['display_options']['style']['options']['map_provider_settings'] = $mapProvider->getSettings([]);

    foreach (array_keys($tileLayerProviderManager->getTileLayerProviderDefinitions()) as $tileLayerProviderId) {
      $tileLayerProvider = $tileLayerProviderManager->createInstance($tileLayerProviderId);

      $layers = [];
      foreach (array_keys($tileLayerProvider->getLayerOptions()) as $tileLayerOptionId) {
        $layers[$tileLayerProviderId . ':' . $tileLayerOptionId] = ['enabled' => 1];
      }

      $display['display_options']['style']['options']['map_provider_settings']['tile_layers'] = [
        $tileLayerProviderId => [
          'settings' => $this->getPluginSettings($tileLayerProvider->getSettingsForm()),
          'layers' => $layers,
        ],
      ];
      $view->save();

      $this->assertMapRendered('geolocation_demo_common_map', 'page_1', "Tile layer provider $tileLayerProviderId");
    }
  }

  /**
   * Test MapCenters plugins.
   */
  public function testMapCenters(): void {
    // Boundary based map centers derive their option IDs from a views handler
    // ID and expose options only within a views context.
    $map_displays = [
      'geolocation_demo_commonmap_boundary_filter_with_ajax' => 'attachment_1',
    ];
    foreach ($map_displays as $view_id => $display_id) {
      $view = $this->loadView($view_id);
      $executable = $view->getExecutable();
      $executable->setDisplay($display_id);
      $executable->initStyle();

      $display = &$view->getDisplay($display_id);
      $display['display_options']['style']['options']['centre'] = $this->getMapCenterSettings(['views_style' => $executable->style_plugin]);
      $view->save();

      $this->assertMapRendered($view_id, $display_id, "View $view_id");
    }

    $display = EntityViewDisplay::load('node.geolocation_default_article.default');
    $display->setComponent('field_geolocation_demo_single', [
      'type' => 'geolocation_map',
      'settings' => ['centre' => $this->getMapCenterSettings()],
    ])->save();

    $nodes = $this->createDemoNodes([
      'Map center node' => ['lat' => 49.880657, 'lng' => 10.869212],
    ]);

    $build = \Drupal::entityTypeManager()->getViewBuilder('node')->view($nodes['Map center node']);
    $output = (string) $this->container->get('renderer')->renderInIsolation($build);

    $this->assertStringContainsString('geolocation-map-container', $output, 'Field formatter built a map with all map centers enabled.');
    $this->assertStringContainsString('data-map-type', $output, 'Formatter map carries its map provider.');
  }

  /**
   * Fill a settings form with values, so every setting hits the config schema.
   *
   * @param array $form
   *   Settings form.
   *
   * @return array
   *   Settings.
   */
  protected function getPluginSettings(array $form): array {
    $settings = [];
    foreach (Element::children($form) as $key) {
      $settings[$key] = match ($form[$key]['#type'] ?? 'textfield') {
        'number' => 1,
        'checkbox' => TRUE,
        default => 'test',
      };
    }

    return $settings;
  }

  /**
   * Loads a view for modification.
   *
   * @param string $view_id
   *   View ID.
   *
   * @return \Drupal\views\Entity\View
   *   The view.
   */
  protected function loadView(string $view_id): object {
    $view = \Drupal::entityTypeManager()->getStorage('view')->load($view_id);
    $this->assertNotEmpty($view, "View $view_id available.");

    return $view;
  }

  /**
   * Asserts a view display to render.
   *
   * Static map providers render an image instead of a map container, so this
   * only asserts the display to produce output at all - the point of this test
   * is the config schema validation happening on view save plus the plugins
   * surviving a render.
   *
   * @param string $view_id
   *   View ID.
   * @param string $display_id
   *   Display ID.
   * @param string $message
   *   Assertion message prefix.
   */
  protected function assertMapRendered(string $view_id, string $display_id, string $message): void {
    $output = $this->renderViewDisplay($view_id, $display_id);

    // Static map providers replace the whole style output with an image, so
    // the only provider agnostic assertion left is that something rendered.
    $this->assertNotEmpty($output, "$message rendered output.");
  }

  /**
   * Enable every available map center option.
   *
   * @param array $context
   *   Context.
   *
   * @return array
   *   Center option settings, keyed by option ID.
   */
  protected function getMapCenterSettings(array $context = []): array {
    /** @var \Drupal\geolocation\MapCenterManager $map_center_manager */
    $map_center_manager = \Drupal::service('plugin.manager.geolocation.mapcenter');

    /** @var \Drupal\geolocation\LocationManager $location_manager */
    $location_manager = \Drupal::service('plugin.manager.geolocation.location');

    $centre = [];
    $weight = 0;

    foreach (array_keys($map_center_manager->getDefinitions()) as $map_center_id) {
      $map_center = $map_center_manager->createInstance($map_center_id);

      foreach (array_keys($map_center->getAvailableMapCenterOptions($context)) as $option_id) {
        if ($map_center_id === 'location_plugins') {
          // Option IDs are location plugin IDs here.
          $location = $location_manager->createInstance($option_id);
          $settings = $location->getSettings([]);
          $settings['location_option_id'] = key($location->getAvailableLocationOptions($context));
        }
        else {
          $settings = $map_center->getSettings([]);
        }

        $centre[$option_id] = [
          'enable' => TRUE,
          'weight' => $weight++,
          'map_center_id' => $map_center_id,
          'settings' => $settings,
        ];
      }
    }

    return $centre;
  }

}
