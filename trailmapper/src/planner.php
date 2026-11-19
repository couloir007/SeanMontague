<?php

/**
 * Shared planner functions: the network export, profile SVG and GPX output.
 *
 * Used by bin/export-geojson.php, the legacy src/ entry points and the Drupal
 * trail_mapper_planner service, so each output has one implementation.
 */

use TrailMapper\GetProfile;

require_once __DIR__ . '/GetProfile.php';

/**
 * Builds the trail network GeoJSON for an area from PostGIS.
 *
 * @throws \InvalidArgumentException
 *   If the area is unknown.
 */
function trailmapper_network_geojson(PDO $conn, string $area = 'kt'): string {
  if (!isset(GetProfile::AREAS[$area])) {
    throw new InvalidArgumentException('Unknown area');
  }
  // Table name and SRID come from the fixed area map, never from input.
  $table = GetProfile::AREAS[$area]['trails'];
  $scr = (int) GetProfile::AREAS[$area]['srid'];

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

  foreach ($conn->query($query) as $row) {
    return $row['row_to_json'];
  }
  throw new RuntimeException('Network query returned no rows');
}

/**
 * Builds the elevation profile SVG for ordered segment ids.
 *
 * With $debug, the flip array and gap indexes are appended as an HTML comment.
 *
 * @throws \InvalidArgumentException
 *   If the area is unknown or an id is malformed.
 */
function trailmapper_profile_svg(PDO $conn, array $ids, string $area = 'kt', bool $debug = FALSE): string {
  $theGeom = new GetProfile($conn, $ids, $area);
  $theGeom->getDirection();
  $theGeom->assembleProfileSegments();
  $theGeom->getRideStats();

  $out = "<svg id='svg' x='0px' y='0px' width='100%' height='100%'>\n";
  $out .= "   <svg id='profile' x='7%' y='25' width='90%' height='500'
              viewBox='{$theGeom->XMin} {$theGeom->YMin} {$theGeom->width} {$theGeom->height}'
              preserveAspectRatio='none'>\n";
  $out .= $theGeom->writeProfileSegments();
  $out .= "</svg>\n";
  $out .= $theGeom->writeProfileStats();
  $out .= "</svg>\n";
  if ($debug) {
    $out .= '<!-- flips: ' . json_encode($theGeom->getFlipArray()) . ' gaps: ' . json_encode($theGeom->getGaps()) . " -->\n";
  }
  return $out;
}

/**
 * Builds the GPX document for ordered segment ids.
 *
 * @throws \InvalidArgumentException
 *   If the area is unknown or an id is malformed.
 */
function trailmapper_gpx(PDO $conn, array $ids, string $area = 'kt'): string {
  $theGeom = new GetProfile($conn, $ids, $area);
  $theGeom->getDirection();
  $trackArray = $theGeom->getGPX();

  $out = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
  $out .= '<gpx creator="The Mountain Scene" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd" version="1.1" xmlns="http://www.topografix.com/GPX/1/1">';
  $out .= '<metadata>';
  $out .= '<name>Mountain Scene Ride</name>';
  $out .= '<author>';
  $out .= '<name>Sean Montague</name>';
  $out .= '</author>';
  $out .= '<copyright author="OpenStreetMap contributors">';
  $out .= '<year>2023</year>';
  $out .= '<license>https://www.openstreetmap.org/copyright</license>';
  $out .= '</copyright>';
  $out .= '</metadata>';
  $out .= '<trk>';
  $out .= '<name>Mountain Scene Ride Route</name>';
  $out .= '<type>mountain_biking</type>';
  $out .= '<trkseg>';

  foreach ($trackArray as $point) {
    $decode = json_decode($point['st_asgeojson']);

    $out .= '<trkpt lat="' . $decode->coordinates[1] . '" lon="' . $decode->coordinates[0] . '">';
    $out .= '<ele>' . $point['ele'] . '</ele>';
    $out .= '</trkpt>';
  }

  $out .= '</trkseg>';
  $out .= '</trk>';
  $out .= '</gpx>';
  return $out;
}
