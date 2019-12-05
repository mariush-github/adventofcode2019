<?php

// see https://adventofcode.com/2019/day/5

$opcodes = array(
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

$valus = array(0,0,0);
$addrs = array(0,0,0);
$modes = array(0,0,0); // 0=address, 1=immediate

$counter = 0;
$in = 5;
$out = 0;

$a = 0;
$b = 0;
$c = 0;

$codetext = '3,225,1,225,6,6,1100,1,238,225,104,0,2,218,57,224,101,-3828,224,224,4,224,102,8,223,223,1001,224,2,224,1,223,224,223,1102,26,25,224,1001,224,-650,224,4,224,1002,223,8,223,101,7,224,224,1,223,224,223,1102,44,37,225,1102,51,26,225,1102,70,94,225,1002,188,7,224,1001,224,-70,224,4,224,1002,223,8,223,1001,224,1,224,1,223,224,223,1101,86,70,225,1101,80,25,224,101,-105,224,224,4,224,102,8,223,223,101,1,224,224,1,224,223,223,101,6,91,224,1001,224,-92,224,4,224,102,8,223,223,101,6,224,224,1,224,223,223,1102,61,60,225,1001,139,81,224,101,-142,224,224,4,224,102,8,223,223,101,1,224,224,1,223,224,223,102,40,65,224,1001,224,-2800,224,4,224,1002,223,8,223,1001,224,3,224,1,224,223,223,1102,72,10,225,1101,71,21,225,1,62,192,224,1001,224,-47,224,4,224,1002,223,8,223,101,7,224,224,1,224,223,223,1101,76,87,225,4,223,99,0,0,0,677,0,0,0,0,0,0,0,0,0,0,0,1105,0,99999,1105,227,247,1105,1,99999,1005,227,99999,1005,0,256,1105,1,99999,1106,227,99999,1106,0,265,1105,1,99999,1006,0,99999,1006,227,274,1105,1,99999,1105,1,280,1105,1,99999,1,225,225,225,1101,294,0,0,105,1,0,1105,1,99999,1106,0,300,1105,1,99999,1,225,225,225,1101,314,0,0,106,0,0,1105,1,99999,108,226,677,224,102,2,223,223,1005,224,329,1001,223,1,223,1107,677,226,224,102,2,223,223,1006,224,344,1001,223,1,223,7,226,677,224,1002,223,2,223,1005,224,359,101,1,223,223,1007,226,226,224,102,2,223,223,1005,224,374,101,1,223,223,108,677,677,224,102,2,223,223,1006,224,389,1001,223,1,223,107,677,226,224,102,2,223,223,1006,224,404,101,1,223,223,1108,677,226,224,102,2,223,223,1006,224,419,1001,223,1,223,1107,677,677,224,1002,223,2,223,1006,224,434,101,1,223,223,1007,677,677,224,102,2,223,223,1006,224,449,1001,223,1,223,1108,226,677,224,1002,223,2,223,1006,224,464,101,1,223,223,7,677,226,224,102,2,223,223,1006,224,479,101,1,223,223,1008,226,226,224,102,2,223,223,1006,224,494,101,1,223,223,1008,226,677,224,1002,223,2,223,1005,224,509,1001,223,1,223,1007,677,226,224,102,2,223,223,1005,224,524,1001,223,1,223,8,226,226,224,102,2,223,223,1006,224,539,101,1,223,223,1108,226,226,224,1002,223,2,223,1006,224,554,101,1,223,223,107,226,226,224,1002,223,2,223,1005,224,569,1001,223,1,223,7,226,226,224,102,2,223,223,1005,224,584,101,1,223,223,1008,677,677,224,1002,223,2,223,1006,224,599,1001,223,1,223,8,226,677,224,1002,223,2,223,1006,224,614,1001,223,1,223,108,226,226,224,1002,223,2,223,1006,224,629,101,1,223,223,107,677,677,224,102,2,223,223,1005,224,644,1001,223,1,223,8,677,226,224,1002,223,2,223,1005,224,659,1001,223,1,223,1107,226,677,224,102,2,223,223,1005,224,674,1001,223,1,223,4,223,99,226';

$data = explode(',',$codetext);
foreach ($data as $idx => $value) { $data[$idx] = trim($value); } // just some safety check
 
while (true) {

    $op = str_pad($data[$counter],5,'0',STR_PAD_LEFT);
    $opCounter = $counter; // only for display purposes
    $counter++;
    for ($i=0;$i<3;$i++) {
        $addrs[$i] = -1;
        $valus[$i] = 0;
        $modes[$i] = 0;
    }
    $opId = ltrim(substr($op,3,2),'0'); 
    if ($opId=='') $opId = '0';
    $opId = intval($opId);
    $validOp = false;
    foreach ($opcodes as $key => $value) {
        if ($opId==$key) $validOp=true;
    }
    if ($validOp==false) die("Encountered invalid opcode! opcode=$op\n");
    if ($opcodes[$opId]>0) {
        for ($i=0;$i<$opcodes[$opId]['count'];$i++) {
                $modes[$i] = intval(substr($op,2-$i,1));
                if ($modes[$i]==1) $valus[$i] = intval($data[$counter]);
                if ($modes[$i]==0) {
                    $addrs[$i] = intval($data[$counter]);
                    $valus[$i] = $data[$addrs[$i]];
                }
                $counter++;
            
        }
    }
    // pretty display on screen
    echo str_pad($opCounter,6,' ',STR_PAD_LEFT).'  '.$op.'  '.
         str_pad($opcodes[$opId]['label'],4,' ',STR_PAD_LEFT).':'.$opcodes[$opId]['count'].'  ';
    for ($i=0;$i<3;$i++) {
        if ($i<$opcodes[$opId]['count']) {
            echo '[ A='.str_pad($addrs[$i],8,' ', STR_PAD_LEFT).
                 ', V='.str_pad($valus[$i],8,' ', STR_PAD_LEFT).
                 ' ] ';
        } else {
            echo str_pad('',27,' ');
        }
    }     
    $log = '';
    if (($opId==1) || ($opId==2)) { // add or mul
        $a = $valus[0];
        $b = $valus[1];
        if ($opId==1) $c = $a + $b;
        if ($opId==2) $c = $a * $b;
        $data[$addrs[2]] = $c;
        $log = ' c= '.$c;
    }
    if ($opId==3) {  // in
        // override input with 1 for 1st solution, 5 for the 2nd solution
        // always in immediate mode
        $data[$addrs[0]] = $in;

    }
    if ($opId==4) {  // output
        // put the value in the $output
        $out = $valus[0];
    }
    if ($opId==5) { // jump if true
        if ($valus[0]!=0) {
            $counter = $valus[1];
            $log = ' jump if true to '.$counter;
        }
    }
    if ($opId==6) { // jump if false
        if ($valus[0]==0) {
            $counter = $valus[1];
            $log = ' jump if false to '.$counter;
        }
    }
    if ($opId==7) { // jump if less
        $c = ($valus[0] < $valus[1]) ? 1 : 0;
        $data[$addrs[2]] = $c;
        $log = ' jump less, store '.$c.' to '.$addrs[2];
        
    }
    if ($opId==8) { // jump if eq
        $c = ($valus[0] == $valus[1]) ? 1: 0;
        $data[$addrs[2]] = $c;
        $log = ' jump eq, store '.$c.' to '.$addrs[2];
        
    }
    
    echo ' in=' .str_pad($in,8,' ', STR_PAD_LEFT). 
        ' out=' .str_pad($out,8,' ', STR_PAD_LEFT);  
    echo $log;
    echo "\n";
    $in = 0;
    $out = 0;
    $log = '';
    if ($opId == 99) die('Exit.');

}


function calculate($a1,$a2,$log=false) {
	$data = explode(',','1,0,0,3,1,1,2,3,1,3,4,3,1,5,0,3,2,13,1,19,1,6,19,23,2,23,6,27,1,5,27,31,1,10,31,35,2,6,35,39,1,39,13,43,1,43,9,47,2,47,10,51,1,5,51,55,1,55,10,59,2,59,6,63,2,6,63,67,1,5,67,71,2,9,71,75,1,75,6,79,1,6,79,83,2,83,9,87,2,87,13,91,1,10,91,95,1,95,13,99,2,13,99,103,1,103,10,107,2,107,10,111,1,111,9,115,1,115,2,119,1,9,119,0,99,2,0,14,0');
	foreach ($data as $idx => $value) {
		$data[$idx] = intval($value);
	}
	$i = 0;
	$data[1] = $a1;
	$data[2] = $a2;
	$opcode = 0;
	while (($opcode!=99) && ($i<count($data))) {
		$opcode = $data[$i];
		if ($opcode!=99) {
			$a = $data[$data[$i+1]];
			$b = $data[$data[$i+2]];
			$offset = $data[$i+3];
			$result = 0;
			if ($opcode==1) $result = $a+$b;
			if ($opcode==2) $result = $a*$b;
			$data[$offset] = $result;
			if ($log==true) echo "op=$opcode a=$a b=$b o=$offset r=$result\n";
			$i = $i + 4;
		}
	}
	return $data[0];
}

// Part 1 : 12 and 2 (show the changes because they're few lines)

$result  = calculate(12,2,true);

// Part 2 : find i,j that results in 19690720

for ($i=0;$i<100;$i++) {
	for ($j=0;$j<100;$j++) {
		$temp = calculate($i,$j);
		if ($temp==19690720) {
			echo ("i=$i, j=$j\n");
			die();
		}
	}
}
?>