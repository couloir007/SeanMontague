<?php

namespace Drupal\Tests\geolocation\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the map formatter behavior common to all map providers.
 *
 * Leaflet is used as the map provider here on purpose - the behavior under test
 * is provided by the formatter, the widget and MapProviderBase, so the tests
 * must not depend on the Google Maps JS API.
 *
 * @group geolocation
 */
#[RunTestsInSeparateProcesses]
class GeolocationMapFormatterTest extends GeolocationJavascriptTestBase {

  /**
   * Admin user.
   */
  protected UserInterface $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
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
          'allow_override_map_settings' => TRUE,
        ],
      ])
      ->save();

    EntityViewDisplay::load('node.article.default')
      ->setComponent('field_geolocation', [
        'type' => 'geolocation_map',
        'settings' => [
          'map_provider_id' => 'leaflet',
          'use_overridden_map_settings' => TRUE,
        ],
        'weight' => 1,
      ])
      ->save();

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $node_storage->create([
      'id' => 1,
      'title' => 'Default map settings',
      'body' => 'This content uses the map settings of the formatter.',
      'type' => 'article',
      'field_geolocation' => [
        'lat' => 52,
        'lng' => 47,
      ],
    ])->save();
    $node_storage->create([
      'id' => 2,
      'title' => 'Custom map settings',
      'body' => 'This content tests if the custom map settings are respected',
      'type' => 'article',
      'field_geolocation' => [
        'lat' => 54,
        'lng' => 49,
        'data' => [
          'map_provider_settings' => [
            'height' => '376px',
            'width' => '229px',
          ],
        ],
      ],
    ])->save();
  }

  /**
   * Tests the map formatter with the map settings of the formatter.
   */
  public function testMapFormatter(): void {
    $this->drupalGet('node/1');

    $this->assertSession()->elementExists('css', '.geolocation-map-container');
    $this->assertMapInitialized();
    $this->assertNotEmpty(
      $this->assertSession()->waitForElementVisible('css', '.geolocation-map-container img.leaflet-marker-icon'),
      'Marker added to the map.'
    );
  }

  /**
   * Tests per-item map settings stored by the widget.
   */
  public function testMapFormatterOverriddenSettings(): void {
    $this->drupalGet('node/2');

    $this->assertMapInitialized();
    $this->assertSession()->elementAttributeContains('css', '.geolocation-map-container', 'style', 'height: 376px');
    $this->assertSession()->elementAttributeContains('css', '.geolocation-map-container', 'style', 'width: 229px');

    // Change the stored map settings through the widget.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/2/edit');

    $details = $this->assertSession()->waitForElementVisible('css', '.geolocation-map-widget > details');
    $this->assertNotEmpty($details, 'Widget offers to override the map settings.');
    $details->click();

    $this->assertNotEmpty(
      $this->assertSession()->waitForElementVisible('css', '[name="field_geolocation[map_provider_settings][height]"]'),
      'Map settings of the widget are accessible.'
    );

    $this->submitForm([
      'field_geolocation[map_provider_settings][height]' => '273px',
    ], 'Save');

    $this->drupalGet('node/2');

    $this->assertMapInitialized();
    $this->assertSession()->elementAttributeContains('css', '.geolocation-map-container', 'style', 'height: 273px');
  }

  /**
   * Tests conditional initialization by button.
   */
  public function testConditionalInitializationByButton(): void {
    $this->setFormatterMapSettings(['conditional_initialization' => 'button']);

    $this->drupalGet('node/1');

    $button = $this->assertSession()->waitForElementVisible('css', '.geolocation-map-container .geolocation-map-conditional button');
    $this->assertNotEmpty($button, 'Button to initialize the map present.');
    $this->assertSession()->elementNotExists('css', '.geolocation-map-container.leaflet-container');

    $button->click();

    $this->assertMapInitialized();
    $this->assertSession()->elementNotExists('css', '.geolocation-map-conditional');
  }

  /**
   * Tests conditional initialization triggered from Javascript.
   */
  public function testConditionalInitializationProgrammatically(): void {
    $this->setFormatterMapSettings(['conditional_initialization' => 'programmatically']);

    $this->drupalGet('node/1');

    $this->assertNotEmpty(
      $this->assertSession()->waitForElement('css', '.geolocation-map-container'),
      'Map container rendered.'
    );
    $this->assertSession()->elementNotExists('css', '.geolocation-map-container.leaflet-container');

    $this->getSession()->evaluateScript('Drupal.geolocation.maps.initializeDelayed();');

    $this->assertMapInitialized();
  }

  /**
   * Sets the map provider settings of the formatter.
   *
   * @param array $settings
   *   Map provider settings to set.
   */
  protected function setFormatterMapSettings(array $settings): void {
    $display = EntityViewDisplay::load('node.article.default');
    $component = $display->getComponent('field_geolocation');
    $component['settings']['map_provider_settings'] = $settings;
    $display->setComponent('field_geolocation', $component)->save();
  }

  /**
   * Asserts a Leaflet map to be initialized in the map container.
   */
  protected function assertMapInitialized(): void {
    $this->assertNotEmpty(
      $this->assertSession()->waitForElementVisible('css', '.geolocation-map-container.leaflet-container'),
      'Leaflet map initialized.'
    );
  }

}
