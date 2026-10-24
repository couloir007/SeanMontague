<?php

/**
 * Exports the KT trail network from PostGIS to GeoJSON. CLI only.
 *
 * Usage: php trailmapper/bin/export-geojson.php
 */

if (PHP_SAPI !== 'cli') {
  exit;
}

$conn = require __DIR__ . '/../src/db.php';

$table = 'kt_trails';
$output = __DIR__ . '/../../public_html/mapping/GeoJSON/KingdomTrails.geojson';
$scr = 6589;

$query = "
        WITH tmp1 as (
            SELECT 'Feature' as " . '"type"' . ",
            ST_AsGeoJSON(ST_Transform(ST_SetSRID(t.geom," . $scr . "), 4326), 6)::json as " . '"geometry"' . ",
            (
                SELECT json_strip_nulls(row_to_json(t))
                FROM (select objectid As id, name, touches,  ST_Length(ST_Transform(geom," . $scr . ")) as length) t
            ) as " . '"properties"' . "
            FROM public." . $table . " t
        ),
        tmp2 as (
            SELECT 'FeatureCollection' as " . '"type"' . ",
            array_to_json(array_agg(t)) as " . '"features"' . "
            FROM tmp1 t
        ) SELECT row_to_json(t)
        FROM tmp2 t;
        ";

try {
  foreach ($conn->query($query) as $row) {
    file_put_contents($output, $row['row_to_json']);
    fwrite(STDERR, "Wrote $output\n");
  }
}
catch (PDOException $e) {
  fwrite(STDERR, "Export failed: " . $e->getMessage() . "\n");
  exit(1);
}
