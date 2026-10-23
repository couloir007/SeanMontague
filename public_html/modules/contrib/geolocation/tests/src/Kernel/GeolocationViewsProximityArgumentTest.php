<?php

namespace Drupal\Tests\geolocation\Kernel;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the proximity views argument.
 *
 * @group geolocation
 */
#[RunTestsInSeparateProcesses]
class GeolocationViewsProximityArgumentTest extends GeolocationDemoKernelTestBase {

  /**
   * View used by this test.
   */
  protected const VIEW_ID = 'geolocation_demo_proximity_argument_and_sort';

  /**
   * Tests the proximity argument to filter by distance.
   */
  public function testProximityArgument(): void {
    $executable = $this->executeViewDisplay(self::VIEW_ID, 'page_1');
    $this->assertEmpty($executable->result, 'No results without content.');

    $this->createDemoNodes([
      'Proximity 1' => ['lat' => 52, 'lng' => 47],
      'Proximity 2' => ['lat' => 53, 'lng' => 48],
      'Proximity 3' => ['lat' => 54, 'lng' => 49],
    ]);

    // Without an argument the required argument yields no results.
    $executable = $this->executeViewDisplay(self::VIEW_ID, 'page_1');
    $this->assertEmpty($executable->result, 'No results without proximity argument.');

    $expectations = [
      '52,47<=1mi' => ['Proximity 1'],
      '52,47<=140' => ['Proximity 1', 'Proximity 2'],
      '52,47>140' => ['Proximity 3'],
    ];

    foreach ($expectations as $argument => $expected) {
      $executable = $this->executeViewDisplay(self::VIEW_ID, 'page_1', [$argument]);
      $titles = $this->getResultTitles($executable);

      sort($titles);
      $this->assertSame($expected, $titles, "Argument '$argument' returned the expected locations.");
    }
  }

  /**
   * Tests argument parsing regressions.
   *
   * @see https://www.drupal.org/project/geolocation/issues/2796999
   * @see https://www.drupal.org/project/geolocation/issues/2856948
   */
  public function testProximityArgumentParsing(): void {
    $this->createDemoNodes([
      'Proximity 4' => ['lat' => 51.4545, 'lng' => -2.5879],
    ]);

    // Rounding error, d.o #2796999: a location exactly at the origin must be
    // within any positive distance.
    $executable = $this->executeViewDisplay(self::VIEW_ID, 'page_1', ['51.4545,-2.5879<5mi']);
    $this->assertSame(['Proximity 4'], $this->getResultTitles($executable), 'No rounding error for a location at the origin (d.o #2796999).');

    // Argument parse, d.o #2856948.
    $executable = $this->executeViewDisplay(self::VIEW_ID, 'page_1', ['52.5,-0.5<=5000']);
    $this->assertSame(['Proximity 4'], $this->getResultTitles($executable), 'Argument with negative longitude parsed correctly (d.o #2856948).');
  }

}
