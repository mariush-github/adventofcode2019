<?php

// Advent of Code 2019 Day 18 - Incomplete (Part 1 working for small maps)
//
// Contains all needed to do Part 1 but solves using a brute force / recursive function that generates
// all combinations of paths (it was written just to test the overall code). 
// This works for small maps but not big maps (too many combinations)
//

include 'clsMap.php';

function map_optimize(&$map) {
    global $width,$height;
    $continue=true;

    while($continue) {
        $continue = false;
        for ($j=1;$j<$height;$j++) {
            for ($i=1;$i<$width;$i++) {
                $p22 = $map->get($i,$j);
                if ($p22==0) {
                    $p11 = $map->get($i-1,$j-1); $p12 = $map->get($i,$j-1); $p13 = $map->get($i+1,$j-1);
                    $p21 = $map->get($i-1,$j); $p23 = $map->get($i+1,$j);
                    $p31 = $map->get($i-1,$j+1); $p32 = $map->get($i,$j+1); $p33 = $map->get($i+1,$j+1);
                }
                if ($p22==0 && $p11==1 && $p12==1 && $p21==1 && $p31==1 && $p32==1) {     // --
                    $map->set($i,$j,1);$continue=true;$p22=1;                             // -+
                }                                                                         // --

                if ($p22==0 && $p12==1 && $p13==1 && $p23==1 && $p32==1 && $p33==1) {     // --
                    $map->set($i,$j,1);$continue=true;$p22=1;                             // +-
                }                                                                         // --
                if ($p22==0 && $p11==1 && $p12==1 && $p13==1 && $p21==1 && $p23==1) {     // 
                    $map->set($i,$j,1);$continue=true;$p22=1;                             // ---
                }                                                                         // -+-
                
                if ($p22==0 && $p31==1 && $p32==1 && $p33==1 && $p21==1 && $p23==1) {     // 
                    $map->set($i,$j,1);$continue=true;$p22=1;                             // -+-
                }                                                                         // ---

            }
        }
        //echo '.';
    }
}

