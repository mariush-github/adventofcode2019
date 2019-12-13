<?php

// see https://adventofcode.com/2019/day/13

class Computer {

    private $opcodes;

    private $pc;        // program counter (initializes at 0)
 
    private $memory;    // memory of the computer (all 64 bit signed values)
    private $opcode;    // current opcode

    private $addr_input;        // where to put incoming data (computer pauses on input command)
    private $addr_relative;     // address offset in relative mode

    private $outputs;
    public $pauseReason;
    public $running;

    private $cfg;

    public function __construct($code='99') {
        $this->opcodes = array(
            1 => array('label' => 'add', 'count' => 3),
            2 => array('label' => 'mul', 'count' => 3),
            3 => array('label' => ' in', 'count' => 1),
            4 => array('label' => 'out', 'count' => 1),
            5 => array('label' => 'jit', 'count' => 2), // jump if true
            6 => array('label' => 'jif', 'count' => 2), // jump if false
            7 => array('label' => 'jls', 'count' => 3), // jump if less
            8 => array('label' => 'jeq', 'count' => 3), // jump if equal
            9 => array('label' => 'rel', 'count' => 1), // set relative address
           99 => array('label' => 'die', 'count' => 0),
           );
        $this->memory = array();
        $this->pc = 0;

        $this->cfg['debug'] = false;
        $this->cfg['debug_id'] = 0;
        $this->cfg['pause_output'] = true;
        $this->cfg['input_autoresume'] = true;

        $this->opcode = $this->defaultOpcode();
        $this->addr_input = -1;
        $this->addr_relative = 0;
        
        $this->running = false;
        $this->pauseReason = ''; // pause on input, output, other
        
        $this->outputs = array();

        if (trim($code)!='') $this->load($code);
    }

    public function configure($array) {
        if (is_array($array)==false) return;
        foreach ($array as $key => $value) {
            $this->cfg[$key]=$value;
        }
    }
    public function load($code) {
        if (trim($code)=='') return;
        $i=0;
        $values = explode(',',$code);
        foreach ($values as $value) {
            $s = trim($value); if ($s!='') { $this->memory[$i] = intval($s); $i++; }
        }
        $this->pc = 0;
        //echo "Loaded $i values.\n";
    }
    public function reset() {
        $this->pc = 0;
        $this->addr_relative = 0;
        $this->addr_input = -1;
    }

    private function defaultOpcode() {
        $opcode = new stdClass();
        $opcode->pc = 0;
        $opcode->value = 99;
        $opcode->a = array(-1,-1,-1);
        $opcode->m = array(0,0,0);
        $opcode->v = array(0,0,0);
        return $opcode;
    }

    public function set_value($address,$value) {
        $this->memory[$address] = $value;
    }

    public function get_value($address) {
        return (isset($this->memory[$address])==true) ? $this->memory[$address] : 0;
    }

    public function input($value) {
        $this->memory[$this->addr_input] = $value;
        if ($this->cfg['debug']==true) {
            echo /*str_pad($this->cfg['debug_id'],2,' ',STR_PAD_LEFT).' '.*/ ' '.str_pad($this->addr_input,2,' ',STR_PAD_LEFT).' INPUT '.$value;
        }
        if ($this->cfg['input_autoresume']==true) $this->run();
    }
    public function output() {
        if (count($this->outputs)==0) return 0;
        $value = array_shift($this->outputs);
        return $value;
    }

    public function hasOutput() {
        return count($this->outputs)>0 ? TRUE : FALSE;
    }
       
    private function get_counter_value() {
        $value = (isset($this->memory[$this->pc])==TRUE) ? $this->memory[$this->pc] : 0;
        $this->pc++;
        return $value;
    }

    private function opcode_decode() {
        $value = $this->get_counter_value();
        if ($value<0) die("Invalid opcode encountered at address ".($this->pc-1)." : $value\n");
        $this->opcode->value = $value % 100; $value = intdiv($value,100);
        $this->opcode->pc = $this->pc;
        for ($i=0;$i<3;$i++) {
            $j = 2-$i;
            $this->opcode->a[$i] = -1;
            $this->opcode->v[$i] =  0;
            $this->opcode->m[$i] = $value % 10; 
            $value = intdiv($value,10);
        }
        $valid = false;
        foreach ($this->opcodes as $key => $value) { if ($this->opcode->value==$key) $valid=true; }
        if ($valid==false) die("Encountered invalid opcode at offset ".($this->pc-1)."! [opcode=".$this->opcode->value."]\n");
        for ($i=0;$i<$this->opcodes[$this->opcode->value]['count'];$i++) {
            $value = $this->get_counter_value(); 
            if ($this->opcode->m[$i]==1) $this->opcode->v[$i] = $value;
            if (($this->opcode->m[$i]==0) || ($this->opcode->m[$i]==2)) {
                $this->opcode->a[$i] = $value + (($this->opcode->m[$i]==2) ? $this->addr_relative : 0);
                $this->opcode->v[$i] = $this->get_value($this->opcode->a[$i]);
            }
        }
        if ($this->cfg['debug']==true) {
            $text = "\n".str_pad($this->cfg['debug_id'],2,' ',STR_PAD_LEFT).' '.
                    str_pad($this->opcode->pc,6,' ',STR_PAD_LEFT).' '.
                    str_pad($this->opcode->value,2,' ',STR_PAD_LEFT).' '.
                    str_pad($this->opcodes[$this->opcode->value]['label'],6,' ',STR_PAD_LEFT).' '.
                    'm='.$this->opcode->m[0].$this->opcode->m[1].$this->opcode->m[2].' '.
                    'a=[ ';
            for ($i=0;$i<3;$i++) { $v = $this->opcode->a[$i]; $text .= str_pad($v,6,' ',STR_PAD_LEFT).' ';}
            $text .= ' ] '.'v=[ ';
            for ($i=0;$i<3;$i++) { $v = $this->opcode->v[$i]; $text .= str_pad($v>99999999 ? dechex($v) : $v,8,' ',STR_PAD_LEFT).' '; }
            $text .= ' ]';
            echo $text;
        }
    }

