<?php

$text = '#..##'.
'#.#..'.
'#...#'.
'##..#'.
'#..##';

// $text = '....#'.
// '#..#.'.
// '#..##'.
// '..#..'.
// '#....';

// $text = '.....'.
// '.....'.
// '.....'.
// '#....'.
// '.#...';

$map = [];
$hashes = [];


function map_display(){
    global $map;
    echo "\n";
    for ($j=0;$j<5;$j++) {
        for ($i=0;$i<5;$i++) {
            echo $map[$j][$i];
        }
        echo "\n";
    }
}

function map_cycle() {
    global $map;
    //echo "\n";
    $sum = [];
    for ($j=0;$j<5;$j++) {
        $sum[$j] = [];
        for ($i=0;$i<5;$i++) {
            $total = 0;
            if ($i!=0) $total += $map[$j][$i-1];
            if ($i!=4) $total += $map[$j][$i+1];
            if ($j!=0) $total += $map[$j-1][$i];
            if ($j!=4) $total += $map[$j+1][$i];
            $sum[$j][$i] = $total;
            //echo $total;
        }
        //echo "\n";
    }
    for ($j=0;$j<5;$j++) {
        for ($i=0;$i<5;$i++) {
            if ($map[$j][$i]==1 && $sum[$j][$i] != 1) {
                $map[$j][$i] = 0;
            } else {
                if ($map[$j][$i]==0 && $sum[$j][$i]>0 && $sum[$j][$i]<=2) $map[$j][$i] = 1;
            }
        }
    }
}

function map_hash(){
    global $map;
    $map_signature = 0;
    $offset = 0;
    for ($j=0;$j<5;$j++) {
        for ($i=0;$i<5;$i++) {
            $map_signature += $map[$j][$i] * (2**$offset);
            $offset++;
        }
    }
    return $map_signature;
}

function map_load() {
    global $map,$text;
    $offset = 0;
    for ($j=0;$j<5;$j++) {
        $map[$j] = [];
        for ($i=0;$i<5;$i++) {
            $map[$j][$i] = substr($text,$offset,1)=='#' ? 1 : 0;
            $offset++;
        }
    }
}


map_load();
map_display();
$hash = map_hash();

$hashes = [];
$hashes[$hash] = 0;
$counter = 1;
while (1==1) {
    map_cycle();
    $hash = map_hash();
    if (isset($hashes[$hash])==true) die('Hash '.$hash.' found at cycle '.$hashes[$hash]);
    $hashes[$hash]=$counter;
    $counter++;    
}
?>