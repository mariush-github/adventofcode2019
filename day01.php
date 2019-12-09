<?php

// see https://adventofcode.com/2019/day/1

// originally made in Excel just for fun, but adding it to Git for completeness

$numbers = explode(chr(0x0A),file_get_contents(__DIR__ . '/inputs/01.txt'));

// part 1
$total = 0;
foreach ($numbers as $number) {
    if (trim($number)!='') {
        $nr = intval($number);
        $total += floor($nr/3)-2;
    }
}
echo "First result: $total\n";

// part 2
$total = 0;
foreach ($numbers as $number) {
    if (trim($number)!='') {
        $nr = intval($number);
        $fuel = floor($nr/3)-2;
        while($fuel>0){
            $total += $fuel;
            $fuel = floor($fuel/3)-2;
        }
    }
}
echo "Second result: $total\n";
?>
