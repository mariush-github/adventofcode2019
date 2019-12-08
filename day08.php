<?php

// see https://adventofcode.com/2019/day/8

$filename = __DIR__ . '/inputs/08.txt';
$width = 25;
$height= 6;

$layersize = $width*$height;

$image = file_get_contents($filename);
// there's a new line character which would screw up the count, so use floor to get the layers.
$lcount =  intval(floor(strlen($image) / $layersize)); 
echo "Image read: ".strlen($image).' bytes, '.$lcount.' layers of '.$width.'x'.$height.' pixels.'."\n";

$layers = array(); 

// Part 1

$layer = -1;
$zeroes = $layersize+1; // a number that's larger than the maximum number of zeroes possible in layer

for ($l=0;$l<$lcount;$l++) {
    $offset = $l*$layersize;
    $data = substr($image,$offset,$layersize);
    $digits = array(0,0,0,0,0,0,0,0,0,0);
    for ($i=0;$i<$layersize;$i++) {
        $digit = ord(substr($data,$i,1)) - 48;
        $digits[$digit]++;
    }
    $layers[$l] = $digits;
    if ($digits[0]<$zeroes) { 
        $zeroes = $digits[0];
        $layer = $l;
    }
}
echo "The layer with the least 0 digits is $layer. It has $zeroes zero digits.\n";
for ($l=0;$l<$lcount;$l++) {
    if ($layers[$l][0]==$zeroes) echo "Layer $l: ".json_encode($layers[$l])."\n";
}
// part 2

function getPixel($x,$y) {
    global $width;
    global $height;
    global $lcount;
    global $image;
    
    for ($i=0;$i<$lcount;$i++) {
        $offset = $y*$width+$x + ($i*$width*$height);
        $digit = substr($image,$offset,1);
        if ($digit!='2') return $digit;
    }
}
echo "\n";
for ($j=0;$j<$height;$j++) {
    for ($i=0;$i<$width;$i++) {
        echo (getPixel($i,$j)==0) ? ' ': '#';
    }
    echo "\n";
}

?>