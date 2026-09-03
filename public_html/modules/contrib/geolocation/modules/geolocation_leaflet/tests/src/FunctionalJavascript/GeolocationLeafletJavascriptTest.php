<?php

namespace Drupal\Tests\geolocation_leaflet\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\geolocation\MapProviderInterface;
use Drupal\views\Entity\View;

/**
 * Tests the leaflet JavaScript functionality.
 *
 * @group geolocation
 */
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
