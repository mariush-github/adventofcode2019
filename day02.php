<?php

// see https://adventofcode.com/2019/day/2

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