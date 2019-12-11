<?php

// see https://adventofcode.com/2019/day/11

// same as Day 9 class, but pauses after every output (must do ->run() to resume)

class Computer {

    private $debug;
    private $debug_id;

    private $opcodes;
    private $counter;
 
    private $code;
    private $opcode;
    private $addrs;
    private $valus;
    private $modes;
    private $addr_input;
    private $addr_relative;

    public $output;
    public $pauseReason;
    public $running;

    public function __construct($codetext='99',$autostart=true,$debug=true, $debug_id=0) {
        $this->debug = $debug; // set to true to see output on screen
        $this->debug_id = $debug_id; 
        $this->opcodes = array(
            1 => array('label' => 'add', 'count' => 3),
            2 => array('label' => 'mul', 'count' => 3),
            3 => array('label' => ' in', 'count' => 1),
            4 => array('label' => 'out', 'count' => 1),
            5 => array('label' => 'jit', 'count' => 2), // jump if true
            6 => array('label' => 'jif', 'count' => 2), // jump if false
            7 => array('label' => 'jls', 'count' => 3), // jump if less
            8 => array('label' => 'jeq', 'count' => 3), // jump if equal
            9 => array('label' => 'rel', 'count' => 1),
           99 => array('label' => 'die', 'count' => 0),
           );
        $this->valus = array(0,0,0);
        $this->addrs = array(0,0,0);
        $this->modes = array(0,0,0); // 0=address, 1=immediate, 2=relative
        $this->addr_input = -1;
        $this->counter = 0;
        $this->addr_relative = 0;
        $this->running = false;
        $this->pauseReason = ''; // pause on input, output, future (which will show up here)
        if (trim($codetext)!='') {        
            $this->code = explode(',',$codetext);
            foreach ($this->code as $index => $value) { $this->code[$index] = floatval(trim($value)); } 
            if ($autostart==true) $this->run();
        }
    }

    public function input($value) {
        $this->code[$this->addr_input] = $value;
        if ($this->debug==true) {
            echo /*str_pad($this->debug_id,2,' ',STR_PAD_LEFT).' '.*/ ' '.str_pad($this->addr_input,2,' ',STR_PAD_LEFT).' INPUT '.$value;
        }
        $this->run();
    }
    public function run() {
        if (count($this->code)<1) return; // safety check, in case codetext variable was empty (file not found)
        $continue = true;
        $this->running = true;
        while ($continue==true) {
            $result = $this->decode_opcode();
            $log = '';
            if (($this->opcode==1) || ($this->opcode==2)) { // add or mul
                $a = $this->valus[0];
                $b = $this->valus[1];
                if ($this->opcode==1) $c = $a + $b;
                if ($this->opcode==2) $c = $a * $b;
                $this->code[$this->addrs[2]] = $c;
                $log = ' c= '.$c;
            }
            if ($this->opcode==3) {  // input (memorize address and pause, input value from main program)
                $this->pauseReason = 'input';
                $this->addr_input = $this->addrs[0];
                $continue = FALSE;
            }
            if ($this->opcode==4) {  // output
                $this->output = $this->valus[0];
                $log = ' out='.$this->output;
                $this->pauseReason = 'output';
                $continue = FALSE;
            }
            if ($this->opcode==5) { // jump if true
                if ($this->valus[0]!=0) {
                    $this->counter = $this->valus[1];
                    $log = ' JIT='.$this->counter;
                }
            }
            if ($this->opcode==6) { // jump if false
                if ($this->valus[0]==0) {
                    $this->counter = $this->valus[1];
                    $log = ' JIF='.$this->counter;
                }
            }
            if ($this->opcode==7) { // jump if less
                $c = ($this->valus[0] < $this->valus[1]) ? 1 : 0;
                $this->code[$this->addrs[2]] = $c;
                $log = ' JLS '.$c.' -> '.$this->addrs[2];
            }
            if ($this->opcode==8) { // jump if eq
                $c = ($this->valus[0] == $this->valus[1]) ? 1: 0;
                $this->code[$this->addrs[2]] = $c;
                $log = ' JEQ, '.$c.' -> '.$this->addrs[2];
            }
            if ($this->opcode==9) {
                $this->addr_relative += $this->valus[0];
                $log = ' REL = '.$this->addr_relative;
            }
            if ($this->opcode==99) {
                $log = " EXIT\n";
                $this->running = false;
                $continue = false;
            }
            if ($this->debug==true) echo $log;
        }
    }
    private function get_value($address) {
        return (isset($this->code[$address])==TRUE) ? $this->code[$address] : 0;
    }
    
    private function get_counter_value($autoincrement=true) {
        $value = (isset($this->code[$this->counter])==TRUE) ? $this->code[$this->counter] : 0;
        if ($autoincrement==true) $this->counter++;
        return $value;
    }

