<?php
/**
 * Standalone test for GeoElevationCalculator.
 *
 * Run with:
 *   lando php public_html/modules/custom/trail_mapper/tests/Unit/GeoElevationCalculatorTest.php
 *
 * Reference values for mtb_ride.gpx (Gaia route, DEM-derived elevations):
 *   Source          Distance      Gain      Loss      Min     Max
 *   Sean's mapping  12.44-12.86   1,229ft   ~1,229ft  —       —
 *   Gaia GPS        12.3mi        1,373ft   1,381ft   811ft   1595ft
 *
 * Loop ride — gain ≈ loss by definition.
 * Sean's mapping numbers are more trusted than Gaia for gain/loss.
 * Min/max consistent across sources.
 */

require_once __DIR__ . '/../../src/Service/GeoElevationCalculator.php';

use Drupal\trail_mapper\Service\GeoElevationCalculator;

// ── Reference values ──────────────────────────────────────────────────────────
const GPX_FILE = __DIR__ . '/../fixtures/mtb_ride.gpx';

const REFERENCE_GAIN_FT = 1229;  // Sean's mapping
const REFERENCE_LOSS_FT = 1229;  // Loop ride — should match gain
const REFERENCE_MIN_FT  = 811;   // Consistent across sources
const REFERENCE_MAX_FT  = 1595;  // Consistent across sources

// Distance: range 12.3–12.86
const REFERENCE_DIST_MIN = 12.2;
const REFERENCE_DIST_MAX = 12.9;

const TOLERANCE_PCT = 8.0;

// ── Run ───────────────────────────────────────────────────────────────────────
echo "\nGeoElevationCalculator Test — MTB Ride (Gaia Route)\n";
echo str_repeat('─', 52) . "\n";

if (!file_exists(GPX_FILE)) {
  echo "ERROR: GPX fixture not found at " . GPX_FILE . "\n";
  exit(1);
}

$coords = GeoElevationCalculator::parseGpx(GPX_FILE);
printf("Points parsed:    %d\n", count($coords));

$stats = GeoElevationCalculator::compute($coords);
if (!$stats) {
  echo "ERROR: compute() returned null.\n";
  exit(1);
}

echo "\nResults:\n";
printf("  Distance:   %s mi\n",   $stats['distance_mi']);
printf("  Gain:       %d ft  (%s m)\n", $stats['elev_gain_ft'], $stats['elev_gain_m']);
printf("  Loss:       %d ft  (%s m)\n", $stats['elev_loss_ft'], $stats['elev_loss_m']);
printf("  Min elev:   %d ft  (%s m)\n", $stats['elev_min_ft'],  $stats['elev_min_m']);
printf("  Max elev:   %d ft  (%s m)\n", $stats['elev_max_ft'],  $stats['elev_max_m']);

// ── Comparisons ───────────────────────────────────────────────────────────────
echo "\nComparison:\n";
$allPassed = true;

$distPass = $stats['distance_mi'] >= REFERENCE_DIST_MIN
         && $stats['distance_mi'] <= REFERENCE_DIST_MAX;
if (!$distPass) $allPassed = false;
printf("  %s Distance:   %s mi  (expected %.1f–%.1f)\n",
  $distPass ? '✓' : '✗',
  $stats['distance_mi'],
  REFERENCE_DIST_MIN, REFERENCE_DIST_MAX
);

$checks = [
  'Gain (ft)'     => [REFERENCE_GAIN_FT, $stats['elev_gain_ft']],
  'Loss (ft)'     => [REFERENCE_LOSS_FT, $stats['elev_loss_ft']],
  'Min elev (ft)' => [REFERENCE_MIN_FT,  $stats['elev_min_ft']],
  'Max elev (ft)' => [REFERENCE_MAX_FT,  $stats['elev_max_ft']],
];

foreach ($checks as $label => [$ref, $got]) {
  $pct  = $ref > 0 ? abs($got - $ref) / $ref * 100 : 0;
  $pass = $pct <= TOLERANCE_PCT;
  if (!$pass) $allPassed = false;
  printf("  %s %-16s ref=%-6d got=%-6d diff=%.1f%%\n",
    $pass ? '✓' : '✗', $label, $ref, $got, $pct
  );
}

echo "\nAlgorithm constants:\n";
printf("  SAMPLE_INTERVAL_M = %s\n",  GeoElevationCalculator::SAMPLE_INTERVAL_M);
printf("  MIN_GRADE         = %s\n",  GeoElevationCalculator::MIN_GRADE);
printf("  MIN_VERTICAL_M    = %s ft\n",
  round(GeoElevationCalculator::MIN_VERTICAL_M * 3.28084, 1)
);

echo "\n" . ($allPassed ? "ALL PASSED ✓" : "NEEDS TUNING ✗") . "\n\n";
exit($allPassed ? 0 : 1);
