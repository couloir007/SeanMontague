<?php

/**
 * Class GetProfile
 *
 * @package GetProfile
 */
class GetProfile
{
    public $ln;

    public $width;

    //# of trail segments to process
    public $height;

    //Cumulative length of profile
    public $XMin;

    //Array of flip states
    public $YMin;

    public $theTable;

    public $junctions;

    public $db;

    private $conn;

    private $points;

    private $theCnt;

    private $flipArray;

    private $bboxArray;

    private $profileArray;

    private $cumElev;

    private $startElev;

    private $YMax;

    private $ymin;

    private $absElev;

    private $elevArray;

    public function __construct($conn, $points, $db)
    {
        $this->conn = $conn;
        $this->points = $points;

        $this->theCnt = count($points);

        $this->db = $db;

        if ($db == 'roundybr_trailmapper') {
            $this->theTable = 'kt_trails';
            $this->junctions = 'kt_junctions';
        } else {
            $this->theTable = 'trails';
            $this->junctions = 'junctions';
        }
    }

    public function getDirection()
    {
        // Variable for calculating distance
        $this->ln = 0;
        $flipArray1 = [];

        //Determine profile direction
        $this->flipArray = '';

        $thePrTrl = '';
        $thePrEndPts = [];

        $t = 0;
        $i = 0;
        $flip = 0;
        $flip2 = -1;
        $prevFlip = -1;

        foreach ($this->points as $idx => $ptId) {
            $query = "SELECT name, objectid,  ST_Length(ST_Transform(geom,26915)) As length FROM public.{$this->theTable} WHERE objectid='{$ptId}';";

            // Only one row is selected...I think.
            foreach ($this->conn->query($query) as $row) {
                //add up distance
                $this->ln = $this->ln + $row['length'];

                // For last segment. Not sure it is usefule yet.
                $flip3 = 0;

                $TheLastFlip = 0;

                /* Set first trail = to previous trail for comparison later */
                if ($t == 0) {
                    $thePrTrl = $row['objectid'];
                } else {
                    $flip = 0;

                    //Select endpoints of Trail
                    $objID = $row['objectid'];
                    $query_endpoints = ("SELECT objectid, id, start FROM public.{$this->junctions} WHERE objectid ~ ',$objID,'");


                    $x = 0;
                    $prev_touch = 0;
                    $last_array = [];
                    foreach ($this->conn->query($query_endpoints) as $row_endpoint) {
                        $objectIDs = trim($row_endpoint['objectid'], ',');


                        //Select objectid and place into an array
                        $trailArray = explode(",", $objectIDs);

                        /*
                            Iterate trough trails that touch each endpoint
                            Variable to determine when last trail start point has been determined.
                        */


                        $last_array[$row_endpoint['id']] = [$row_endpoint, $objID, 0];

                        foreach ($trailArray as $trl) {

                            //If the previous trail touches one of the two current endpoints, it's the junction


                            if ($thePrTrl == ltrim($trl)) {
                                $last_array[$row_endpoint['id']] = [$row_endpoint, $objID, 1];
                                $trailArray2 = explode(" ", $row_endpoint['start']);

                                // Identify which the two last points touch previous trail.
                                foreach ($trailArray2 as $trl2) {
                                    //Check to see if starts the previous trail...if so, it gets flipped.
                                    if ($trl2 == $thePrTrl) {

                                        if ($thePrTrl != $objID) {
                                            // If both have same starting point...this is key. Flip the previous trail.
                                            $flip = 1;
                                        } else {

                                        }
                                    } else {

                                    }

                                    //Check to see if it starts the current trail...if so, it is not flipped.
                                    if ($trl2 == $ptId) {

                                        // Check if the same trail is selected twice to ensure proper direction. Set the previous trail
                                        // to previous flip status and ensure the current is the opposite.

                                        $query_endpoints1 = ("SELECT objectid, id, start FROM public.{$this->junctions} WHERE objectid ~ ',$objID,'");
                                        $query_endpoints2 = ("SELECT objectid, id, start FROM public.{$this->junctions} WHERE objectid ~ ',$thePrTrl,'");
                                        $endPTS1 = $this->conn->query($query_endpoints1);
                                        $endPTS2 = $this->conn->query($query_endpoints2);
                                        $arraysAreEqual = ($endPTS1->fetchAll() == $endPTS2->fetchAll());

                                        if ($arraysAreEqual) {
                                            $flip = $prevFlip;
                                            $flip2 = (1 == $prevFlip) ? 0 : 1; // $flip2 is the opposite of $prevFlip
                                        } else {
                                            $flip2 = 0;
                                        }
                                    } else {
                                        $flip2 = 1;
                                    }

                                    if ($i == ($this->theCnt - 1)) {
//                                        $flip3 = 1;
                                    }
                                }


                            }
                        }
                        $x++;
                        $thePrEndPts[] = $row_endpoint;
                    }


                    if ($i == ($this->theCnt - 1)) {
                        foreach ($last_array as $idx => $last) {
                            if ($last[2] == 0) {

                                $start_array = explode(' ', $last[0]['start']);

                                $TheLastFlip = 1;

                                if (in_array($last[1], $start_array)) {
                                    $flip3 = 1;
                                } else {
                                    $flip3 = 0;
                                }
                            }
                        }

                    }

                    $thePrTrl = $row['objectid'];
                }

                if ($t != 0) {
                    $prevFlip = $flip;
                    $flipArray1[] = $flip;

                    if ($flip2 != -1) {
                        $prevFlip = $flip2;
                    }

                    if ($TheLastFlip == 1) {
                        $flipArray1[] = $flip3;
                    }
                }
                $t++;
            }

            $i++;
        }

        $this->flipArray = $flipArray1;
    }

