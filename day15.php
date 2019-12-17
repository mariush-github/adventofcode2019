<?php
define('BLOCK_UNKNOWN',-1);
define('BLOCK_WALL',0);
define('BLOCK_TERRAIN',1);
define('BLOCK_OXYGEN',2);

define('DIR_WEST',3);
define('DIR_EAST',4);
define('DIR_NORTH',1);
define('DIR_SOUTH',2);

include 'intCode.php';



class clsMap {
    private $data;
    private $width;
    private $height;

    private $player_x;
    private $player_y;
    private $player_c;

    private $default;

    private $min;
    private $max;
    private $characters;

    public function __construct($width=1,$height=1, $default = 0,$player_x=-1,$player_y=-1,$player_c='P') {
        if ($width < 1) die('width <1');
        if ($height < 1) die('height <1');
        $this->default = $default;
        $this->width = $width;
        $this->height = $height;
        $this->max = array(0,0);
        $this->min = array($this->width,$this->height);
        $this->characters = array();
        $this->player_x = $player_x;
        $this->player_y = $player_y;
        $this->player_c = $player_c;
        
    }
    public function get($x,$y) {
        return $this->get_any($x,$y,0,$this->default);
    }
    public function set($x,$y,$value) {
        $this->set_any($x,$y,0,$value);
        $this->update_minmax($x,$y);
    }
    public function get_ox($x,$y) {
        return $this->get_any($x,$y,1,0);
    }
    public function set_ox($x,$y,$value) {
        $this->set_any($x,$y,1,$value);
    }

    public function get_any($x,$y,$z,$default) {
        if ($y > $this->height) return $default;
        if ($x > $this->width) return $default;
        $offset = $y*$this->width + $x;
        if (isset($this->data[$offset])==false) return $default;
        if (isset($this->data[$offset][$z])==false) return $default;
        return $this->data[$offset][$z];
    }
    public function set_any($x,$y,$z,$value) {
        if ($y > $this->height) return ;
        if ($x > $this->width) return ;
        $offset = $y*$this->width + $x;
        if (isset($this->data[$offset])==false) $this->data[$offset] = array(); 
        $this->data[$offset][$z] = $value;
        
    }

    public function set_player($x=-1,$y=-1,$char=''){
        $this->player_x = $x;
        $this->player_y = $y;
        if ($char!='') $this->player_c = $char;
        if ($x!=-1 && $y!=-1) $this->update_minmax($x,$y);
    }
    public function resize($width_new,$height_new) {
        $can_resize=false;
        if ($width_new > $this->width || $height_new > $this->height) $can_resize=true;
        if ($can_resize==false) return;
        $buffer = array();
        foreach ($this->data as $offset => $value) {
            $y = intdiv($offset,$this->width);
            $x = $offset % $this->width;
            $this->buffer[$y*$width_new + $x] = $value;
        }
        $this->data = $buffer;
        $this->width = $width_new;
        $this->height = $height_new;
    }
    public function char($id,$value) {
        $this->characters[$id] = $value;
    }
    private function update_minmax($x,$y) {
        if ($x<$this->min[0]) $this->min[0] = $x;
        if ($y<$this->min[1]) $this->min[1] = $y;
        if ($x>$this->max[0]) $this->max[0] = $x;
        if ($y>$this->max[1]) $this->max[1] = $y;

    }
    public function display() {
        
        $corner_topleft = chr(0xC9); 
        $corner_botleft = chr(0xC8);
        $corner_topright = chr(0xBB);
        $corner_botright = chr(0xBC);
        $border_horz = chr(0xCD);
        $border_vert = chr(0xBA);
        $text = '';
        echo "\n";
        $text .= $corner_topleft.str_pad($border_horz,($this->max[0]-$this->min[0]+1),$border_horz).$corner_topright."\n";
        for ($j=$this->min[1];$j<=$this->max[1];$j++) {
            $text.= $border_vert;
            for ($i=$this->min[0];$i<=$this->max[0];$i++) {
                $value = $this->get($i,$j);
                $char = ' ';
                if (isset($this->characters[$value])==true) $char = $this->characters[$value];
                if ($i==$this->player_x && $j==$this->player_y) {
                    $text.= $this->player_c;
                } else {
                    $text .= $char;
                }
            }
            $text .= $border_vert."\n";
        }
        
        $text .= $corner_botleft.str_pad($border_horz,($this->max[0]-$this->min[0]+1),$border_horz).$corner_botright."\n";
        echo $text;
        echo "[".$this->min[0].','.$this->min[1].']-['.$this->max[0].','.$this->max[1]."] \n";
        return $text;
    }
}

