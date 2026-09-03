<?php

namespace Drupal\Tests\geolocation_geometry\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the common map style AJAX JavaScript functionality.
 *
 * @group geolocation
 */
class GeolocationGeometryCountriesTest extends WebDriverTestBase {

  /**
   * Admin User.
   *
   * @var \Drupal\user\Entity\User
   */
  public User $adminUser;

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
    'node',
    'path',
    'user',
    'field',
    'taxonomy',
    'views',
    'geolocation',
    'geolocation_leaflet',
    'geolocation_geometry',
    'geolocation_geometry_data',
    'geolocation_geometry_test_js',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static array $testViews = ['geolocation_countries'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer migrations',
      'access administration pages',
      'administer site configuration',
      'access taxonomy overview',
      'create terms in geolocation_geometry_countries',
    ]);
  }

  /**
   * Tests country import.
   */
  public function testCountryMigrateImport(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/structure/taxonomy/manage/geolocation_geometry_countries/overview');
    $this->assertSession()->pageTextNotContains('Access denied');
    $this->assertSession()->pageTextContains('Countries');

    $this->drupalGet('admin/structure/migrate/manage/geolocation_geometry_data/migrations/geolocation_geometry_countries/execute');
    $this->submitForm(['operation' => 'import'], 'Execute');

    $this->assertSession()->waitForText("done with 'geolocation_geometry_countries'", 60000);
    $this->assertSession()->pageTextContains("done with 'geolocation_geometry_countries'");

    $this->drupalGet('admin/structure/taxonomy/manage/geolocation_geometry_countries/overview');
    $this->assertSession()->pageTextContains('Afghanistan');

    $this->drupalGet('geolocation-countries');
    $this->assertSession()->waitForElement('css', '.messages__content');

    // See test-leaflet-countries-view.js file.
    $this->assertSession()->pageTextNotContains('Geolocation not present.');
    $this->assertSession()->pageTextNotContains('Geolocation map not loaded.');
    $this->assertSession()->pageTextNotContains('No map element present.');
    $this->assertSession()->pageTextNotContains('Map ID not found.');
    $this->assertSession()->pageTextNotContains('Geolocation map not loaded.');
    $this->assertSession()->pageTextNotContains('Geolocation no shapes found.');
    $this->assertSession()->pageTextContains('Geolocation found countries');

    $this->assertTrue((bool) preg_match('/Geolocation found countries:\s*(\d+)/', $this->getSession()->getPage()->getText(), $matches));

    $this->assertTrue((int) $matches[1] > 150);
  }

}
