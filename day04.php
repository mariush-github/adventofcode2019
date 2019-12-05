<?php
$start = microtime(true);
// see https://adventofcode.com/2019/day/4

// going for the brute force method
//
// 
// It is a six-digit number.
// The value is within the range given in your puzzle input.
// Two adjacent digits are the same (like 22 in 122345).
// Going from left to right, the digits never decrease; they only ever increase or stay the same (like 111123 or 135679).

// Other than the range rule, the following are true:

// 111111 meets these criteria (double 11, never decreases).
// 223450 does not meet these criteria (decreasing pair of digits 50).
// 123789 does not meet these criteria (no double).

$min = 234208;
$max = 765869;
$digits = array();
$count = 0;
$count2 = 0;

function part2($text) {
    global $count2;
    $t = $text;
    $chars = array();
    $segments = array();
    $idx = 0;
    for ($i=0;$i<6;$i++) $chars[$i] = substr($t,$i,1);
    $segments[0] = $chars[0];
    for ($i=1;$i<6;$i++) {
        if ($chars[$i-1]==$chars[$i]) {
            $segments[$idx] .= $chars[$i];
        } else {
            $idx++;
            $segments[$idx] = $chars[$i];
        }
    }
    $hasDouble = false;
    $hasMoreThanDouble = false;
    foreach ($segments as $value) {
        if (strlen($value)==2) $hasDouble=true;
        if (strlen($value) >2) $hasMoreThanDouble = true;
    }
    if ($hasDouble==true) {
        $count2++;
    }
}

for ($i=$min;$i<=$max;$i++){
    $text = ''.$i;
    for ($j=0;$j<6;$j++) $digits[$j] = intval(substr($text,$j,1));
    $decrease = false;
    $hasdouble = false;
    for ($j=1;$j<6;$j++) {
        if ($digits[$j-1]>$digits[$j]) $decrease=true;
        if ($digits[$j-1]==$digits[$j]) $hasdouble=true;
    }
    if (($decrease==false) && ($hasdouble==true)) {
        $count++;
        //echo $i."\n";
        part2($i);
    }
}
echo 'part 1 solution: '.$count."\n";
echo 'part 2 solution: '.$count2;

$finish = microtime(true);
echo "\n".($finish-$start);
?>