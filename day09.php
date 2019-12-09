<?php

// see https://adventofcode.com/2019/day/8

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
    private $relative;

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
        $this->modes = array(0,0,0); // 0=address, 1=immediate
        $this->input_addr = -1;
        $this->counter = 0;
        $this->relative = 0;
        $this->running = false;
        $this->pauseReason = ''; // pause on input, output, future (which will show up here)
        if (trim($codetext)!='') {        
            $this->code = explode(',',$codetext);
            foreach ($this->code as $idx => $value) { $this->code[$idx] = floatval(trim($value)); } 
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
            if ($this->opcode==9) {
                $this->relative += $this->valus[0];
                $log = ' REL = '.$this->relative;
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
        $this->opcode = floatval(substr($temp,3,2));
        $valid = false;
        foreach ($this->opcodes as $key => $value) { if ($this->opcode==$key) $valid=true; }
        if ($valid==false) die("Encountered invalid opcode at offset $this->counter! [opcode=$this->opcode ($this->opcode)]\n");
        
        for ($i=0;$i<$this->opcodes[$this->opcode]['count'];$i++) {
            $this->modes[$i] = floatval(substr($temp,2-$i,1));
            $value = (isset($this->code[$this->counter])==TRUE) ? $this->code[$this->counter] : 0;
            if ($this->modes[$i]==1) $this->valus[$i] = $value;
            if ($this->modes[$i]==0) {
                $this->addrs[$i] = $this->code[$this->counter];
                $this->valus[$i] = (isset($this->code[$this->addrs[$i]])==TRUE) ? $this->code[$this->addrs[$i]] : 0;
            }
            if ($this->modes[$i]==2) { 
                $this->addrs[$i] = $this->relative + ((isset($this->code[$this->counter])==TRUE) ? $this->code[$this->counter] : 0);
                $this->valus[$i] = (isset($this->code[$this->addrs[$i]])==TRUE) ? $this->code[$this->addrs[$i]] : 0;
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


$filename = __DIR__ . '/inputs/09.txt';

$code = file_get_contents($filename);
// test sequence
//$code = '109,1,204,-1,1001,100,1,100,1008,100,16,101,1006,101,0,99';
$pc = new Computer($code,true,true,1);
// change to 2 for second part.
$pc->input(1);
echo "\n".$pc->output."\n";

?>
