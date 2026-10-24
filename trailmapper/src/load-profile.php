<?php

use TrailMapper\GetProfile;

require_once __DIR__ . '/GetProfile.php';

$conn = require __DIR__ . '/db.php';

try {
  $theGeom = new GetProfile($conn, $_GET, 'kt');
}
catch (InvalidArgumentException $e) {
  http_response_code(400);
  exit('Bad request');
}
$theGeom->getDirection();
$theGeom->assembleProfileSegments();
$theGeom->getRideStats();

echo "<svg id='svg' x='0px' y='0px' width='100%' height='100%'>\n";

echo("   <svg id='profile' x='7%' y='25' width='90%' height='500'
              viewBox='{$theGeom->XMin} {$theGeom->YMin} {$theGeom->width} {$theGeom->height}'
              preserveAspectRatio='none'>\n");
echo $theGeom->writeProfileSegments();
echo "</svg>\n";
echo $theGeom->writeProfileStats();
echo "</svg>\n";
