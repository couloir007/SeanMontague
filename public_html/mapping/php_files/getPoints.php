<?php
ob_start();
//include('gzip_encode.php');
require_once 'dbconfig.php';
?>
<?php
print('<?xml version="1.0" encoding="iso-8859-1"?>')."\n";
?>
<?php

$host = '192.168.144.1';
//$db = 'kokopelli';
//$db = 'themountainscene_com';
$db = 'PostGIS';

$dsn = "pgsql:host=$host;port=5432;user=$username;dbname=$db;password=$password";

//    $conn=pg_connect('host=pgsql80.hub.org port=5432 dbname=656_themountainscene_com user= 	656_couloir007 password=reggie01') or die ('I cannot connect to the database because: ' . pg_error());
//    $conn=pg_connect('host=localhost port=5432 dbname=themountainscene_com user=couloir0 password=first101') or die ('I cannot connect to the database because: ' . pg_error());

$conn = new PDO($dsn);

$theCnt = $_GET['theCnt'];

//Variable for calculating distance
$ln = 0;

$t = 0;
$flip4 = '';
$flipArray = [];


//echo $theCnt . '<br />';
/* var thePrTrl */
//        /getPoints.php?theCnt=8&id0=trl54&id1=trl55&id2=trl60&id3=trl58&id4=trl192&id5=trl190&id6=trl193&id7=trl54
$thePrTrl = '';
if ($conn) {
    for ($i = 0; $i < $theCnt; $i++) {
        //Select objectid from php list
        $id = 'id' . $i;
        $ptId = $_GET[$id];
        //Query trail
        $query = "SELECT name, objectid, ST_Length(geom) As length, ptid FROM public.trails WHERE objectid='$ptId';";

        foreach ($conn->query($query) as $row) {
            //add up distance
            $ln = $ln + $row['length'];
            $flip3 = 0;

            /* Set first trail = to previous trail for comparison later */
            if ($t == 0) {
                $thePrTrl = $row['objectid'];
            } else {
                $flip = 0;

                //Select endpoints of Trail
                $objID = $row['objectid'];
                $query_endpoints = ("SELECT objectid, id, start FROM public.junctions WHERE objectid ~ ',$objID,'");

                $x = 0;
                foreach ($conn->query($query_endpoints) as $row_endpoint) {
                    //Select objectid and place into an array
                    $trailArray = explode(",", $row_endpoint['objectid']);

                    /*
                        Iterate trough trails that touch each endpoint
                        Variable to determine when last trail start point has been determined.
                    */
                    $cont = 1;
                    foreach ($trailArray as $trl) {
                        //If the previous trail touches one of the two current endpoints, it's the junction
                        if ($thePrTrl == ltrim($trl)) {
                            $trailArray2 = explode(" ", $row_endpoint['start']);
//                            $theJunc = $row_endpoint['objectid'];
                            foreach ($trailArray2 as $trl2) {
                                //Check to see if it starts the previous trail...if so, it gets flipped.
                                if ($trl2 == $thePrTrl) {
                                    $flip = 1;
                                } else {
                                }

                                //Check to see if it starts the current trail...if so, it is not flipped.
                                if ($trl2 == $ptId) {
                                    $flip2 = 0;
                                    $cont = 0;
                                } elseif ($cont == 1) {
                                    $flip2 = 1;
                                }

                                if ($i == ($theCnt - 1)) {
                                    $flip3 = 1;
                                }
                            }

                            if ($i == ($theCnt - 1)) {
                                // Not sure what this is.
                            }
                        }
                    }
                    $x++;
                }

                $thePrTrl = $row['objectid'];
            }

            if ($t != 0) {
//                $flip4 = $flip4 . $flip . ",";
                $flipArray[] = $flip;
                if ($flip3 == 1) {
                    $flipArray[] = $flip2;
//                    $flip4 = $flip4 . $flip2;
                }
            }
            $t++;
        }
    }
}




//echo number_format(($ln / 1609.344), 3) . ' miles <br />';


$layersArray = [];
for ($i = 0; $i < $theCnt; $i++) {
    $id = 'id' . $i;
    $ptId = $_GET[$id];
    $layersArray[] = $ptId;
}


