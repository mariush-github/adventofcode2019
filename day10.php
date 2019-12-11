<?php

class Points {
    public $list;
    private $hash;
    private $debug;
    public function __construct($debug=false){
        $this->reset();
        $this->debug= $debug;
    }
    public function reset() {
        $this->list = array();
        $this->hash = array();        
    }
    public function add($x,$y,$step_x,$step_y) {
        $counter = count($this->list);
        $hashId = $y * 0xFFFF + $x;
        if (isset($this->hash[$hashId])==true) return;
        $this->list[$counter] = array($x,$y,$step_x,$step_y,FALSE,FALSE);
        $this->hash[$hashId ] = $counter;
        if ($this->debug==true) echo "add $x,$y,$step_x,$step_y \n";
    }
    public function get($x,$y) {
        $hashId = $y * 0xFFFF + $x;
        if (isset($this->hash[$hashId])==false) return FALSE;
        $id = $this->hash[$hashId];
        if ($this->list[$id][5]==TRUE) return FALSE; // deleted
        return $this->list[$id];
    }
    public function delete($x,$y) {
        $hashId = $y * 0xFFFF + $x;
        if (isset($this->hash[$hashId])==false) return FALSE;
        $id = $this->hash[$hashId];
        $this->list[$id][5]=TRUE;       
    }
    public function get_by_index($id) {
        return $this->list[$id];
    }
    public function total() {
        return count($this->list);
    }
    public function total_visible() {
        $counter = 0;
        foreach ($this->list as $point) {
            if ($point[4]==FALSE) $counter++;
        }
        return $counter;
    }
    public function dump() {
        echo json_encode($this->list);
        echo "\n".json_encode($this->hash);
    }
    public function set_hidden($x,$y,$value) {
        $hashId = $y * 0xFFFF + $x;
        if (isset($this->hash[$hashId])==false) return ;
        $id = $this->hash[$hashId];
        $this->list[$id][4]=$value;
    }

    private function helper_cdiv($a,$b) {
      if ($b == 0) return $a;  
        return $this->helper_cdiv($b, $a % $b);  
    }     
}

class AsteroidMap {
    public $width;
    public $height;
    public $map;
    public $points;
    private $pointsindex;
    private $debug;

    public function __construct($width,$height,$debug=false) {
        $this->map = array();
        $this->points = new Points($debug);
        $this->width = $width;
        $this->height = $height;
        for ($i=0;$i<$width*$height;$i++) {
            $this->map[$i] = 0;
        }
        $this->debug=$debug;
    }
    public function setPoint($x,$y,$value) {
        $this->map[$y*$this->width + $x] = $value;
    }
    public function getPoint($x,$y) {
        $id = $y*$this->width + $x;
        if (isset($this->map[$id])==true) return $this->map[$id];
        return 0;
    }

    private function helper_cdiv($a,$b) {
        if ($b == 0) return $a;  
          return $this->helper_cdiv($b, $a % $b);  
    }

    public function scan_from_point($x,$y) {
        $this->points->reset();
        for ($j=0;$j<$this->height;$j++) {
            for ($i=0;$i<$this->width;$i++) {
                if (($this->getPoint($i,$j)==1) && (!($i==$x  && $j==$y))) $this->points->add($i,$j,$i-$x,$j-$y);
            }
        }
        $totalpoints = $this->points->total();
        if ($this->debug==true) echo "Total points: $totalpoints\n";
        for ($i=0;$i<$totalpoints;$i++) {
            $point = $this->points->get_by_index($i);
            $dist_x = $point[0]-$x;
            $dist_y = $point[1]-$y;
            if ($this->debug==true) echo "($x,$y):($point[0],$point[1]) [$dist_x:$dist_y]: ";
            $sx = 0;
            $sy = 0;
            if ($point[2]==0) { $sy = ($dist_y<0) ? -1 : 1; $sx = 0; }
            if ($point[3]==0) { $sx = ($dist_x<0) ? -1 : 1; $sy = 0; }
            if ($point[2]==$point[3]) { $sx = ($dist_x<0) ? -1 : 1; $sy = ($dist_y<0) ? -1 : 1; }

            $divisor = $this->helper_cdiv(abs($dist_x),abs($dist_y));
            if ($divisor>1) { $sx = $dist_x / $divisor; $sy = $dist_y / $divisor;}
            if ($this->debug==true) echo "dv=$divisor sx=$sx sy=$sy ";
            $is_blocked = false;
            if ($sx==0 && $sy==0) {
            } else {
                $px = $x+$sx;
                $py = $y+$sy;
                $continue=true;
                if ($px==$point[0] && $py==$point[1]) $continue=false;
                while ($continue==true) {
                    if ($this->debug==true) echo "($px,$py";
                    if ($this->points->get($px,$py)!==FALSE) { if ($this->debug==true) echo ' YES'; $is_blocked=true; }
                    $px += $sx;
                    $py += $sy;
                    if ($px==$point[0] && $py==$point[1]) $continue=false;
                    if ($this->debug==true) echo ") ";
                }
            }
            if ($is_blocked==true) $this->points->set_hidden($point[0],$point[1],true);

            if ($this->debug==true) echo "\n";
            }
        $vispoints = $this->points->total_visible();
        if ($this->debug==true) echo "Visible points: $vispoints\n";
        return $vispoints;
    }
}
$map = array();
$blocked = array();

$width = 0;
$height = 0;

$filename = __DIR__ . '/inputs/10.txt';

$maplines = explode(chr(0x0A),file_get_contents($filename));
$width = strlen($maplines[0]);
$height = count($maplines);
$map = new AsteroidMap($width,$height);
for ($y=0;$y<$height;$y++) {
    for ($x=0;$x<$width;$x++) {
        $map->setPoint($x,$y,(substr($maplines[$y],$x,1)=='#') ? 1 : 0);
    }
}
echo "Loaded a map with size $width x $height.\n";


$max = 0;
$coords = 0;
for ($y=0;$y<$height;$y++) {
    for ($x=0;$x<$width;$x++) {
        if ($map->map[$y*$width+$x] ==1) {
            $result = $map->scan_from_point($x,$y);
            //echo "$x,$y: $result\n";
            if ($result>$max) {
                $max = $result;
                $coords = array($x,$y);
            }
        }   
    }
}
echo "The max obstacles scannable is $max at coordinates ($coords[0],$coords[1])\n";
$x = $coords[0];
$y = $coords[1];
$map->setPoint($x,$y,false); // don't include x,y in the results

$angles = array();
for ($j=0;$j<$height;$j++) {
    for ($i=0;$i<$width;$i++) {
        if ($map->getPoint($i,$j)==1) {
            $angle = 90 + rad2deg(atan2($j - $coords[1], $i - $coords[0]));
            if ($angle<0) $angle += 360;
            //$points[$pcount] = array($i,$j,$angle);
            $array_key = ''.$angle;
            if (isset($angles[$array_key])==false) $angles[$array_key] = array();
            array_push($angles[$array_key],array($i,$j));
        }
    }
}


// sort the array angles by key
uksort($angles, function ($a, $b) { return (float) $a <=> (float) $b; });
// now sort all entries of that angle
foreach ($angles as &$anglearray) {
    usort($anglearray, function ($a, $b) use ($x, $y) { return (abs($a[1] - $y) + abs($a[0] - $x)) <=> (abs($b[1] - $y) + abs($b[0] - $x)); });
}

$i = 0;
foreach ($angles as $angle){
    $point = array_shift($angle);
    echo $i.": ".$point[0].'x'.$point[1]."\n";
    $i++;
}

?>