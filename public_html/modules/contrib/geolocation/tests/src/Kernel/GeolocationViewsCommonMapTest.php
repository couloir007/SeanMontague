<?php

namespace Drupal\Tests\geolocation\Kernel;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the common map views style to build a map.
 *
 * @group geolocation
 */
#[RunTestsInSeparateProcesses]
class GeolocationViewsCommonMapTest extends GeolocationDemoKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'geolocation_google_maps',
    'geolocation_google_maps_demo',
  ];

  /**
   * {@inheritdoc}
   */
  protected static array $demoConfig = [
    'geolocation_google_maps',
    'geolocation_google_maps_demo',
  ];

  /**
   * Tests the static and the AJAX enabled common map display.
   */
  public function testCommonMapDisplays(): void {
    $this->createDemoNodes([
      'Common map 1' => ['lat' => 52, 'lng' => 47],
      'Common map 2' => ['lat' => 53, 'lng' => 48],
    ]);

    $displays = [
      'geolocation_demo_common_map' => 'page_1',
      'geolocation_demo_common_map_ajax' => 'common_map_ajax_page',
    ];

    foreach ($displays as $view_id => $display_id) {
      $output = $this->renderViewDisplay($view_id, $display_id);

      $this->assertStringContainsString('geolocation-map-container', $output, "View $view_id built a map container.");
      $this->assertStringContainsString('Common map 1', $output, "View $view_id rendered its rows.");
    }
  }

}
