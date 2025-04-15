<?php
require_once("pg_connect.php");
require_once("GetProfile.php");

$host = 'us17.acugis-dns.com';
$db = 'roundybr_trailmapper';
$username = 'roundybr';
$password = 'Fi8w0t8I4x';

$username = 'trailmaster';
$password = '!MbiaSC5010!';
$conn = connectDBLook($host, $username, $db, $password);

$points = $_GET;

//print "<pre>";
//print_r($points);
//print "</pre>";

//$getPoints = [
//        'id0'=>'trl54',
//        'id1'=>'trl55',
//        'id2'=>'trl59',
//        'id3'=>'trl66',
//        'id4'=>'trl88',
//        'id5'=>'trl184',
//        'id6'=>'trl185',
//        'id7'=>'trl93',
//        'id8'=>'trl114',
//        'id9'=>'trl113',
//        'id10'=>'trl63',
//        'id11'=>'trl57',
//        'id12'=>'trl194',
//        'id13'=>'trl193',
//        'id14'=>'trl54',
//    ];
// /getPoints.php?id0=trl54&id1=trl55&id2=trl60&id3=trl58&id4=trl192&id5=trl190&id6=trl193&id7=trl54

$theGeom = new GetProfile($conn, $points, $db);
$theGeom->getDirection();
$theGeom->assembleProfileSegments();
$theGeom->getRideStats();

//echo "<svg id='svg' x='0px' y='0px' width='100%' height='100%' onload='onLoad();' onresize='onLoad();'>\n";
echo "<svg id='svg' x='0px' y='0px' width='100%' height='100%'>\n";
//echo("   <script type='text/ecmascript' xlink:href='../JavaScript/1profile.js'/>\n");
echo("   <svg id='profile' x='100' y='25' width='90%' height='500'
            viewBox='{$theGeom->XMin} {$theGeom->YMin} {$theGeom->width} {$theGeom->height}'
            preserveAspectRatio='none'>\n");
echo $theGeom->writeProfileSegments();
echo "</svg>\n";
echo $theGeom->writeProfileStats();
echo "</svg>\n";
?>