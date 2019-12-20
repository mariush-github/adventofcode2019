<?php

// see https://adventofcode.com/2019/day/19

include 'intCode.php';
$code = file_get_contents(__DIR__ .'/inputs/19.txt');
$total = 0;
$map = [];

function scan($from_x,$from_y,$width,$height) {
global $map,$total,$code;

for ($j=$from_y;$j<$height;$j++) {
    if (isset($map[$j])==false) $map[$j] = [];
    for ($i=$from_x;$i<$width;$i++) {
            if (isset($map[$j][$i])==false) {
                $robot = new intCodeComputer($code);
                $robot->configure(array('pause_output'=>TRUE,'debug'=>FALSE));
                
                $robot->run();
                $robot->input($i);
                $robot->input($j);
                $value = $robot->output();
                $robot->run();
                $total = $total + $value;
                //echo "\n$i,$j $value\n";
                $map[$j][$i]=$value;
            }
        }
    }
}

$part = 2; // set this to 1 to solve part 1 of the problem

$map = [];
$total = 0;
if ($part==1) {
    scan(0,0,50,50);
    echo "Part 1 answer: $total\n";
    die();
}

function scan_line($offset_x,$offset_y) {
    global $code;
    $s = '';
    $robot = new intCodeComputer($code);
    $robot->configure(array('pause_output'=>TRUE,'debug'=>FALSE));
    $value=0;
    $i=$offset_x;
    $found_ones = false;
    $continue=true;
    while ($continue==true){
        $robot->run();
        $robot->input($i);
        $robot->input($offset_y);
        $value = $robot->output();
        $robot->reset();
        if ($value==0) {
            if ($found_ones==true) {
                $continue=false;
            } else {
                $s.='0';
            }
        } else {
            $found_ones=true;
            $s.='1';
        }
        if ($continue==true) $i++;
    }
    $i=$offset_x;
    while (substr($s,0,1)=='0') {$i++;$s=substr($s,1);}
    return array($i,strlen($s));
}



$lines = array();
$lines[1499] = [0,0];
for ($j=1500;$j<1601;$j++) $lines[$j] = scan_line($lines[$j-1][0],$j);
$y=1600;
while(1==1) {
    $found=true;
    $ship_xs = $lines[$y][0];
    $ship_xf = $ship_xs + 99;
    $j=0;
    while ($j<100 && $found==true) {
        $line_y = $y-$j;
        $line_xs = $lines[$line_y][0];
        $line_xf = $line_xs + $lines[$line_y][1]-1;
        $line_len = $lines[$line_y][1];
        if ($line_len<100) {
            $found=false;
        } else {
            $a=false;
            $b=false;
            if ($ship_xs >= $line_xs && $ship_xs <= $line_xf) $a=true;
            if ($ship_xf >= $line_xs && $ship_xf <= $line_xf) $b=true;
            if ($a==false || $b==false) $found=false;
        }
        $j++;
    }
    if ($found==true) {
        echo "Top left corner is at ($ship_xs,$line_y).\n";
        die();
    }else {
        $y++;
        $lines[$y]= scan_line($lines[$y-1][0]-2,$y);
        echo str_pad($y,6,' ',STR_PAD_LEFT).': '.str_pad($lines[$y][0],6,' ',STR_PAD_LEFT).'-'.str_pad(($lines[$y][1]+$lines[$y][0]-1),6,' ',STR_PAD_LEFT).' ('.$lines[$y][1].')'."\n";
    }

}