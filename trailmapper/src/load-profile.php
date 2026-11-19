<?php

require_once __DIR__ . '/planner.php';

$conn = require __DIR__ . '/db.php';

$ids = $_GET;
$debug = ($ids['debug'] ?? '') === '1';
unset($ids['debug']);

try {
  $svg = trailmapper_profile_svg($conn, $ids, 'kt', $debug);
}
catch (InvalidArgumentException $e) {
  http_response_code(400);
  exit('Bad request');
}

echo $svg;
