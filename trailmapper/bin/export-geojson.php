<?php

/**
 * Exports the KT trail network from PostGIS to GeoJSON. CLI only.
 *
 * Usage: php trailmapper/bin/export-geojson.php
 */

if (PHP_SAPI !== 'cli') {
  exit;
}

require_once __DIR__ . '/../src/planner.php';

$conn = require __DIR__ . '/../src/db.php';

$output = __DIR__ . '/../../public_html/mapping/GeoJSON/KingdomTrails.geojson';

try {
  file_put_contents($output, trailmapper_network_geojson($conn, 'kt'));
  fwrite(STDERR, "Wrote $output\n");
}
catch (PDOException $e) {
  fwrite(STDERR, "Export failed: " . $e->getMessage() . "\n");
  exit(1);
}
