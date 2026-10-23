<?php

namespace Drupal\Tests\geolocation_google_maps\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the Google Maps "Use current language" setting.
 *
 * @group geolocation
 */
#[RunTestsInSeparateProcesses]
class GeolocationGoogleMapsLanguageTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
    'field',
    'geolocation',
    'geolocation_google_maps',
    'geolocation_google_maps_test',
    'locale',
    'language',
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

    EntityViewDisplay::load('node.article.default')
      ->setComponent('field_geolocation', [
        'type' => 'geolocation_map',
        'settings' => [
          'map_provider_id' => 'google_maps',
        ],
        'weight' => 1,
      ])
      ->save();

    \Drupal::entityTypeManager()->getStorage('node')->create([
      'id' => 1,
      'title' => 'Google map',
      'body' => 'This content renders a Google map.',
      'type' => 'article',
      'field_geolocation' => [
        'lat' => 54,
        'lng' => 49,
      ],
    ])->save();
  }

  /**
   * Tests the Use Current Language option from the settings.
   *
   * Changes the language to French, checking for the French map.
   */
  public function testGoogleMapUsingCurrentLanguage(): void {
    // Log in as an administrator and change geolocation and language settings.
    $admin_user = $this->drupalCreateUser([
      'configure geolocation',
      'administer languages',
      'access administration pages',
    ]);
    $this->drupalLogin($admin_user);

    // Get the geolocation configuration settings page.
    $this->drupalGet('admin/config/services/geolocation/google_maps');

    // Enable the checkbox to use current language.
    $edit = ['use_current_language' => TRUE];
    $this->submitForm($edit, 'Save configuration');

    // Add and set French as the language. See from LanguageSwitchingTest.
    $edit = ['predefined_langcode' => 'fr'];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');

    \Drupal::service('language.config_factory_override')
      ->getOverride('fr', 'language.entity.fr')
      ->set('label', 'français')
      ->save();

    // Enable URL language detection and selection.
    $edit = ['language_interface[enabled][language-url]' => '1'];
    $this->drupalGet('admin/config/regional/language/detection');
    $this->submitForm($edit, 'Save settings');

    $this->drupalGet('fr/node/1');
    $this->assertSession()->elementExists('css', 'html[lang="fr"]');

    $anchor = $this->assertSession()->waitForElement('css', 'a[href^="https://maps.google.com"][href*="hl="]');
    $this->assertNotEmpty($anchor, "Wait for GoogleMaps to be loaded.");
    // To control the test messages, search inside the anchor's href.
    // This is achieved by looking for the "hl" parameter in an anchor's href:
    // https://maps.google.com/maps?ll=54,49&z=10&t=m&hl=fr&gl=US&mapclient=apiv3
    $this->assertStringContainsString('hl=fr', $anchor->getAttribute('href'), 'Did not find expected parameters from Google Maps link for French translation.');
  }

}
