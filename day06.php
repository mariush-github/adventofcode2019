<?php

// see https://adventofcode.com/2019/day/6


$items_orbits = array();
$items_unique = array();

$filename = __DIR__ . '/inputs/06.txt';

$lines = explode(chr(0x0A),str_replace(chr(0x0D).chr(0x0A),chr(0x0A),file_get_contents($filename)));
if (count($lines)<1) die('No entries read. Maybe filename not found?');
foreach ($lines as $line) {
    if (trim($line)!='') {
        $parts = explode(')',$line);
        // making a list of unique names to make it easier later and show count on screen
        if (isset($items_unique[$parts[0]])==FALSE) $items_unique[$parts[0]] = TRUE;
        if (isset($items_unique[$parts[1]])==FALSE) $items_unique[$parts[1]] = TRUE;
        // store the relation (x orbits y) in array
        $items_orbits[$parts[1]] = $parts[0];
    }
}

function counter($name) {
    global $items_orbits;
 
    $list = chain($name);

    $nr = count($list);
    if ($nr==0) echo "$name does not orbit anything.";
    if ($nr >0) echo "$name directly orbits ".$list[0];
    if ($nr >1) echo ' and indirectly orbits '.($nr-1).' planets.';
    echo "\n";
    return $nr;
}

function chain($name) {
    global $items_orbits;
    $list = array();
    if (isset($items_orbits[$name])==FALSE) return $list;
    $temp = $items_orbits[$name];
    while ($temp!='') {
        array_push($list,$temp);
        $temp = isset($items_orbits[$temp])==TRUE ? $items_orbits[$temp] : '';
    }
    return $list;
}

$total = 0;

echo "There are ".count($items_unique)." planets and ".count($items_orbits)." orbits.\n";

foreach ($items_unique as $name => $value) {
    $result = counter($name);
    $total += $result;
}
echo 'Total orbits: '.$total."\n";

$o1 = chain('YOU');
$o2 = chain('SAN');
echo "Orbit chain of YOU:".json_encode($o1)."\n";
echo "Orbit chain of SAN:".json_encode($o2)."\n";
$offset1 = count($o1)-1;
$offset2 = count($o2)-1;
while ($o1[$offset1]==$o2[$offset2]) { 
    $offset1--;
    $offset2--;
}
echo "Orbital jumps required: ".($offset1+$offset2+2)." ($offset1 + $offset2 + 2)\n";

?>