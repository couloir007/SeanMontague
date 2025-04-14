<?php

require_once("pg_connect.php");
require_once("GetProfile.php");

$port = '5432';
$host = 'us17.acugis-dns.com';
$db = 'roundybr_trailmapper';
$username = 'roundybr';
$password = 'Fi8w0t8I4x';

$conn = connectDBLook($host, $username, $db, $password, $port);

$points = $_GET;

$theGeom = new GetProfile($conn, $points, $db);
$theGeom->getDirection();
$trackArray = $theGeom->getGPX();
$name = 'mtb_ride';

ob_end_clean();
header_remove();

header("Content-type: text/xml");
header('Content-Disposition: attachment; filename="' . $name . '.gpx"');

echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";

echo '<gpx creator="The Mountain Scene" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd" version="1.1" xmlns="http://www.topografix.com/GPX/1/1">';
echo '<metadata>';
echo '<name>Mountain Scene Ride</name>';
echo '<author>';
echo '<name>Sean Montague</name>';
echo '</author>';
echo '<copyright author="OpenStreetMap contributors">';
echo '<year>2023</year>';
echo '<license>https://www.openstreetmap.org/copyright</license>';
echo '</copyright>';
echo '</metadata>';
echo '<trk>';
echo '<name>Mountain Scene Ride Route</name>';
echo '<type>mountain_biking</type>';
echo '<trkseg>';

foreach ($trackArray as $point) {
    $XY = explode(',', $point[1]);
    $decode = json_decode($point['st_asgeojson']);

    echo '<trkpt lat="' . $decode->coordinates[1] . '" lon="' . $decode->coordinates[0] . '">';
    echo '<ele>' . $point['ele'] . '</ele>';
    echo '</trkpt>';
}

echo '</trkseg>';
echo '</trk>';
echo '</gpx>';

exit();
