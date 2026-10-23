<?php

namespace Drupal\Tests\geolocation\Kernel;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the views boundary filter.
 *
 * @group geolocation
 */
#[RunTestsInSeparateProcesses]
class GeolocationViewsBoundaryTest extends GeolocationDemoKernelTestBase {

  /**
   * View used by this test.
   *
   * The filter is locked to a boundary of NE 80,80 to SW 20,20.
   */
  protected const VIEW_ID = 'geolocation_demo_boundary_filter_fixed_values';

  /**
   * Tests the boundary filter.
   */
  public function testBoundaryFilter(): void {
    $executable = $this->executeViewDisplay(self::VIEW_ID, 'page_1');
    $this->assertEmpty($executable->result, 'No results without content.');

    $this->createDemoNodes([
      'Boundary 1' => ['lat' => 52, 'lng' => 47],
      'Boundary 2' => ['lat' => 53, 'lng' => 48],
      'Boundary 3' => ['lat' => 5, 'lng' => 5],
    ]);

    $executable = $this->executeViewDisplay(self::VIEW_ID, 'page_1');
    $titles = $this->getResultTitles($executable);

    $this->assertContains('Boundary 1', $titles);
    $this->assertContains('Boundary 2', $titles);
    $this->assertNotContains('Boundary 3', $titles, 'Location outside the boundary filtered out.');
    $this->assertCount(2, $titles);
  }

}
