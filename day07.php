<?php

// see https://adventofcode.com/2019/day/7

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
    private $input_addr;

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
           99 => array('label' => 'die', 'count' => 0),
           );
        $this->valus = array(0,0,0);
        $this->addrs = array(0,0,0);
        $this->modes = array(0,0,0); // 0=address, 1=immediate
        $this->input_addr = -1;
        $this->counter = 0;
        $this->running = false;
        $this->pauseReason = ''; // pause on input, output, future (which will show up here)
        if (trim($codetext)!='') {        
            $this->code = explode(',',$codetext);
            foreach ($this->code as $idx => $value) { $this->code[$idx] = intval(trim($value)); } 
            if ($autostart==true) $this->run();
        }
    }

    public function input($value) {
        $this->code[$this->input_addr] = $value;
        if ($this->debug==true) {
            echo /*str_pad($this->debug_id,2,' ',STR_PAD_LEFT).' '.*/ ' '.str_pad($this->input_addr,2,' ',STR_PAD_LEFT).' INPUT '.$value;
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
                $this->input_addr = $this->addrs[0];
                $continue = FALSE;
            }
            if ($this->opcode==4) {  // output
                $this->output = $this->valus[0];
                $log = ' out='.$this->output;
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
            if ($this->opcode==99) {
                $log = " EXIT\n";
                $this->running = false;
                $continue = false;
            }
            if ($this->debug==true) echo $log;
        }
    }

    private function decode_opcode() {
        $temp = str_pad($this->code[$this->counter],5,'0',STR_PAD_LEFT);
        $this->counter++;
        for ($i=0;$i<3;$i++) {
            $this->addrs[$i] = -1;
            $this->valus[$i] = 0;
            $this->modes[$i] = 0;
        }
        $this->opcode = intval(substr($temp,3,2));
        $valid = false;
        foreach ($this->opcodes as $key => $value) { if ($this->opcode==$key) $valid=true; }
        if ($valid==false) die("Encountered invalid opcode at offset $this->counter! [opcode=$this->opcode ($this->opcode)]\n");
        
        for ($i=0;$i<$this->opcodes[$this->opcode]['count'];$i++) {
            $this->modes[$i] = intval(substr($temp,2-$i,1));
            if ($this->modes[$i]==1) $this->valus[$i] = $this->code[$this->counter];
            if ($this->modes[$i]==0) {
                $this->addrs[$i] = $this->code[$this->counter];
                $this->valus[$i] = $this->code[$this->addrs[$i]];
            }
            $this->counter++;
        }
        if ($this->debug==true) {
            echo "\n".str_pad($this->debug_id,2,' ',STR_PAD_LEFT).' '.
                 str_pad($this->counter,6,' ',STR_PAD_LEFT).' '.
                 str_pad($this->opcode,2,' ',STR_PAD_LEFT).' '.
                 str_pad($this->opcodes[$this->opcode]['label'],6,' ',STR_PAD_LEFT).' '.
                 'm='.$this->modes[0].$this->modes[1].$this->modes[2].' '.
                 'a=[ '.str_pad($this->addrs[0],8,' ',STR_PAD_LEFT).' '.str_pad($this->addrs[1],8,' ',STR_PAD_LEFT).' '.str_pad($this->addrs[2],8,' ',STR_PAD_LEFT).' ] '.
                 'v=[ '.str_pad($this->valus[0],8,' ',STR_PAD_LEFT).' '.str_pad($this->valus[1],8,' ',STR_PAD_LEFT).' '.str_pad($this->valus[2],8,' ',STR_PAD_LEFT).' ] ';
        }
    }
}


$code = file_get_contents(__DIR__ .'/inputs/07.txt');
// test sequence for mode 1
//$code = '3,15,3,16,1002,16,10,16,1,16,15,15,4,15,99,0,0';
// test sequence for mode 2 
//$code = '3,52,1001,52,-5,52,3,53,1,52,56,54,1007,54,5,55,1005,55,26,1001,54,-5,54,1105,1,12,1,53,54,53,1008,54,0,55,1001,55,1,55,2,53,55,53,4,53,1001,56,-1,56,1005,56,6,99,0,0,0,0,10';