function helper_buildChain($id){
    global $nodes;
    $l=[$id];
    $n = $nodes[$id]['p'];
    while ($n!=0) {array_push($l,$n); $n = $nodes[$n]['p'];}
    if ($id!=0) array_push($l,0);
    return $l;
}
function helper_commonPoint($chain1,$chain2) {
    $commonPoint = 0;
    $m = count($chain1);
    $n = count($chain2);
    $elements = (($m<$n) ? $m : $n);
    $m=$m-$elements;
    $n=$n-$elements;
    for ($i=0;$i<$elements;$i++) {
        if ($chain1[$m]==$chain2[$n]) return $chain1[$m];
        $m++;$n++;
    }
    $counter = 1;
}
function helper_extractDepends($chain,$commonPoint) {
    global $nodes;
    if (count($chain)<1) return '';
    if ($chain[0]==$commonPoint) return '';
    $list = '';
    $i=1;
    while ($i<count($chain)) {
        $c = strtoupper(chr($nodes[$chain[$i]]['n']));
        if ($c>='A' && $c<='Z') $list .= $c;
        if ($chain[$i]==$commonPoint) return $list;
        $i++;
    }
    return $list;
}
function calculate_distances() {
    global $map, $distances,$nodes,$nodesByName, $width,$height,$x,$y;
    // put the "space" positions and our objectives into a separate map, to compute the distances from @
    // initially all are -1, and we mark our @ position with distance 0
    // then loop: 
    // * put points with distance n in an array, 
    // * for each point with distance n, look for the neighboring points
    // * if those points don't have distance assigned, give them distance +1 and queue them in array
    // * if more than 1 neighboring points received distance, then start point is a junction (we'll keep it at end)
    // * job done, now repeat with the points that were queued into array, until that array is empty (no more points)
    // When done, we'll have [letter]-[space]-[space]-[junction]-[space]-[@]
    // We want to filter out spaces as they're not important, and 
    // * point letter->parent to junction 
    // * point junction->parent to @
    // The spaces can then be removed from array
    $dist = [];

    for ($j=0;$j<=$height;$j++) {
        $dist[$j] = array();
        for ($i=0;$i<=$width;$i++) {
            $dist[$j][$i] = -1;//array('d'=>-1); 
        }
    }
    $nodes= array();
    
    $dist[$y][$x] = 0;
    $points = [[$x,$y,0]];
    $id = 0;
    //d = distance, n = name, p = parent , j = is junction
    $nodes[0] = [ 'd'=>0, 'n'=>0x40, 'p'=>0,'j'=>1, 'x'=>$x,'y'=>$y];
    while (count($points)>0) {
        $points2 = [];
        foreach ($points as $point) {
            $i = $point[0];
            $j = $point[1];
            $parent = $point[2];
            $dirs = [[-1,0],[0,-1],[0,1],[1,0]];
            $counter=0;
            foreach ($dirs as $dir) {
                $m = $i+$dir[0];
                $n = $j+$dir[1];
                $v = $map->get($m,$n);
                 
                if ($v!=1 && $dist[$n][$m]==-1){ // this is a space, and not already used by other routes
                    $id++;
                    $nodes[$id] = ['d'=>$nodes[$parent]['d']+1, 'n'=>$v, 'p'=>$parent, 'j'=>($v>0) ? 1: 0,'x'=>$m,'y'=>$n];
                    $dist[$n][$m] = $dist[$j][$i]+1;
                    array_push($points2, array($m,$n,$id));
                    $counter++;
                }
            }
            if ($counter>1) { $nodes[$parent]['j']=1; } 
        }
        $points = $points2;
    }
    // take out spaces and point ->parent to proper places
    for ($i=0;$i<count($nodes);$i++) {
        $process_node = false;
        if ($nodes[$i]['n']!=0 && $nodes[$i]['n']!=0x40) $process_node=true;
        if ($nodes[$i]['n']==0 && $nodes[$i]['j']==1) $process_node=true;
        if ($process_node) {
            $parent = $nodes[$i]['p'];
            while ($nodes[$parent]['j']==0) $parent = $nodes[$parent]['p'];
            $nodes[$i]['p'] = $parent;
        }
    }
    // filter out the non-junction points as they're no longer important
    $n = [];
    for ($i=0;$i<count($nodes);$i++) {
        if ($nodes[$i]['j']==1) $n[$i]=$nodes[$i];
        if ($nodes[$i]['j']==1 && $n[$i]['n']!=0) $nodesByName[chr($n[$i]['n'])] = $i;
    }
    $nodes = $n;
    //var_dump($nodes);
    // precompute the distances between two points ex from b to F 
    // * memorize all the junctions points from "b" to "@" - ex b ->ju100->x->ju200->ju300->@ 
    // * memorize all the junctions points from "F" to "@" - ex F ->ju250->ju200->ju300->@
    // * find the point where the two path intersect - ex ju200  (worst case scenario they intersect at @)
    // * add distances together - ex b-ju200 + F-ju200 
    // * memorize all named junctions and store it with distance
    // ** in example move only possible if we have key for "x" because x is between b and ju200
    $distances = [];
    foreach ($nodes as $i => $node1) { // ($i=1;$i<count($nodes);$i++) {
        foreach ($nodes as $j =>$node2) { // ($j=1;$j<count($nodes);$j++) {
            $goodpair = true;
            if ($i==0) $goodpair = false; // first node can't be @
            if ($i==$j) $goodpair = false;
            if ($nodes[$i]['n']==0 || $nodes[$j]['n']==0 ) $goodpair = false;
            if ($nodes[$i]['n']>=0x41 && $nodes[$i]['n']<=0x5A) $goodpair = false; // no A..Z
            if ($nodes[$j]['n']>=0x41 && $nodes[$j]['n']<=0x5A) $goodpair = false; // no A..Z
            if ($goodpair==true ) { 
                //echo $i.':'.$j."\n";
                $name1 = $nodes[$i]['n'];
                $name2 = $nodes[$j]['n'];
                $label1 = chr($name1).chr($name2);
                $label2 = (chr($name2) !='@') ? chr($name2).chr($name1) : '';

                if ( isset($distances[$label1])==false ) { // two named junctions we did not compute already
                    $c1 = helper_buildChain($i);
                    $c2 = helper_buildChain($j);
                    //echo "c1=".json_encode($c1)."\nc2=".json_encode($c2)."\n";
                    $commonPoint = helper_commonPoint($c1,$c2);
                    //echo "com=$commonPoint\n";
                    $length = 0;
                    if ($commonPoint == $i) $length = $nodes[$j]['d']-$nodes[$i]['d'];
                    if ($commonPoint == $j) $length = $nodes[$i]['d']-$nodes[$j]['d'];
                    if ($commonPoint != $i && $commonPoint != $j) {
                        $length += $nodes[$i]['d'] - $nodes[$commonPoint]['d'];
                        $length += $nodes[$j]['d'] - $nodes[$commonPoint]['d'];
                    }
                    //echo "length=$length\n";
                    $dependson = '';
                    $dependson .= helper_extractDepends($c1,$commonPoint);
                    $dependson .= helper_extractDepends($c2,$commonPoint);
                    //echo "depends=$dependson\n";
                    $distances[$label1] = [$length,$dependson];
                    if ($label2!='') $distances[$label2] = [$length,$dependson];
                }
            }
        }
    } 
}


