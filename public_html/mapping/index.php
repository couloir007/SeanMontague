<?php

require_once("php_files/pg_connect.php");

$port = '5432';

$host = 'us17.acugis-dns.com';
$db = 'roundybr_trailmapper';
$username = 'roundybr';
$password = 'Fi8w0t8I4x';

if ($db == 'roundybr_trailmapper') {
  $table = 'kt_trails';
  $output = 'GeoJSON/KingdomTrails.geojson';
} else {
  $table = 'trails';
  $output = 'GeoJSON/FrontRange.geojson';
}

$dsn = "pgsql:host=$host;port=$port;user=$username;dbname=$db;password=$password";

try {
  // create a PostgreSQL database connection
  //    $conn = new PDO($dsn);
  $conn = connectDBLook($host, $username, $db, $password, $port);
  // display a message if connected to the PostgreSQL successfully
  if ($conn) {
    $scr = 3719;
    $scr = 3720;
    $scr = 4326;
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

    foreach ($conn->query($query) as $row) {
      echo "<pre>";
      print_r($query);
      print_r($row['row_to_json']);
      echo "</pre>";

      $fp = fopen($output, 'w');
      fwrite($fp, $row['row_to_json']);
      fclose($fp);
    }
  }
}
catch (PDOException $e) {
  // report error message
  echo $host . '<br />';
  echo $e->getMessage();
}
//}
