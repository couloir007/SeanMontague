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
//  echo "<pre>";
//  print_r($points);
//  echo "</pre>";


  $theGeom = new GetProfile($conn, $points, $db);
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