function helper_processResult($path) {
    global $result,$resultlen,$distances; 
    $text = '';
    $text .= $path.' ';
    $sum = 0;
    for ($i=1;$i<strlen($path);$i++) {
        $code = strtolower(substr($path,$i,1).substr($path,$i-1,1));
        $text .= ' +'.$code.':'.$distances[$code][0];
        $sum += $distances[$code][0];
    }
    $text.= ' = '.$sum."\n";
    if ($resultlen==0 || ($sum<$resultlen)) {
        $resultlen = $sum;
        $result = $path;
        echo $text;
    }

}
function path_recursive($path,$level=0) {
    //if ($level>4) die();
    global $distances,$nodes, $nodesByName,$allroutes;
    $debug = false;
    // look for all the nodes that were not visited already (not listed in path)
    // for each node, check to see if we previously visited all the nodes the node depends on, otherwise cant be used
    $lastNode = strtolower(substr($path,strlen($path)-1,1));
    $explore = [];
    if ($debug) echo "path = $path lastnode=$lastNode\n";
    foreach ($nodesByName as $nodeKey =>$nodeId) {
        if ($nodeKey!='@' && ord($nodeKey)>=0x61 && ord($nodeKey)<=0x7A && strpos($path,strtoupper($nodeKey))===FALSE) { // we still have to visit this node
            if ($debug) echo "Trying ".$nodeKey.$lastNode.': ';
            $route = $distances[$nodeKey.$lastNode]; // ex a@  to find route from a to @
            $length  = $route[0];
            $depends = $route[1];
            $can_reach = true;
            if (strlen($depends)>0) {
                if ($debug) echo "(depends on ".$depends.') ';
                for ($i=0;$i<strlen($depends);$i++) {
                    $c = substr($depends,$i,1);
                    if (strpos($path,$c)===FALSE) {
                        if ($debug) echo '[-'.$c.']';                        
                        $can_reach = false;
                    } else {
                        if ($debug) echo '[+'.$c.']';
                    }
                }
            }
            if ($can_reach==true) array_push($explore,$nodeKey); // array($nodeKey,$length));
            //echo ($can_reach==true)? 'OK' : 'FAIL';
            if ($debug) echo "\n";
        }
    }
    if (count($explore)==0) { // no more nodes to add, so we collected all keys, add this key to all routes
        //array_push($allroutes,$path); // array($path,$length,$level+1));
        helper_processResult($path);
    } else {

        foreach ($explore as $i => $ex) {
            path_recursive($path.chr(ord($explore[$i][0])-32),$level+1); // ,$length+$explore[$i][1],$level+1);
        }
    }
}

$data = file_get_contents(__DIR__ .'/inputs/18_test1.txt').chr(0x0A);
$data = str_replace(chr(0x0D).chr(0x0A),chr(0x0A),$data);
$width = strpos($data,chr(0x0A));
$height = intdiv(strlen($data),$width+1)-1;
echo "width=$width height=$height\n";
$data = str_replace(chr(0x0A),'',$data);

//$map = new clsMap(82,81,0);
$map = new clsMap($width,$height,0);
$map->char(0,chr(0x20)); // space
$map->char(1,chr(0xDB)); // full fill

for ($j=0;$j<=$height;$j++){
    for ($i=0;$i<=$width;$i++) {
        $c = substr($data,$j*$width+$i,1);
        $v = 0;
        if ($c=='#' || $c=='.') {
            $v = ($c=='#') ? 1 : 0;
            $map->set($i,$j,$v);
        } else {
            if ($c=='@') {
                $x = $i;$y = $j;
            } else {
                $v = ord($c);
                $map->set($i,$j,$v);
            }
        }
    }
}

$distances = array();
$nodes = array();
$nodesByName = array();
$allroutes = array();

$map->display();
map_optimize($map);
$map->display();
calculate_distances();
//var_dump($distances);
$result = '';$resultlen=0;
path_recursive('@',0);

?>