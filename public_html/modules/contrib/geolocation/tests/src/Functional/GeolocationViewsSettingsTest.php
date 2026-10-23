<?php

namespace Drupal\Tests\geolocation\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the Views integration.
 *
 * @group geolocation
 */
#[RunTestsInSeparateProcesses]
class GeolocationViewsSettingsTest extends BrowserTestBase {

  /**
   * Modules to enable for this test.
   *
   * @var string[]
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
    'views_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the Views admin UI for the map style and the proximity handlers.
   *
   * Both cases share the same fixture, and every test method reinstalls the
   * site, so they are asserted in one go.
   */
  public function testViewsSettingsForms(): void {
    $admin_user = $this->drupalCreateUser([
      'access administration pages',
      'administer views',
    ]);
    $this->drupalLogin($admin_user);

    // Map style settings of the common map display.
    $this->drupalGet('admin/structure/views/view/geolocation_demo_common_map');
    $this->clickLink('Settings');
    $this->submitForm([
      'style_options[centre][fit_bounds][enable]' => 1,
    ], 'Apply');
    $this->assertSession()->statusCodeEquals(200);

    // Proximity filter settings.
    $this->drupalGet('admin/structure/views/view/geolocation_demo_proximity_filter_sort');
    $this->clickLink('Content: Geolocation Demo Single - Proximity Form');
    $this->submitForm([
      'options[center][coordinates][enable]' => 1,
    ], 'Apply');
    $this->assertSession()->statusCodeEquals(200);
  }

}