    public function run() {
        if (count($this->memory)<1) return; // safety check, in case codetext variable was empty (file not found)
        $this->running = true;
        $continue=true;
        while ($continue==true) {
            $this->opcode_decode();
            switch ($this->opcode->value) {
                case 1:
                    $a = $this->opcode->v[0];
                    $b = $this->opcode->v[1];
                    $this->set_value($this->opcode->a[2],$a+$b);
                    break;
                case 2:
                    $a = $this->opcode->v[0];
                    $b = $this->opcode->v[1];
                    $this->set_value($this->opcode->a[2],$a*$b);
                    break;
                case 3: 
                    $this->pauseReason = 'input';
                    $this->addr_input = $this->opcode->a[0];
                    $continue = FALSE;
                    break;
                case 4:
                    array_push($this->outputs,$this->opcode->v[0]);
                    if ($this->cfg['pause_output']==true) {
                        $this->pauseReason = 'ouput';
                        $continue = FALSE;
                    }
                    break;
                case 5:
                    if ($this->opcode->v[0]!=0) $this->pc = $this->opcode->v[1];
                    break;
                case 6: 
                    if ($this->opcode->v[0]==0) $this->pc = $this->opcode->v[1];
                    break;
                case 7:
                    $value = ($this->opcode->v[0]<$this->opcode->v[1]) ? 1 : 0;
                    $this->set_value($this->opcode->a[2],$value);
                    break;
                case 8:
                    $value = ($this->opcode->v[0]==$this->opcode->v[1]) ? 1 : 0;
                    $this->set_value($this->opcode->a[2],$value);
                    break;
                case 9:
                    $this->addr_relative += $this->opcode->v[0];
                    break;
                case 99:
                    $this->running = FALSE;
                    $continue=FALSE;
                    break;
                default: 
                    die('Invalid opcode value '.$this->opcode->value.' at address '.($this->pc-1)." \n");
                    $this->running = FALSE;
                    $continue = FALSE;
            }
            
        }
    }
}

function map_set($x,$y,$value) {
    global $map;
    if (isset($map[$y])==false) $map[$y]=array();
    $map[$y][$x] = $value; 
}
function map_get($x,$y) {
    global $map;
    if (isset($map[$y])==false) $map[$y]=array();
    return (isset($map[$y][$x])==true) ? $map[$y][$x] : 0; 
}
function processOutputs() {
    global $map;
    global $game;
    global $player;
    global $ball;
    global $x_max;
    global $y_max;
    global $score;

    while ($game->hasOutput()==true) {
        $x = $game->output();
        $y = $game->output();
        $tile = $game->output();
        //echo "($x,$y,$tile) ";
        if ($x>-1 && $y>-1) {
            map_set($x,$y,$tile);
            if ($x>$x_max) $x_max = $x;
            if ($y>$y_max) $y_max = $y;
            if ($tile==3) {
                map_set($player[0],$player[1],0);
                $player = array($x,$y);
            }
            if ($tile==4) {
                map_set($ball[0],$ball[1],0);
                $ball = array($x,$y);
            }
        }
        if ($x==-1 && $y==0) {
            $score = $tile;
        }
    }

}


$map = array();

$code = file_get_contents(__DIR__ .'/inputs/13.txt');

$part = 2;  // change to 2 for answer to 2nd solution

$x_max = 0;
$y_max = 0;

$player = array(0,0);
$ball = array(0,0);

$score = 0;

$game = new Computer($code);
$game->configure(array('pause_output'=>FALSE,'debug'=>TRUE));
if ($part==2) $game->configure(array('debug'=>FALSE));
if ($part==2) $game->set_value(0,2);
$game->run();
processOutputs(); // in part 1, game ends without any request for input
if ($part==1){
    $k=0;
    for ($j=0;$j<=$y_max;$j++) {
        for ($i=0;$i<=$x_max;$i++) {
            if (map_get($i,$j)==2) $k++;
        }
    }
    die("count=$k");
}
while ($game->running == true) {
    processOutputs();
    if ($game->pauseReason=='input') {
        $direction = 0;
        if ($player[0]>$ball[0]) $direction = -1;
        if ($player[0]<$ball[0]) $direction = +1;
        $game->input($direction);

    }
}
processOutputs();
echo "x_max = $x_max  y_max = $y_max score = $score\n";
die();
?>