// https://www.dcode.fr/permutations-generator - too easy to code it myself 
$permutations = json_decode('[[0,1,2,3,4],[0,1,2,4,3],[0,1,3,2,4],[0,1,3,4,2],[0,1,4,2,3],[0,1,4,3,2],[0,2,1,3,4],[0,2,1,4,3],[0,2,3,1,4],[0,2,3,4,1],[0,2,4,1,3],[0,2,4,3,1],[0,3,1,2,4],[0,3,1,4,2],[0,3,2,1,4],[0,3,2,4,1],[0,3,4,1,2],[0,3,4,2,1],[0,4,1,2,3],[0,4,1,3,2],[0,4,2,1,3],[0,4,2,3,1],[0,4,3,1,2],[0,4,3,2,1],[1,0,2,3,4],[1,0,2,4,3],[1,0,3,2,4],[1,0,3,4,2],[1,0,4,2,3],[1,0,4,3,2],[1,2,0,3,4],[1,2,0,4,3],[1,2,3,0,4],[1,2,3,4,0],[1,2,4,0,3],[1,2,4,3,0],[1,3,0,2,4],[1,3,0,4,2],[1,3,2,0,4],[1,3,2,4,0],[1,3,4,0,2],[1,3,4,2,0],[1,4,0,2,3],[1,4,0,3,2],[1,4,2,0,3],[1,4,2,3,0],[1,4,3,0,2],[1,4,3,2,0],[2,0,1,3,4],[2,0,1,4,3],[2,0,3,1,4],[2,0,3,4,1],[2,0,4,1,3],[2,0,4,3,1],[2,1,0,3,4],[2,1,0,4,3],[2,1,3,0,4],[2,1,3,4,0],[2,1,4,0,3],[2,1,4,3,0],[2,3,0,1,4],[2,3,0,4,1],[2,3,1,0,4],[2,3,1,4,0],[2,3,4,0,1],[2,3,4,1,0],[2,4,0,1,3],[2,4,0,3,1],[2,4,1,0,3],[2,4,1,3,0],[2,4,3,0,1],[2,4,3,1,0],[3,0,1,2,4],[3,0,1,4,2],[3,0,2,1,4],[3,0,2,4,1],[3,0,4,1,2],[3,0,4,2,1],[3,1,0,2,4],[3,1,0,4,2],[3,1,2,0,4],[3,1,2,4,0],[3,1,4,0,2],[3,1,4,2,0],[3,2,0,1,4],[3,2,0,4,1],[3,2,1,0,4],[3,2,1,4,0],[3,2,4,0,1],[3,2,4,1,0],[3,4,0,1,2],[3,4,0,2,1],[3,4,1,0,2],[3,4,1,2,0],[3,4,2,0,1],[3,4,2,1,0],[4,0,1,2,3],[4,0,1,3,2],[4,0,2,1,3],[4,0,2,3,1],[4,0,3,1,2],[4,0,3,2,1],[4,1,0,2,3],[4,1,0,3,2],[4,1,2,0,3],[4,1,2,3,0],[4,1,3,0,2],[4,1,3,2,0],[4,2,0,1,3],[4,2,0,3,1],[4,2,1,0,3],[4,2,1,3,0],[4,2,3,0,1],[4,2,3,1,0],[4,3,0,1,2],[4,3,0,2,1],[4,3,1,0,2],[4,3,1,2,0],[4,3,2,0,1],[4,3,2,1,0]]');

$nr = 0;
$maxPerm = 0;
$max = 0;
$units= array();

foreach ($permutations as $key=>$perm) {
    echo json_encode($perm)." ";
    $result = 0;
    for ($i=0;$i<5;$i++) {
        $units[$i] = new Computer($code,true,false,$i);
        $units[$i]->input($perm[$i]);
        $units[$i]->input($result);
        $result = $units[$i]->output;
    }
    echo "\n";
    if ($result>$max) {
        $max = $result;
        $maxPerm = $perm;
    }    
}
echo "\nThe maximum was $max, reached with combination: ".json_encode($maxPerm)."\n";
echo 'Waiting 5s... Press Ctrl+Break to pause.';
sleep(5);


$max = 0;
$maxPerm = 0;
// create the permutations from the first set
$permutations2 = $permutations;
foreach ($permutations2 as $key => $val) {
    $p = $val;
    foreach ($p as $i=>$v) { $p[$i] = $v + 5; }
    $permutations2[$key] = $p;
}

foreach ($permutations2 as $key=>$perm) {
    echo json_encode($perm)." ";
    $result = 0;
    for ($i=0;$i<5; $i++) {
        $units[$i] = new Computer($code,true,true,$i);
        $units[$i]->input($perm[$i]);
        $units[$i]->input($result);
        $result = $units[$i]->output;
    }
    if ($units[4]->running==true){
        $continue = true;
        while ($continue==true) {
            for ($i=0;$i<5;$i++) {
                $units[$i]->input($result);
                $result = $units[$i]->output;
            }
            if ($units[4]->running==false) $continue=FALSE;
        }
    }
    if ($result>$max) {
        $max = $result;
        $maxPerm = $perm;
    }

}

echo "\nThe maximum was $max, reached with combination: ".json_encode($maxPerm)."\n";

?>