function create_node($x,$y,$parent=null) {
    //echo "create_node $x,$y,$parent\n";
    global $nodes;
    $node = new stdClass;
    $node->x = $x;
    $node->y = $y;
    $node->o = 0;   // how many minutes until oxygen is in this node
    $node->prev = $parent;
    $node->next = array();
    $node->scan = false; // did we look around?
    $node->locked = false; // true = we went this route and was dead end
    $index = count($nodes);
    $nodes[$index] = $node;
    if ($parent!==null) {
        array_push($nodes[$parent]->next,$index);
        //var_dump($nodes[$parent]);
    }
    //var_dump($nodes[$index]);
    
    return $index;
}
// TRIES to move in one direction, returns what the robot says
function robot_move($from_x,$from_y,$to_x,$to_y) {
    global $robot;
    $command = '';
    if ($to_x == $from_x && $to_y <  $from_y) $command = 1; // ^ NORTH (1)
    if ($to_x == $from_x && $to_y >  $from_y) $command = 2; // < WEST (3)
    if ($to_x <  $from_x && $to_y == $from_y) $command = 3; // < WEST (3)
    if ($to_x >  $from_x && $to_y == $from_y) $command = 4; // < EAST (4)
    $robot->input($command);
    $answer = $robot->output();
    $robot->run();
    return $answer;
}
// TRIES to move in one direction, goes back if successful 
function robot_peek($from_x,$from_y,$to_x,$to_y) {
    $answer = robot_move($from_x,$from_y,$to_x,$to_y);
    if ($answer>0) $answer2 = robot_move($to_x,$to_y,$from_x,$from_y);
    return $answer;

}
// 
function can_scan($x,$y) {
    global $nodes,$map;
    $value = $map->get($x,$y);
    return ($value==BLOCK_UNKNOWN) ? TRUE : FALSE;


}

$generateHTML=false;

$jsonArray = array();

$nodes = array();

$map = new clsMap(100,100,BLOCK_UNKNOWN);
$map->char(BLOCK_UNKNOWN,' ');
$map->char(BLOCK_WALL,chr(0xDB));       // full fill block
$map->char(BLOCK_TERRAIN,chr(0xB0));    // like gravel road
$map->char(BLOCK_OXYGEN,chr(0xF9));     // ball like char
$map->set_player(50,50);
$map->set(50,50,BLOCK_TERRAIN);
$map->display();

$node = create_node(50,50,null);

$code = file_get_contents(__DIR__ .'/inputs/15.txt');

$robot = new intCodeComputer($code);
$robot->configure(array('pause_output'=>TRUE,'debug'=>FALSE));
$robot->run();

$oxynode = null;
$oxyx = 0;
$oxyy = 0;
$oxyn = 0;