    private function decode_opcode() {
        $this->opcode = $this->get_counter_value();
        if ($this->opcode<0) die("Invalid opcode encountered at address ".($this->counter-1).": $this->opcode");
        $temp = str_pad($this->opcode,5,'0',STR_PAD_LEFT);
        for ($i=0;$i<3;$i++) {
            $this->addrs[$i] = -1;
            $this->valus[$i] = 0;
            $this->modes[$i] = 0;
        }
        $this->opcode = intval(substr($temp,3,2));
        $valid = false;
        foreach ($this->opcodes as $key => $value) { if ($this->opcode==$key) $valid=true; }
        if ($valid==false) die("Encountered invalid opcode at offset $this->counter! [opcode=$this->opcode]\n");
        
        for ($i=0;$i<$this->opcodes[$this->opcode]['count'];$i++) {
            $this->modes[$i] = ord(substr($temp,2-$i,1))-0x30;
            $value = $this->get_counter_value(); // auto increments counter
            if ($this->modes[$i]==1) $this->valus[$i] = $value;
            if ($this->modes[$i]==0) {
                $this->addrs[$i] = $value;
                $this->valus[$i] = $this->get_value($this->addrs[$i]);
            }
            if ($this->modes[$i]==2) { 
                $this->addrs[$i] = $this->addr_relative + $value;
                $this->valus[$i] = $this->get_value($this->addrs[$i]);
            }
        }
        if ($this->debug==true) {
            echo "\n".str_pad($this->debug_id,2,' ',STR_PAD_LEFT).' '.
                 str_pad($this->counter,6,' ',STR_PAD_LEFT).' '.
                 str_pad($this->opcode,2,' ',STR_PAD_LEFT).' '.
                 str_pad($this->opcodes[$this->opcode]['label'],6,' ',STR_PAD_LEFT).' '.
                 'm='.$this->modes[0].$this->modes[1].$this->modes[2].' '.
                 'a=[ ';
            for ($i=0;$i<3;$i++) echo str_pad($this->addrs[$i],6,' ',STR_PAD_LEFT).' ';
            echo ' ] '.'v=[ ';
            for ($i=0;$i<3;$i++) echo str_pad($this->valus[$i]>99999999 ? dechex($this->valus[$i]) : $this->valus[$i],8,' ',STR_PAD_LEFT).' ';
            echo ' ]';
        }
    }
}



function getPixel($x,$y) {

    global $map;
    $offset = $x.','.$y;
    if (isset($map[$offset])==FALSE) return 0;
    return $map[$offset];
}   
function setPixel($x,$y,$color) {
    global $map;
    $offset = $x.','.$y;
    $map[$offset] = $color;
}

$code = file_get_contents(__DIR__ .'/inputs/11.txt');


$robot = new Computer($code,true,false,1);

$map = array();
$x = 8192;
$y = 8192;

// COMMENT LINE BELOW FOR PART 1 answer.
setPixel($x,$y,1);  // define the cell under robot as white

$orientation = 'up';
$moves = array(   'up'=> array('left','right'), 'left' => array('down','up'), 
                'down'=> array('right','left'), 'right'=> array('up','down'));
while ($robot->running==true) {
    //echo "$x,$y: ";
    $color = getPixel($x,$y);
    $robot->input($color);
    $newcolor = $robot->output;
    $robot->run();
    $direction = $robot->output;
    $robot->run();
    setPixel($x,$y,$newcolor);
    $orientation = $moves[$orientation][$direction];
    if ($orientation=='up') { $y--; }
    if ($orientation=='down') { $y++; }
    if ($orientation=='left') { $x--;}
    if ($orientation=='right') { $x++; }
    //echo "$newcolor, $direction, $orientation, $x, $y\n";
}
$minx = 99999999;
$maxx = -99999999;

$miny = 99999999;
$maxy = -99999999;

foreach ($map as $key => $value) {
    $p = explode(',',$key);
    //echo $key."\n";
    if ($minx > $p[0]) $minx = $p[0];
    if ($maxx < $p[0]) $maxx = $p[0];

    if ($miny > $p[1]) $miny = $p[1];
    if ($maxy < $p[1]) $maxy = $p[1];
    
}

echo count($map);
echo "($minx,$miny) : ($maxx,$maxy)\n";
foreach ($map as $key => $value) {
    if ($value==0) $map[$key]=2;
}
for ($j=$miny;$j<=$maxy;$j++) {
    for ($i=$minx;$i<=$maxx;$i++) {
        echo (getPixel($i,$j)==2) ? chr(0xdb) : chr(0xb2);
    }
    echo "\n";
}


//var_dump($map);

?>