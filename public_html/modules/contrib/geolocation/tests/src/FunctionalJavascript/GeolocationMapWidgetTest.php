<?php

namespace Drupal\Tests\geolocation\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the map widget functionality.
 *
 * Leaflet is used as the map provider here on purpose - the behavior under test
 * is provided by the widget itself, so the tests must not depend on the Google
 * Maps JS API.
 *
 * @group geolocation
 */
#[RunTestsInSeparateProcesses]
class GeolocationMapWidgetTest extends GeolocationJavascriptTestBase {

  /**
   * Admin user.
   */
  protected UserInterface $adminUser;

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
    'geolocation',
    'geolocation_leaflet',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    $this->adminUser = $this->drupalCreateUser([
      'bypass node access',
      'administer nodes',
    ]);

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
        'type' => 'geolocation_map',
        'settings' => [
          'map_provider_id' => 'leaflet',
        ],
      ])
      ->save();

    EntityViewDisplay::load('node.article.default')
      ->setComponent('field_geolocation', [
        'type' => 'geolocation_map',
        'settings' => [
          'map_provider_id' => 'leaflet',
        ],
      ])
      ->save();

    \Drupal::entityTypeManager()->getStorage('node')->create([
      'id' => 1,
      'title' => 'Existing location',
      'body' => 'test test',
      'type' => 'article',
      'field_geolocation' => [
        'lat' => 52,
        'lng' => 47,
      ],
    ])->save();
  }

  /**
   * Tests the map widget to render a map.
   */
  public function testWidgetMapPresent(): void {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('node/1/edit');

    $this->assertSession()->elementExists('css', '.geolocation-map-widget .geolocation-map-container');
    $this->assertNotEmpty(
      $this->assertSession()->waitForElementVisible('css', '.geolocation-map-container.leaflet-container'),
      'Leaflet map initialized in the widget.'
    );
    $this->assertNotEmpty(
      $this->assertSession()->waitForElementVisible('css', '.geolocation-map-container img.leaflet-marker-icon'),
      'Existing coordinates added as marker.'
    );
  }

  /**
   * Tests an AJAX rebuild of the widget items to keep the map.
   *
   * Adding an item replaces the item inputs next to the map, not the map
   * itself.
   */
  public function testWidgetMapKeptOnAddMore(): void {
    FieldStorageConfig::loadByName('node', 'field_geolocation')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->save();

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('node/1/edit');

    $this->assertNotEmpty(
      $this->assertSession()->waitForElementVisible('css', '.geolocation-map-container img.leaflet-marker-icon'),
      'Existing coordinates added as marker.'
    );

    $this->getSession()->executeScript('window.mapBeforeRebuild = Drupal.geolocation.maps.initializedMaps.values().next().value;');

    $this->getSession()->getPage()->pressButton('Add another item');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertNotEmpty($this->assertSession()->waitForField('field_geolocation[1][lat]'), 'Item added.');

    $this->assertJsCondition('(() => {
      const maps = Drupal.geolocation.maps.initializedMaps;
      return maps.size === 1 && maps.values().next().value === window.mapBeforeRebuild && window.mapBeforeRebuild.features.size > 0;
    })()', 10000, 'The rebuild replaced or destroyed the widget map.');

    $this->assertSession()->fieldValueEquals('field_geolocation[0][lat]', '52');
    $this->assertSession()->fieldValueEquals('field_geolocation[0][lng]', '47');
    $this->assertSession()->elementExists('css', '.geolocation-map-container img.leaflet-marker-icon');
  }

  /**
   * Tests an empty field to stay empty when saved from the widget.
   */
  public function testWidgetEmptyValuePreserved(): void {
    // Center the widget map on a fixed location. Those coordinates must not
    // end up in the field just because a map was displayed.
    EntityFormDisplay::load('node.article.default')
      ->setComponent('field_geolocation', [
        'type' => 'geolocation_map',
        'settings' => [
          'map_provider_id' => 'leaflet',
          'centre' => [
            'fixed_value' => [
              'enable' => TRUE,
              'weight' => 0,
              'map_center_id' => 'location_plugins',
              'settings' => [
                'location_option_id' => 'fixed_value',
                'latitude' => 12.0,
                'longitude' => 24.0,
              ],
            ],
          ],
        ],
      ])
      ->save();

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('node/add/article');

    $this->assertNotEmpty(
      $this->assertSession()->waitForElementVisible('css', '.geolocation-map-container.leaflet-container'),
      'Leaflet map initialized in the widget.'
    );

    $page = $this->getSession()->getPage();
    $page->fillField('title[0][value]', 'I am new');
    $page->pressButton('Save');

    // Saving is a full page load. Wait for the redirect through evaluated
    // Javascript - querying elements while the document is being replaced
    // yields stale element references.
    $this->assertJsCondition('!window.location.pathname.endsWith("/node/add/article")');

    $new_node = $this->drupalGetNodeByTitle('I am new');
    $this->assertNotEmpty($new_node, 'Node created.');
    $this->assertTrue($new_node->get('field_geolocation')->isEmpty(), 'Node geolocation field empty after saving without touching the map widget.');
  }

}
