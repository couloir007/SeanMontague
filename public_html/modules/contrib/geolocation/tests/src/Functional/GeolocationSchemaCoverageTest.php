<?php

namespace Drupal\Tests\geolocation\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\views\Entity\View;

/**
 * Tests schema for providers and features.
 *
 * @group geolocation
 */
class GeolocationSchemaCoverageTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field',
    'views',
    'taxonomy',
    'geolocation',
    'geolocation_demo',
    'geolocation_google_maps',
    'geolocation_google_maps_demo',
    'geolocation_google_static_maps',
    'geolocation_leaflet',
    'geolocation_yandex',
    'geolocation_here',
    'geolocation_baidu',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test MapProviders.
   */
  public function testMapProvidersDefaults(): void {
    /** @var \Drupal\geolocation\MapProviderManager $mapProviderManager */
    $mapProviderManager = \Drupal::service('plugin.manager.geolocation.mapprovider');
    $mapProviderIds = $mapProviderManager->getDefinitions();

    $view = View::load('geolocation_demo_common_map');
    foreach ($mapProviderIds as $mapProviderId => $definition) {
      $mapProvider = $mapProviderManager->getMapProvider($mapProviderId);

      $display = &$view->getDisplay('default');
      $display['display_options']['style']['options']['map_provider_id'] = $mapProviderId;
      $display['display_options']['style']['options']['map_provider_settings'] = $mapProvider->getSettings([]);
      $view->save();

      $this->drupalGet('geolocation-demo/common-map');
      $this->assertSession()->statusCodeEquals(200);
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

    $view = View::load('geolocation_demo_common_map');
    foreach ($mapProviderManager->getDefinitions() as $mapProviderId => $definition) {
      $mapProvider = $mapProviderManager->getMapProvider($mapProviderId);

      $display = &$view->getDisplay('default');
      $display['display_options']['style']['options']['map_provider_id'] = $mapProviderId;
      $display['display_options']['style']['options']['map_provider_settings'] = $mapProvider->getSettings([]);
      $display['display_options']['style']['options']['map_provider_settings']['map_features'] = [];
      foreach ($mapFeatureManager->getMapFeaturesByMapType($mapProviderId) as $mapFeatureId => $mapFeatureDefinition) {
        $mapFeature = $mapFeatureManager->getMapFeature($mapFeatureId);
        $display['display_options']['style']['options']['map_provider_settings']['map_features'][$mapFeatureId] = [
          'enabled' => TRUE,
          'settings' => $mapFeature->getSettings([]),
        ];

        $view->save();

        $this->drupalGet('geolocation-demo/common-map');
        $status_code = $this->getSession()->getStatusCode();
        $this->assertEquals(200, $status_code, "Testing Map provider $mapProviderId: Map feature $mapFeatureId returning Status 200.");
      }

      $display['display_options']['style']['options']['map_provider_settings']['data_layers'] = [];
      $display['display_options']['style']['options']['map_provider_settings']['data_layers']['geolocation_default_layer:default'] = [];
      $display['display_options']['style']['options']['map_provider_settings']['data_layers']['geolocation_default_layer:default']['settings'] = [];
      $display['display_options']['style']['options']['map_provider_settings']['data_layers']['geolocation_default_layer:default']['enabled'] = 1;
      $display['display_options']['style']['options']['map_provider_settings']['data_layers']['geolocation_default_layer:default']['weight'] = 1;
      foreach ($layerFeatureManager->getLayerFeaturesByMapType($mapProviderId) as $layerFeatureId => $layerFeatureDefinition) {
        $layerFeature = $layerFeatureManager->getLayerFeature($layerFeatureId);
        $display['display_options']['style']['options']['map_provider_settings']['data_layers']['geolocation_default_layer:default']['settings']['features'][$layerFeatureId] = [
          'enabled' => TRUE,
          'settings' => $layerFeature->getSettings([]),
        ];

        $view->save();

        $this->drupalGet('geolocation-demo/common-map');
        $status_code = $this->getSession()->getStatusCode();
        $this->assertEquals(200, $status_code, "Testing Map provider $mapProviderId: Layer feature $layerFeatureId returning Status 200.");
      }
    }
  }

}