$continue = true;
while ($continue == true){
    $x = $nodes[$node]->x;
    $y = $nodes[$node]->y;    
    if ($nodes[$node]->scan==false) {
        // down,up, right,left - if we branch, we always pick the last possible path
        $dirs = array(array(0,1),array(0,-1), array(1,0), array(-1,0));
        for ($i=0;$i<4;$i++) {
            $m = $x + $dirs[$i][0];
            $n = $y + $dirs[$i][1];
            if (can_scan($m,$n)==true) { 
                $r = robot_peek($x,$y,$m,$n);
                $map->set($m,$n,$r);
                //echo "scan $m,$n :: $r \n";
                if ($r==BLOCK_TERRAIN) $temp = create_node($m,$n,$node);    // terrain 
                if ($r==BLOCK_OXYGEN) {$oxynode = $node; $oxyx=$m;$oxyy=$n; $oxyn = create_node($m,$n,$node);/*$continue = FALSE;*/ }           // found our oxygen
            }
        }
        $nodes[$node]->scan = true;
        $text = $map->display();
        array_push($jsonArray,$text);
        usleep(500000);
    }
    
    $m = null;
    $n = null; 
    $nodeid = null;
    //var_dump($nodes[$node]);
    // let's look and see if there's a direction we can take
    if (count($nodes[$node]->next)>0) {
        foreach ($nodes[$node]->next as $key => $id) {
            //echo "check $key::$id \n";
            if ($nodes[$id]->locked==false) { $m = $nodes[$id]->x; $n = $nodes[$id]->y; $nodeid = $id; }
        }
    }
    //echo "$m,$n,$nodeid\n";
    if ($nodeid===null) { 
        // we're at a dead end, so we go one step backwards 
        // and we keep going backwards until we reach a previous junction
        $nodes[$node]->locked = true;
        if ($nodes[$node]->prev === null) {
            $continue = false;
        }  else {
            $node = $nodes[$node]->prev;
            $m = $nodes[$node]->x; // go backwards to the parent node 
            $n = $nodes[$node]->y;
            robot_move($x,$y,$m,$n);       
            
            $map->set_player($m,$n);
            $text = $map->display();
            array_push($jsonArray,$text);
            usleep(50000);
        }
    } else {
        // there's at least one route we can advance 
        $node = $nodeid;
        robot_move($x,$y,$m,$n);        
        $map->set_player($m,$n);
        $text = $map->display();
        array_push($jsonArray,$text);
        usleep(50000);
        
    }

}
$counter = 0;
while ($oxynode!==null) {
    $counter++;
    $oxynode = $nodes[$oxynode]->prev;
}
echo "Part 1 answer: steps=$counter\n";
echo "Oxygen is located at x=$oxyx y=$oxyy node=$oxyn \n";
$map->set_ox($oxyx,$oxyy,1);
$level = 1;
$continue = true;
while ($continue==true) {
    $continue=false;
    for ($j=0;$j<100;$j++) {
        for ($i=0;$i<100;$i++) {
            if ($level == $map->get_ox($i,$j)) {
                $dirs = [ [-1,0],[1,0],[0,-1],[0,1]];
                for ($k=0;$k<4;$k++) {
                    $m = $i+$dirs[$k][0];
                    $n = $j+$dirs[$k][1];
                    if ($map->get($m,$n)==BLOCK_TERRAIN) {
                        if ($map->get_ox($m,$n)==0) {
                            $map->set_ox($m,$n,$level+1);
                            $continue = true;
                        }
                    }
                }
            }
        }
    }
    $level++;
}
echo "Maximum level was ".($level-2)."\n"; // -2 because we start with 1 on oxygen position.



function text2html($text) {
    $t = str_replace("\n",'<br/>',$text);
    $t = str_replace(array(' ',"\n",chr(0xDB),chr(0xB0),chr(0xF9),chr(0xC9),chr(0xC8),chr(0xBB),chr(0xBC),chr(0xCD),chr(0xBA)), 
                     array('&nbsp;','<br/>','\u2588','\u2591','\u25CF','\u2554','\u255A','\u2557','\u255d','\u2550','\u2551'),$t);
    return $t;
}

if ($generateHTML ==true) {

$html = '<html><head></head><body>
<p id="status"></p>
<p id="animation">
</p>
<script type=\'text/javascript\'>
let framecount = '.count($jsonArray).'
let animationframes = []
';
foreach ($jsonArray as $id => $text) {
    $html .= 'animationframes['.$id.'] = `'.text2html($text)."`\n";
}
$html .= "
var texta  = document.getElementById('animation')
texta.style.fontFamily = \"Liberation Mono\";
var frame = 0;

function drawFrame() {
    let s = document.getElementById('status')
    s.innerHTML = `Frame ` + frame + `/` + framecount
    if (frame >= framecount) return
    texta.innerHTML = animationframes[frame]
    frame++;
    
}
setInterval(drawFrame,25);

</script>
</body>
</html>
";
file_put_contents('animation.html',$html);
}
die();
?>