    public function assembleProfileSegments()
    {
        $this->points = array_values($this->points);

        $bboxArray1 = [];
        $profileArray1 = [];

//        $scr = 6589;
//
//        $query = "
//            WITH tmp1 as (
//                SELECT 'Feature' as " . '"type"' . ",
//                ST_AsGeoJSON(ST_Transform(ST_SetSRID(t.geom," . $scr . "), 4326), 6)::json as " . '"geometry"' . ",
//                (
//                    SELECT json_strip_nulls(row_to_json(t))
//                    FROM (select objectid As id, name, touches,  ST_Length(ST_Transform(geom," . $scr . ")) as length) t
//                ) as " . '"properties"' . "
//                FROM public." . $table . " t
//            ),
//            tmp2 as (
//                SELECT 'FeatureCollection' as " . '"type"' . ",
//                array_to_json(array_agg(t)) as " . '"features"' . "
//                FROM tmp1 t
//            ) SELECT row_to_json(t)
//            FROM tmp2 t;
//            ";
//
//
//        foreach ($this->conn->query($query) as $trail) {
//            $trailArray[] = $trail;
//        }


        foreach ($this->points as $idx => $objID) {
            if ($idx < ($this->theCnt - 2)) {
                if ($objID == $this->points[$idx + 1]) {
                    if ($this->flipArray[$idx + 1] == 0) {
                        $this->flipArray[$idx] = 1;
                    } else {
                        $this->flipArray[$idx] = 0;
                    }
                }
            }

            if ($this->flipArray[$idx] == 0) {
                if ($this->db == 'roundybr_trailmapper') {
                    $table = ('public.kt_profiles');
                } else {
                    $table = ('public.profile');
                }
            } else {
                if ($this->db == 'roundybr_trailmapper') {
                    $table = ('public.kt_profiles2');
                } else {
                    $table = ('public.profile2');
                }
            }


            $bbox_sql = ("SELECT ST_XMax(geom),ST_XMin(geom),ST_YMax(geom),ST_YMin(geom),min_elev,max_elev,start_elev,cum_elev
            FROM $table
            WHERE objectid='$objID';");

            $bbox_sql2 = ("SELECT ST_XMax(geom),ST_XMin(geom),ST_YMax(geom),ST_YMin(geom),min_elev,max_elev,start_elev,cum_elev
            FROM $table
            WHERE objectid='$objID';");


            foreach ($this->conn->query($bbox_sql) as $bbox) {
                $bboxArray1[] = $bbox;
            }

            $profile_sql = ("SELECT ST_asSVG(ST_ChaikinSmoothing(geom, 5),0,3)
            FROM $table
            WHERE objectid= '$objID';");

            foreach ($this->conn->query($profile_sql) as $profile) {
                $profileArray1[] = $profile;
            }
        }

        $this->bboxArray = $bboxArray1;
        $this->profileArray = $profileArray1;
    }

    public function getRideStats() {
        // Get cumulative elevation gain, high and low points
        $this->XMin = 0;
        $this->YMin = 0;
        $this->YMax = 0;
        $this->width = 0;
        $this->startElev = 0;
        $this->cumElev = 0;

        foreach ($this->bboxArray as $u => $data) {
            if ($data['cum_elev'] > 0) {
                $this->cumElev = $this->cumElev + $data['cum_elev'];
            }
            if ($u == 0) {
                $this->YMax = $data['max_elev'];
                $this->YMin = $data['min_elev'];
                $this->startElev = $data['start_elev'];
            }
            if ($data[1] < $this->XMin) {
                $this->XMin = $data[1];
            }
            if ($data[4] < $this->YMin) {
                $this->YMin = $data['min_elev'];
            }
            if ($data[5] > $this->YMax) {
                $this->YMax = $data['max_elev'];
            }
            $this->width = $this->width + $data['st_xmax'];
        }


        $this->ymin = $this->YMin;

        $this->height = ($this->YMax - $this->YMin) + 10;
        $this->YMin = ($this->YMax * -1) - 5;
        $this->absElev = $this->YMax - $this->ymin;
    }

    public function writeProfileSegments() {
        $transX = 0;
        $paths = '';

        foreach ($this->points as $u => $lyr) {
            $paths .= "<path id='profile_{($u + 1)}' fill='none' stroke-width='0.095vw'
                stroke='red' transform='translate($transX,0)' d=\"{$this->profileArray[$u]['st_assvg']}\"/>" . "\n";
            $transX = $transX + $this->bboxArray[$u]['st_xmax'];
        }


        $y1 = ($this->YMax * -1);
        $y1a = $y1 - 5;
        $y2 = $y1 + ($this->YMax - $this->ymin);

        $paths .= "<line id='left' z='-$this->ymin' z2='$this->width' x1='0' y1='$y1a' x2='0' y2='$y2' stroke-width='2px' stroke='black'/>\n";
        $paths .= "<line x1='$this->width' y1='$y1a' x2='$this->width' y2='$y2' stroke-width='2px' stroke='black'/>\n";
        $paths .= "<line x1='0' y1='$y1a' x2='$this->width' y2='$y1a' stroke-width='0.15px' stroke='black'/>\n";
        $paths .= "<line x1='0' y1='$y2' x2='$this->width' y2='$y2' stroke-width='0.15px' stroke='black'/>\n";

        $strElev = round($this->ymin * 3.28083, -1);
        $elevMkr = round(((($this->YMax - $this->ymin) * -3.28083) / 3), -1);
        $paths .= "<g id='elev'>\n";
        $p = 0;
        $elevArray1 = [];
        for ($u = 1; $u < 4; $u++) {
            $elev = $strElev - ($elevMkr * $u);
            $elevationGraph = $elev / -3.28083;
            $elevArray1[$p] = $elev;
            $paths .= "<line name='elev' x1='0' y1='$elevationGraph' x2='$this->width' y2='$elevationGraph' stroke-dasharray=\"1% 0.5%\" fill='none' stroke-width='0.005%' stroke='blue'/>\n";
            //            3.28083
            //            $paths .= "<text id='elev$u' x='-95' y='{$elevationGraph}' text-anchor='end' dominant-baseline='mathematical' fill='black' font-weight='bold' font-family='Arial' font-size='14'>" . ($elevationGraph * -3.28083) . " ft</text>\n";
            $p++;
        }

        $paths .= "</g>\n";

        $this->elevArray = $elevArray1;

        return $paths;
    }


    public function writeProfileStats() {
        $theY = 500 - 150;
        $paths = '';
        foreach ($this->elevArray as $u => $elev) {
            $theNum = number_format($elev);
//            print $theY . '--<br />';
            $paths .= "<text id='elev$u' x='3.5%' y='{$theY}' text-anchor='start' dominant-baseline='mathematical' fill='black' font-weight='bold' font-family='Arial' font-size='14'>$theNum ft</text>\n";
            $theY = $theY - 150;
        }

        $lowestElev = number_format($this->ymin * 3.28083);

        $paths .= "<text x='95' y='490' text-anchor='end' dominant-baseline='mathematical' fill='black' font-weight='bold' font-family='Arial' font-size='14'>$lowestElev ft</text>\n";

        $theWidth9x = round($this->width / 1609.344, 2);
        $paths .= "<text id='totalDist' x='95' y='545' text-anchor='end' dominant-baseline='hanging' fill='black' font-weight='bold' font-family='Arial' font-size='14'>$theWidth9x mi</text>\n";

        $paths .= "<g id='info'>\n";
        $paths .= "<text id='info0' x='95' y='650' text-anchor='start' dominant-baseline='hanging' fill='black' font-weight='bold' font-family='Arial' font-size='14'>Surface Length:</text>\n";
        $paths .= "<text id='info1' x='95' y='675' text-anchor='start' dominant-baseline='hanging' fill='black' font-weight='bold' font-family='Arial' font-size='14'>Absolute Elevation Diff:</text>\n";
        $paths .= "<text id='info2' x='95' y='700' text-anchor='start' dominant-baseline='hanging' fill='black' font-weight='bold' font-family='Arial' font-size='14'>Cumulative Elevation Gain:</text>\n";
//        $paths .= "<text id='info3' x='95' y='725' text-anchor='start' dominant-baseline='hanging' fill='black' font-weight='bold' font-family='Arial' font-size='14'>Vertical Exaggeration:</text>\n";
        $paths .= "</g>\n";

        //        print_r($this->absElev );

        $theLength = number_format(($this->ln / 1609.344), 2) . ' mi';
        $absElev = number_format(($this->absElev * 3.28083), 0) . ' ft';
        $cumElev = number_format(($this->cumElev * 3.28083), 0) . ' ft';
        $paths .= "<g id='2info'>\n";
        $paths .= "<text id='info0a' x='300' y='650' text-anchor='start' dominant-baseline='hanging' fill='blue' font-family='Arial' font-size='14'>$theLength</text>\n";
        $paths .= "<text id='info1a' x='300' y='675' text-anchor='start' dominant-baseline='hanging' fill='blue' font-family='Arial' font-size='14'>$absElev</text>\n";
        $paths .= "<text id='info2a' x='300' y='700' text-anchor='start' dominant-baseline='hanging' fill='blue' font-family='Arial' font-size='14'>$cumElev</text>\n";
        //        $paths .= "<text id='info3a' x='300' y='725' text-anchor='start' dominant-baseline='hanging' fill='blue' font-family='Arial' font-size='14'>A</text>\n";
        $paths .= "</g>\n";

        return $paths;
    }


    public function getGPX()
    {
        $this->points = array_values($this->points);
        $trackArray = [];

        foreach ($this->points as $idx => $objID) {
            if ($idx < ($this->theCnt - 2)) {
                if ($objID == $this->points[$idx + 1]) {
                    if ($this->flipArray[$idx + 1] == 0) {
                        $this->flipArray[$idx] = 1;
                    } else {
                        $this->flipArray[$idx] = 0;
                    }
                }
            }

            if ($this->flipArray[$idx] == 0) {
                if ($this->db == 'roundybr_trailmapper') {
                    $tracks = ('public.kt_trax');
                }
            } else {
                if ($this->db == 'roundybr_trailmapper') {
                    $tracks = ('public.kt_trax2');
                }
            }

            $tracks_sql = ("SELECT ele, ST_AsGeoJSON(ST_Transform(geom,4326),6)
            FROM $tracks
            WHERE objectid= '$objID';");

            foreach ($this->conn->query($tracks_sql) as $track) {
                $trackArray[] = $track;
            }
        }

        return $trackArray;
    }
}