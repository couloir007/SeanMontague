<?php


/**
 * Class GetSVG
 *
 * @package GetSVG
 */

class GetSVG
{
    private $trailArray;
    public $XMin;
    public $YMin;
    public $YMax;
    public $width;
    public $height;
    public $cumElev;
    public $absElev;
    private $ln;

    private $conn;

    public function __construct($conn, $trailArray, $flipArray) {
        $this->conn = $conn;

        $this->trailArray = $trailArray;
        $i = 0;
        $this->conn = connectDBLook();
        foreach ($this->trailArray as $trl) {
            if ($flipArray[$i] == 0) {
                $table = ('profile');
            } else {
                $table = ('profile2');
            }
            $sql = ("SELECT xmax(the_geom),xmin(the_geom),ymax(the_geom),ymin(the_geom),low_point,high_point,start_elev,cum_elev_i
                    FROM $table
                    WHERE objectid= '$trl';");
            $bbox = "bbox$i";
            $this->$bbox = pg_exec($this->conn, $sql);

            $sql2 = ("SELECT AsSVG(the_geom,0,3)
                    FROM $table
                    WHERE objectid= '$trl';");
            $prof = "prof$i";
            $this->$prof = pg_exec($this->conn, $sql2);
            $i = $i + 1;
        }
        getSVG::getParams();
    }

    function getParams()
    {
        $this->XMin = 0;
        $this->YMin = 0;
        $this->YMax = 0;
        $this->width = 0;
        $this->height = 0;
        $this->cumElev = 0;
        $this->startElev = 0;
        for ($u = 0; $u < count($this->trailArray); $u++) {
            $trl = $this->trailArray[$u];
            $bbox = "bbox$u";
            if ($this->$bbox) {
                $bbox = $this->$bbox;
                $rows = pg_numrows($bbox);
                for ($i = 0; $i < $rows; $i++) {
                    $data = pg_fetch_row($bbox, $i);
                    if ($data[7] > 0) {
                        $this->cumElev = $this->cumElev + $data[7];
                    }
                    if ($u == 0) {
                        $this->YMax = $data[5];
                        $this->YMin = $data[4];
                        $this->startElev = $data[6];
                    }
                    if ($data[1] < $this->XMin) {
                        $this->XMin = $data[1];
                    }
                    if ($data[4] < $this->YMin) {
                        $this->YMin = $data[4];
                    }
                    if ($data[5] > $this->YMax) {
                        $this->YMax = $data[5];
                    }
                    $this->width = $this->width + $data[0];
                }
            } else {
                echo 'Nope2a' . "\n";
            }
        }
        $this->height = ($this->YMax - $this->YMin) + 10;
        $this->theYmin = ($this->YMax * -1) - 6;
        $this->XMin = $this->XMin - 1;
        $this->width = $this->width + 2;
        $this->absElev = $this->YMax - $this->startElev;
    }

    function getPaths($ln)
    {
        $this->ln = $ln;
        $transX = 0;
        $p = 0;
        for ($u = 0; $u < count($this->trailArray); $u++) {
            $trl = $this->trailArray[$u];

            $prof = "prof$u";
            if ($this->$prof) {
                $bbox = "bbox$u";
                $bbox = $this->$bbox;
                $rows2 = pg_numrows($bbox);

                $prof = $this->$prof;
                $rows = pg_numrows($prof);

                for ($i = 0; $i < $rows; $i++) {
                    $data = pg_fetch_row($prof, $i);
                    $data2 = pg_fetch_row($bbox, $i);
                    echo "       <path id='$p' fill='none' stroke-width='0.015%' stroke='red' transform='translate($transX,0)' d=\"{$data[0]}\"/>" . "\n";
                    $transX = $transX + $data2[0];
                }
            }
            $p = $p + 1;
        }
        getSVG::getGraph();
    }

    public $right;

    function buildOutline()
    {
        //Highest Elevation
        $y = ($this->YMax * -1);
        $top = $y - 5;
        $bottom = $y + ($this->YMax - $this->YMin);
        $this->right = ($this->width - 2);
        //Left Side
        echo "       <line class='ln2' id='left' z='-$this->YMin' z2='$this->width' x1='0' y1='$top' x2='0' y2='$bottom' stroke='black'/>\n";
        //Right Side
        echo "       <line class='ln2' x1='$this->right' y1='$top' x2='$this->right' y2='$bottom' stroke='black'/>\n";
        //Top
        echo "       <line class='ln' x1='0' y1='$top' x2='$this->right' y2='$top' stroke='black'/>\n";
        //Trail Summit
        echo "       <line id='summitLine' x1='0' y1='$y' x2='$this->right' y2='$y' stroke-dasharray='1% 0.5%' fill='none' stroke='green'/>\n";
        $theSummit = number_format($this->YMax * 3.28083);
        $textX = ($this->width / 2);
        echo "       <text class='ln' id='summitText' x='$textX' y='$y' text-anchor='middle' dominant-baseline='hanging' fill='green' font-weight='bold' font-family='Arial'>$theSummit ft</text>\n";
        //Bottom
        echo "       <line class='ln' x1='0' y1='$bottom' x2='$this->right' y2='$bottom' stroke='black'/>\n";
    }

    function getGraph()
    {
        getSVG::buildOutline();

        $strElev = round($this->YMin * 3.28083, -1);
        $elevMkr = round(((($this->YMax - $this->YMin) * -3.28083) / 3), -1);
        echo "       <g id='elev'>\n";
        $p = 0;

        //$this->right does = width from here down!!!!
        for ($u = 1; $u < 4; $u++) {
            $elev = $strElev - ($elevMkr * $u);
            $elev2 = $elev / -3.28083;
            $elevArray1[$p] = $elev;
            echo "           <line name='elev' x1='0' y1='$elev2' x2='$this->right' y2='$elev2' stroke-dasharray='1% 0.5%' fill='none' stroke='blue'/>\n";
            $p++;
        }
        echo "       </g>\n";
        echo "   </svg>\n";
        for ($u = 0; $u < $p; $u++) {
            $theNum = number_format($elevArray1[$u]);
            echo "   <text id='elev$u' x='95' y='0' text-anchor='end' dominant-baseline='mathematical' fill='black' font-weight='bold' font-family='Arial' font-size='14'>$theNum ft</text>\n";
        }
        $theNum = number_format(round($this->YMin * 3.28083));
        echo "   <text id='baseElev' x='95' y='0' text-anchor='end' dominant-baseline='ideographic' fill='black' font-weight='bold' font-family='Arial' font-size='14'>$theNum ft</text>\n";
        $theWidth9x = round($this->right / 1609.344, 2);
        echo "   <text id='totalDist' x='0' y='0' text-anchor='end' dominant-baseline='hanging' fill='black' font-weight='bold' font-family='Arial' font-size='14'>$theWidth9x mi</text>\n";

        echo "   <g id='mrk'>\n";
        if (number_format(($this->ln / 1609.344), 2) <= 15) {
            $mileMkr = floor($this->right / 1609.344);
            $theDiv = 1609.344;
        } else {
            $mileMkr = floor($this->right / 8046.72);
            $theDiv = 8046.72;
        }
        for ($u = 1; $u <= $mileMkr; $u++) {
            $theWidth = ($theDiv * $u);
            echo "      <line id='mrk$u' z='$theWidth' x1='0' y1='0' x2='0' y2='0' stroke-width='0.15%' stroke='black'/>\n";
        }
        echo "   </g>\n";

        echo "   <g id='mrkT'>\n";
        if (number_format(($this->ln / 1609.344), 2) <= 15) {
            $mileMkr = floor($this->right / 1609.344);
            $theDiv = 1609.344;
            $theMult = 1;
        } else {
            $mileMkr = floor($this->right / 8046.72);
            $theDiv = 8046.72;
            $theMult = 5;
        }
        for ($u = 1; $u <= $mileMkr; $u++) {
            $theWidth = ($theDiv * $u);
            $mrkrText = $u * $theMult;
            echo "      <text id='mrkT$u' z='$theWidth' x='0' y='0' text-anchor='middle' dominant-baseline='hanging' fill='black' font-weight='bold' font-family='Arial' font-size='14'>$mrkrText mi</text>\n";
        }
        echo "   </g>\n";
        getSVG::buildText();
    }

    function buildText()
    {
        for ($u = 0; $u < 2; $u++) {
            if ($u == 0) {
                $id = 'info';
                $x = 95;
                $a = '';
                $fill = 'black';
            } else {
                $id = '2info';
                $x = 0;
                $a = 'a';
                $fill = 'blue';
            }
            echo "   <g id='$id'>\n";
            for ($d = 0; $d < 4; $d++) {
                if ($u == 0) {
                    if ($d == 0) {
                        $text = 'Surface Length:';
                    } else if ($d == 1) {
                        $text = 'Absolute Elevation Gain:';
                    } else if ($d == 2) {
                        $text = 'Cumulative Elevation Gain:';
                    } else if ($d == 3) {
                        $text = 'Vertical Exaggeration:';
                    }
                } else {
                    if ($d == 0) {
                        $text = number_format(($this->ln / 1609.344), 2) . ' mi';;
                    } else if ($d == 1) {
                        $text = number_format(($this->absElev * 3.28083), 0) . ' ft';
                    } else if ($d == 2) {
                        $text = number_format(($this->cumElev * 3.28083), 0) . ' ft';
                    } else if ($d == 3) {
                        $text = 'A';
                    }
                }
                echo "      <text id='info$d$a' x='$x' y='0' text-anchor='start' dominant-baseline='hanging' fill='$fill' font-weight='bold' font-family='Arial' font-size='14'>$text</text>\n";
            }
            echo "   </g>\n";
        }
    }
}