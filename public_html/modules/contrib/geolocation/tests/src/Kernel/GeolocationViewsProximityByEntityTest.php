<?php

namespace Drupal\Tests\geolocation\Kernel;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the proximity by entity views argument.
 *
 * @group geolocation
 */
#[RunTestsInSeparateProcesses]
class GeolocationViewsProximityByEntityTest extends GeolocationDemoKernelTestBase {

  /**
   * View used by this test.
   */
  protected const VIEW_ID = 'geolocation_demo_proximity_by_entity_argument';

  /**
   * Tests the proximity to be calculated from a referenced entity.
   */
  public function testProximityByEntity(): void {
    $executable = $this->executeViewDisplay(self::VIEW_ID, 'page_1');
    $this->assertEmpty($executable->result, 'No results without an entity argument.');

    $nodes = $this->createDemoNodes([
      'Proximity Origin Node' => ['lat' => 52, 'lng' => 47],
      'Closest Node' => ['lat' => 53, 'lng' => 48],
    ]);
    $origin_id = $nodes['Proximity Origin Node']->id();

    $executable = $this->executeViewDisplay(self::VIEW_ID, 'page_1', [$origin_id]);
    $titles = $this->getResultTitles($executable);

    $this->assertNotContains('Proximity Origin Node', $titles, 'Origin excluded from its own proximity results.');
    $this->assertContains('Closest Node', $titles);

    // The proximity field is calculated in the query, so it only shows up in
    // the rendered output.
    $output = $this->renderViewDisplay(self::VIEW_ID, 'page_1', [$origin_id]);
    $this->assertStringContainsString('Closest Node', $output);
    $this->assertStringContainsString('130.18', $output, 'Distance from the origin rendered.');
  }

  /**
   * Tests an argument entity that has no coordinates.
   *
   * The coordinates lookup returns NULL for it; the argument must treat that
   * as "no reference location" and the view must still execute.
   */
  public function testProximityByEntityWithoutCoordinates(): void {
    $nodes = $this->createDemoNodes([
      'No Coordinates Node' => [],
      'Some Other Node' => ['lat' => 53, 'lng' => 48],
    ]);
    $origin_id = $nodes['No Coordinates Node']->id();

    $executable = $this->executeViewDisplay(self::VIEW_ID, 'page_1', [$origin_id]);
    $titles = $this->getResultTitles($executable);
    $this->assertContains('Some Other Node', $titles, 'View still executes with an origin that has no coordinates.');

    $output = $this->renderViewDisplay(self::VIEW_ID, 'page_1', [$origin_id]);
    $this->assertStringContainsString('Some Other Node', $output);
  }

}
