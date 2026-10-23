<?php

namespace Drupal\Tests\geolocation\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\views\Tests\ViewTestData;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the common map style AJAX JavaScript functionality.
 *
 * Leaflet is used as the map provider here on purpose - the dynamic map update
 * is provided by the CommonMap views style, so the tests must not depend on the
 * Google Maps JS API.
 *
 * @group geolocation
 */
#[RunTestsInSeparateProcesses]
class GeolocationCommonMapAjaxJavascriptTest extends GeolocationJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
    'field',
    'views',
    'views_test_config',
    'geolocation',
    'geolocation_leaflet',
    'geolocation_test_views',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static array $testViews = ['geolocation_common_map_ajax_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Add the geolocation field to the article content type.
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

    EntityFormDisplay::load('node.article.default')
      ->setComponent('field_geolocation', [
        'type' => 'geolocation_latlng',
      ])
      ->save();

    EntityViewDisplay::load('node.article.default')
      ->setComponent('field_geolocation', [
        'type' => 'geolocation_latlng',
        'weight' => 1,
      ])
      ->save();

    $this->container->get('views.views_data')->clear();

    ViewTestData::createTestViews(get_class($this), ['geolocation_test_views']);

    $entity_test_storage = \Drupal::entityTypeManager()->getStorage('node');
    $entity_test_storage->create([
      'id' => 1,
      'title' => 'Location 1',
      'body' => 'location 1 test body',
      'type' => 'article',
      'field_geolocation' => [
        'lat' => 52,
        'lng' => 47,
      ],
    ])->save();
    $entity_test_storage->create([
      'id' => 2,
      'title' => 'Location 2',
      'body' => 'location 2 test body',
      'type' => 'article',
      'field_geolocation' => [
        'lat' => 53,
        'lng' => 48,
      ],
    ])->save();
    $entity_test_storage->create([
      'id' => 3,
      'title' => 'Location 3',
      'body' => 'location 3 test body',
      'type' => 'article',
      'field_geolocation' => [
        'lat' => 54,
        'lng' => 49,
      ],
    ])->save();
  }

  /**
   * Tests the boundary filter to update the attached view via AJAX.
   */
  public function testCommonMapAjax(): void {
    $this->drupalGet('geolocation-common-map-ajax-test');

    $this->assertSession()->elementExists('css', '.geolocation-map-container');
    $this->assertSession()->elementExists('css', '.geolocation-location');

    $this->assertNotEmpty(
      $this->assertSession()->waitForElementVisible('css', '.geolocation-map-container.leaflet-container .leaflet-marker-pane'),
      'Leaflet map initialized.'
    );
    $this->assertJsCondition('document.querySelectorAll(".leaflet-marker-pane > *").length === 3');

    $this->assertSession()->responseContains('Location 1');
    $this->assertSession()->responseContains('Location 3');

    // Narrow the boundary filter down to Location 1 only.
    $this->submitForm(
      [
        'field_geolocation_boundary[lat_north_east]' => '53',
        'field_geolocation_boundary[lng_north_east]' => '48',
        'field_geolocation_boundary[lat_south_west]' => '51',
        'field_geolocation_boundary[lng_south_west]' => '46',
      ],
      'Apply',
      'views-exposed-form-geolocation-common-map-ajax-test-page-1'
    );
    $this->assertSession()->assertWaitOnAjaxRequest();

    // The map re-initializes on the AJAX response and may itself trigger
    // another update, so wait for the rendered rows to settle instead of
    // sampling the markup once.
    $this->assertJsCondition('document.body.textContent.includes("Location 1") && !document.body.textContent.includes("Location 3")');

    $html = $this->getSession()->getPage()->getHtml();
    $this->assertStringContainsString('Location 1', $html);
    $this->assertStringNotContainsString('Location 3', $html);
  }

}
