<?php

// see https://adventofcode.com/2019/day/11

// Only part 1 implemented.

$moons = array(
    array(13,9,5),
    array(8,14,-2),
    array(-5,4,11),
    array(2,-6,1)
);
$velocities = array(array(0,0,0),array(0,0,0),array(0,0,0),array(0,0,0));

function dump(){ 
    global $moons;
    global $velocities;
    for ($i=0;$i<count($moons);$i++) {
        $pot = abs($moons[$i][0]) + abs($moons[$i][1]) + abs($moons[$i][2]);
        $kin = abs($velocities[$i][0]) + abs($velocities[$i][1]) + abs($velocities[$i][2]);
        echo $i.'.'.' x='.str_pad($moons[$i][0],5,' ',STR_PAD_LEFT).' y='.str_pad($moons[$i][1],5,' ',STR_PAD_LEFT).' z='.str_pad($moons[$i][2],5,' ',STR_PAD_LEFT);
        echo ' vx='.str_pad($velocities[$i][0],5,' ',STR_PAD_LEFT).' vy='.str_pad($velocities[$i][1],5,' ',STR_PAD_LEFT).' vz='.str_pad($velocities[$i][2],5,' ',STR_PAD_LEFT);
        echo ' pot='.str_pad($pot,5,' ',STR_PAD_LEFT).' kin='.str_pad($kin,5,' ',STR_PAD_LEFT).' tot='.str_pad($pot*$kin,5,' ',STR_PAD_LEFT);
        echo "\n";
    }
    echo "\n\n";
}
function makehash() {
    global $moons;
    global $velocities;
    $hash = '';
    for ($j=0;$j<4;$j++) {
        for ($i=0;$i<3;$i++) $hash .= str_pad(dechex($moons[$j][$i]+32767),4,'0',STR_PAD_LEFT);
        //for ($i=0;$i<3;$i++) $hash .= str_pad(dechex($moons[$j][$i]),4,'0',STR_PAD_LEFT);
    }
    return $hash;
}
function minihash() {
    global $moons;
    global $velocities;
    $hash = '';

    for ($i=0;$i<3;$i++) $hash .= chr($moons[0][$i]+127);
    return $hash;
}

$minihash = minihash();
$hash = makehash();
$hashes = array();
$hashes[$hash] = 0;

$part = 1;

// change variable above for answer to first part

$continue = true;
$temp = 0;

$step = 0;
while ($continue==true) {
    $step++;
    foreach ($moons as $moonId => $moon) {
        $nv = array(0,0,0);
        foreach ($moons as $id=>$moon2) {
            if ($id!=$moonId) {
                $nv[0] += ($moons[$id][0]>$moons[$moonId][0]) ? 1 : 0;
                $nv[0] += ($moons[$id][0]<$moons[$moonId][0]) ? -1 : 0;
                $nv[1] += ($moons[$id][1]>$moons[$moonId][1]) ? 1 : 0;
                $nv[1] += ($moons[$id][1]<$moons[$moonId][1]) ? -1 : 0;
                $nv[2] += ($moons[$id][2]>$moons[$moonId][2]) ? 1 : 0;
                $nv[2] += ($moons[$id][2]<$moons[$moonId][2]) ? -1 : 0;
            }
        }
        for ($i=0;$i<3;$i++) $velocities[$moonId][$i] += $nv[$i];
    }
    foreach ($moons as $moonId => $moon) {
        for ($i=0;$i<3;$i++) $moons[$moonId][$i] += $velocities[$moonId][$i];
    }
    //echo "Step $step:\n";
    //dump();
    if ($part == 1) {
        if ($step==1000) $continue=false;
    }

}

?>