$bboxArray = [];
$profileArray = [];
foreach ($layersArray as $idx => $objID) {
    if ($flipArray[$idx] == 0) {
        $table = ('public.profile');
    } else {
        $table = ('public.profile2');
    }

    $bbox_sql = ("SELECT ST_XMax(geom),ST_XMin(geom),ST_YMax(geom),ST_YMin(geom),low_point_,high_point,start_elev,cum_elev_incr
            FROM $table
            WHERE objectid='$objID';");

    foreach ($conn->query($bbox_sql) as $bbox) {
        $bboxArray[] = $bbox;
    }

    $profile_sql = ("SELECT ST_asSVG(geom,0,3)
            FROM $table
            WHERE objectid= '$objID';");

    foreach ($conn->query($profile_sql) as $profile) {
        $profileArray[] = $profile;
    }
}

//echo "<pre>";
//print_r($bboxArray);
//'<br />';
//print_r($profileArray);
//echo "</pre>";

$XMin = 0;
$YMin = 0;
$YMax = 0;
$width = 0;
$cumElev = 0;
$startElev = 0;

// Get cumulative elevation gain, high and low points
foreach ($bboxArray as $u => $data) {
    if ($data['cum_elev_incr'] > 0) {
        $cumElev = $cumElev + $data['cum_elev_incr'];
    }
    if ($u == 0) {
        $YMax = $data['high_point'];
        $YMin = $data['low_point_'];
        $startElev = $data['start_elev'];
    }
    if ($data[1] < $XMin) {
        $XMin = $data[1];
    }
    if ($data[4] < $YMin) {
        $YMin = $data['low_point_'];
    }
    if ($data[5] > $YMax) {
        $YMax = $data['high_point'];
    }
    $width = $width + $data['st_xmax'];
}




$height = ($YMax - $YMin) + 10;
$theYmin = ($YMax * -1) - 5;
$absElev = $YMax - $startElev;

echo "<pre>";
print_r($cumElev);
echo '<br />';
print_r($width);
echo '<br />';
print_r($height);
echo '<br />';
print_r($theYmin);
echo '<br />';
print_r($absElev);
echo "</pre>";

//echo '<pre>';
print ("<svg id='svg' x='0px' y='0px' width='100%' height='100%'>\n");
//print ("    <script type='text/ecmascript' xlink:href='1profile.js'/>\n");
print ("        <svg id='profile' x='100' y='25' width='90%' height='500' viewBox='$XMin $theYmin $width $height' preserveAspectRatio='none'>\n");

$transX = 0;
foreach ($layersArray as $u => $lyr) {
    $profile = $profileArray[$u];

    $data2 = $bboxArray[$u];

    print "<path id='profile_{($u + 1)}' fill='none' stroke-width='0.045%' stroke='red' transform='translate($transX,0)' d=\"{$profile['st_assvg']}\"/>" . "\n";
    $transX = $transX + $data2['st_xmax'];
}


$x2 = $XMin;
$y1 = ($YMax * -1);
$y1a = $y1 - 5;
$y2 = $y1 + ($YMax - $YMin);

print "       <line id='left' z='-$YMin' z2='$width' x1='0' y1='$y1a' x2='0' y2='$y2' stroke-width='0.035%' stroke='black'/>\n";
print "       <line x1='$width' y1='$y1a' x2='$width' y2='$y2' stroke-width='0.035%' stroke='black'/>\n";
print "       <line x1='0' y1='$y1a' x2='$width' y2='$y1a' stroke-width='0.035%' stroke='black'/>\n";
print "       <line x1='0' y1='$y2' x2='$width' y2='$y2' stroke-width='0.035%' stroke='black'/>\n";

$strElev = round($YMin*3.28083,-1);
$strElev2 = round($YMin*3.28083);
$elevMkr = round(((($YMax-$YMin)*-3.28083)/3) ,-1);
print "       <g id='elev'>\n";
$p=0;
$elevArray1 = [];
for($u = 1; $u < 4; $u++){
    $elev = $strElev - ($elevMkr * $u);
    $elevationGraph = $elev / -3.28083;
    $elevArray1[$p] = $elev;
    print "           <line name='elev' x1='0' y1='$elevationGraph' x2='$width' y2='$elevationGraph' stroke-dasharray=\"1% 0.5%\" fill='none' stroke-width='0.025%' stroke='blue'/>\n";
//    print "   <text x='95' y='$elev' text-anchor='end' fill='black' font-weight='bold' font-family='Arial' font-size='14'>$elev2 ft</text>\n";

    $p++;
}
print "       </g>\n";
print "   </svg>\n";
$theY = 480 - 150;
$theX = 95;
foreach ($elevArray1 as $elev) {
    $theNum = number_format($elev);
    print "   <text id='elev$u' x='95' y='{$theY}' text-anchor='end' dominant-baseline='mathematical' fill='black' font-weight='bold' font-family='Arial' font-size='14'>$theNum ft</text>\n";
    $theY = $theY - 150;
}

$startElev = number_format(1848.4237 * 3.28083);
print "   <text x='95' y='490' text-anchor='end' dominant-baseline='mathematical' fill='black' font-weight='bold' font-family='Arial' font-size='14'>$startElev ft</text>\n";


//$theNum = number_format($strElev2);
//print "   <text id='baseElev' x='0' y='535' text-anchor='end' dominant-baseline='ideographic' fill='black' font-weight='bold' font-family='Arial' font-size='14'>$theNum ft</text>\n";
$theWidth9x = round($width/1609.344,2);
print "   <text id='totalDist' x='95' y='545' text-anchor='end' dominant-baseline='hanging' fill='black' font-weight='bold' font-family='Arial' font-size='14'>$theWidth9x mi</text>\n";

//print "   <g id='mrk'>\n";
//$mileMkr=floor($width/1609.344);
//for($u=1; $u<=$mileMkr; $u++){
//    $theWidth=(1609.344*$u);
//    print "      <line id='mrk$u' z='$theWidth' x1='0' y1='0' x2='0' y2='0' stroke-width='0.15%' stroke='black'/>\n";
//}
//print "   </g>\n";

//print "   <g id='mrkT'>\n";
//$mileMkr=floor($width/1609.344);
//for($u=1; $u<=$mileMkr; $u++){
//    $theWidth=(1609.344*$u);
//    print "      <text id='mrkT$u' z='$theWidth' x='0' y='0' text-anchor='middle' dominant-baseline='hanging' fill='black' font-weight='bold' font-family='Arial' font-size='14'>$u mi</text>\n";
//}
//print "   </g>\n";

print "<g id='info'>\n";
print "<text id='info0' x='95' y='650' text-anchor='start' dominant-baseline='hanging' fill='black' font-weight='bold' font-family='Arial' font-size='14'>Surface Length:</text>\n";
print "<text id='info1' x='95' y='675' text-anchor='start' dominant-baseline='hanging' fill='black' font-weight='bold' font-family='Arial' font-size='14'>Absolute Elevation Gain:</text>\n";
print "<text id='info2' x='95' y='700' text-anchor='start' dominant-baseline='hanging' fill='black' font-weight='bold' font-family='Arial' font-size='14'>Cumulative Elevation Gain:</text>\n";
print "<text id='info3' x='95' y='725' text-anchor='start' dominant-baseline='hanging' fill='black' font-weight='bold' font-family='Arial' font-size='14'>Vertical Exaggeration:</text>\n";
print "</g>\n";

$theLength = number_format(($ln/1609.344),2).' mi';
$absElev=number_format(($absElev*3.28083),0).' ft';
$cumElev=number_format(($cumElev*3.28083),0).' ft';
print "   <g id='2info'>\n";
print "      <text id='info0a' x='300' y='650' text-anchor='start' dominant-baseline='hanging' fill='blue' font-family='Arial' font-size='14'>$theLength</text>\n";
print "      <text id='info1a' x='300' y='675' text-anchor='start' dominant-baseline='hanging' fill='blue' font-family='Arial' font-size='14'>$absElev</text>\n";
print "      <text id='info2a' x='300' y='700' text-anchor='start' dominant-baseline='hanging' fill='blue' font-family='Arial' font-size='14'>$cumElev</text>\n";
print "      <text id='info3a' x='300' y='725' text-anchor='start' dominant-baseline='hanging' fill='blue' font-family='Arial' font-size='14'>A</text>\n";
print "   </g>\n";

print "</svg>\n";
//echo '</pre>';
?>