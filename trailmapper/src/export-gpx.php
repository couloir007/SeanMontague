<?php

require_once __DIR__ . '/planner.php';

$conn = require __DIR__ . '/db.php';

try {
  $gpx = trailmapper_gpx($conn, $_GET, 'kt');
}
catch (InvalidArgumentException $e) {
  http_response_code(400);
  exit('Bad request');
}

$name = 'mtb_ride';

ob_end_clean();
header_remove();

header("Content-type: text/xml");
header('Content-Disposition: attachment; filename="' . $name . '.gpx"');

echo $gpx;

exit();
