<?php

// Advent of code 2019 - Day 20 
// map manually modified to give each pair a single letter because didn't want to code it



require __DIR__ .'/path-finder/vendor/autoload.php';

use Letournel\PathFinder\Algorithms;
use Letournel\PathFinder\Converters\Grid\ASCIISyntax;
use Letournel\PathFinder\Core;
use Letournel\PathFinder\Distances;

function map_get($i,$j){
	global $map;
    if ($map[$j][$i]=='#') return 1;
    if ($map[$j][$i]==' ') return 1;
    if ($map[$j][$i]=='.') return 0;
    return ord($map[$j][$i]);
}

function map_wall($i,$j,$extra=''){
	global $map;
	$map[$j][$i]='#';
}

function map_display() {
    global $map,$width,$height;
    for ($j=0;$j<=$height;$j++) {
        for ($i=0;$i<=$width;$i++) { echo $map[$j][$i]=='#' ? chr(0xDB) :$map[$j][$i]; }
        echo "\n";
    }
}


function map_createmapdata() {
    global $map,$width,$height,$mapdata;
    $mapdata = '';
    for ($j=0;$j<=$height;$j++) {
        for ($i=0;$i<=$width;$i++) { $mapdata .= $map[$j][$i]; }
        $mapdata .= "\n";
    }
    // path finder expects x as wall, but we have actual x as teleport point
    // so do the replaces in the actual search function  
    //$mapdata = str_replace('#','x',$mapdata); 
    //$mapdata = str_replace('.',' ',$mapdata);

}


function map_optimize(&$map) {
    global $width,$height;
    $continue=true;

    while($continue) {
        $continue = false;
        for ($j=1;$j<=$height;$j++) {
            for ($i=1;$i<=$width;$i++) {
                $p22 = map_get($i,$j);
                if ($p22==0) {
                    $p11 = map_get($i-1,$j-1); $p12 = map_get($i,$j-1); $p13 = map_get($i+1,$j-1);
                    $p21 = map_get($i-1,$j); $p23 = map_get($i+1,$j);
                    $p31 = map_get($i-1,$j+1); $p32 = map_get($i,$j+1); $p33 = map_get($i+1,$j+1);
                }
                if ($p22==0 && $p11==1 && $p12==1 && $p21==1 && $p31==1 && $p32==1) {     // --
                    map_wall($i,$j,1);$continue=true;$p22=1;                             // -+
                }                                                                         // --

                if ($p22==0 && $p12==1 && $p13==1 && $p23==1 && $p32==1 && $p33==1) {     // --
                    map_wall($i,$j,1);$continue=true;$p22=1;                             // +-
                }                                                                         // --
                if ($p22==0 && $p11==1 && $p12==1 && $p13==1 && $p21==1 && $p23==1) {     // 
                    map_wall($i,$j,1);$continue=true;$p22=1;                             // ---
                }                                                                         // -+-
                
                if ($p22==0 && $p31==1 && $p32==1 && $p33==1 && $p21==1 && $p23==1) {     // 
                    map_wall($i,$j,1);$continue=true;$p22=1;                             // -+-
                }                                                                         // ---
            }
        }
        //echo '.';
    }
}
function map_fix() {
    global $map,$width,$height,$points;
    $points = [];
    for ($j=0;$j<=$height;$j++) {
        for ($i=0;$i<=$width;$i++) {
            $c = $map[$j][$i];
            if ($c==' ') {$c='#';$map[$j][$i]='#';}
            if ($c=='@' || $c=='!') $points[$c] = [$i,$j];
            if ($c!='#' && $c!='.' && $c!='@' && $c!='!'){
                if (isset($points[$c])==false) {
                    $points[$c] = [$i,$j];
                } else {
                    $mode=0;
                    if ($c>="A" && $c<="]") $mode=1;
                    if ($c>='a' && $c<='}') $mode=2;
                    if ($mode==1) $c = chr(ord($c)+32);
                    if ($mode==2) $c = chr(ord($c)-32);
                    $points[$c]= [$i,$j];
                    $map[$j][$i]=$c;
                }
            }
        }
    }
}

function nodes_create($name,$x,$y) {
    global $nodes;
    $node = new stdClass;
    $node->name = $name;
    $node->x=$x;
    $node->y=$y;
    $nr = array_push($nodes,$node);
    return ($nr-1);
}
function pool_from_node($id){
    global $nodemap,$nodes,$map,$nodepools;
    
    $pool = [];
    $pool[$id] = true;
    //echo "\n new pool with first member $id\n";
    $x = $nodes[$id]->x;
    $y = $nodes[$id]->y;
    $queue = [[$x,$y,$id]];

    while (count($queue)>0) {
        $queue2 = [];
        foreach ($queue as $point) {
            $x = $point[0];
            $y = $point[1];
            $dirs = [[-1,0],[0,-1],[0,1],[1,0]];
            $counter=0;
            foreach ($dirs as $dir) {
                $m = $x+$dir[0];
                $n = $y+$dir[1];
                //echo "check ($m,$n) ";
                if ($m>-1 && $n>-1) {
                    $c = $map[$n][$m];
                    //echo "n=$c ";
                    if ($c!='#' && $nodemap[$n][$m]==-1){
                        //echo " create node ";
                        $nid = nodes_create($c,$m,$n);
                        //echo $nid;
                        $nodemap[$n][$m] = $nid;
                        if ($c=='.') array_push($queue2,[$m,$n,$nid]);
                        if ($c!='.') {$pool[$nid]=true;  } //echo ' +'.$nid;}
                    }
                }
            }
        }
        $queue = $queue2;
    }
    //echo "\n";
    //var_dump($pool);
    array_push($nodepools,$pool);
}

function nodes_calculate() {
    global $map,$height,$width,$nodemap,$distmap,$nodepools;
    $nodes=[];
    for ($j=0;$j<=$height;$j++){
        $nodemap[$j]=[];
        for ($i=0;$i<=$width;$i++) {
            $nodemap[$j][$i] = -1;
        }
    }
    for ($j=0;$j<=$height;$j++){
        for ($i=0;$i<=$width;$i++) {
            $c = $map[$j][$i];
            if ($c!='#' && $c!='.' && $nodemap[$j][$i] == -1){
                $id = nodes_create($c,$i,$j);
                $nodemap[$j][$i] = $id;
                pool_from_node($id);
            }
        }
    }
    //var_dump($nodepools);

}

function pools_display() {
    global $nodepools,$nodes;
    echo "Found ".count($nodepools)." node 'pools':\n";
    for ($i=0;$i<count($nodepools);$i++) {
        echo $i.': ';
        foreach ($nodepools[$i] as $id => $value) echo $nodes[$id]->name.' ';
        echo "\n";
    }
}

function solve($map, $algorithm)
{
    $converter = new ASCIISyntax();
    $grid = $converter->convertToGrid($map);
    $matrix = $converter->convertToMatrix($map);
    $source = $converter->findAndCreateNode($matrix, ASCIISyntax::IN);
    $target = $converter->findAndCreateNode($matrix, ASCIISyntax::OUT);
    
    $algorithm->setGrid($grid);
    //$starttime = microtime(true);
    $path = $algorithm->computePath($source, $target);
    //$endtime = microtime(true);
    
    if($path instanceof Core\NodePath) {
        $points = [];
        foreach ($path as $index => $value) {
            $coords = explode(',',$index); array_push($points,[intval($coords[0]),intval($coords[1])]);
        }
        $total = 2;
        //echo ' '.json_encode($points[0]).' ';
        for ($i=1;$i<count($points);$i++) {
            //echo json_encode($points[$i]).' ';
            // diagonal shortcuts not allowed
            $diff_x = abs($points[$i-1][0]-$points[$i][0]);
            $diff_y = abs($points[$i-1][1]-$points[$i][1]);
            if ($diff_x==0 || $diff_y==0) {
                // horizontal or vertical move
                $total++; //echo ' +1 ';
            } else {
                $total = $total+2; //echo ' +2 '; // diagonal move, add the corner
            }
        } 
        return ($total-2); // don't include the start point and end point 
        //echo "Path found in " . floor(($endtime - $starttime) * 1000) . " ms\n";
        //echo $converter->convertToSyntaxWithPath($grid, $path);
    } else {
        return 0;
    }
}

function distance_between_nodes($node_from,$node_to) {
    global $mapdata;

    $map = $mapdata;
    // don't really care about direction, which is input and which is output
    $map = str_replace([$node_from,$node_to],['>','<'],$map);
    // replace wall and path characters with the ones the path finder code wants
    $map = str_replace(['#','.'],['X',' '],$map); 

    //echo $map;

    $distance = new Distances\Euclidean();
    //echo "Solving SSP with Dijkstra:\n";
    $result = solve($map, new Algorithms\ShortestPath\Dijkstra($distance));
    //echo "\n\n\n";
    return $result;
}

function routes_calculate(){
    global $nodepools,$nodes,$routes;
    $coords = [];
    $routes = [];
    // create the teleporter routes , distance of 1
    for ($i=0;$i<count($nodepools);$i++) {
        foreach ($nodepools[$i] as $id => $value) {
            $c = $nodes[$id]->name;
            $coords[$c] = [$nodes[$id]->x,$nodes[$id]->y];
            $d = '';
            if ($c!='@' && $c!='!') {
                $mode=0;
                if ($c>="A" && $c<="]") $mode=1;
                if ($c>='a' && $c<='}') $mode=2;
                if ($mode==1) $d = chr(ord($c)+32);
                if ($mode==2) $d = chr(ord($c)-32);
                $routes[$c.$d] = 1;
                $routes[$d.$c] = 1;
            }
        }
        $combinations = [];
        foreach  ($nodepools[$i] as $j => $v1) {
            foreach ($nodepools[$i] as $k => $v2) {
                if ($j!=$k) {
                    $a = $nodes[$j]->name;
                    $b = $nodes[$k]->name;
                    $pair = ($a<$b) ? $a.$b : $b.$a;
                    $routes[$pair] = 0;
                }
            }
        }
    }
    echo "Route count: ".count($routes)."\n";
    foreach ($routes as $pair => $distance) {
        if ($distance==0) {
            $n1 = substr($pair,0,1);
            $n2 = substr($pair,1,1);
            echo "compute distance between $n1 ".json_encode($coords[$n1])." $n2 ".json_encode($coords[$n2]).':';
            $d = distance_between_nodes($n1,$n2);
            
            $routes[$n1.$n2] = $d;
            $routes[$n2.$n1] = $d;  
            echo $d."\n";
        }
    }
} 


function route_length($route) {
    global $routes;
    if (strlen($route)<2) return 0;
    $total = 0;
    for ($i=1;$i<strlen($route);$i++) {
        $total = $total + $routes[substr($route,$i,1).substr($route,$i-1,1)];
    }
    return $total;
}
function route_process($route) {
    global $routes;
    $lastnode = substr($route,strlen($route)-1,1);
    if ($lastnode=='!') {
        echo "Found route: $route length=".route_length($route)."\n";
        return;
    }
    // echo "Searching paths with last node $lastnode \n";
    $paths = [];
    foreach ($routes as $potentialRoute => $value) {
        // echo "testing route $potentialRoute \n";
        if (substr($potentialRoute,1,1)==$lastnode) {
            // echo " yes ";
            $newnode = substr($potentialRoute,0,1);
            // echo " newnode= [$newnode] searching for $newnode in [$route]";
            $result = strpos($route,$newnode);
            if ($result===FALSE) {
                // echo "possible route: $newnode \n";
                array_push($paths,$newnode);
            }
        }
    }
    if (count($paths)<1) return;
    foreach ($paths as $id => $c) {
        $newRoute = $route.$c;
        if ($c>="A" && $c<="]") $newRoute .= chr(ord($c)+32);
        if ($c>='a' && $c<='}') $newRoute .= chr(ord($c)-32);
        //if ($mode==1) $d = chr(ord($c)+32);
        //if ($mode==2) $d = chr(ord($c)-32);
        route_process($newRoute);
    }

}
function get_routes_from_node($name) {
    global $nodepools,$nodes;
    foreach ($nodepools as $nodepool) {
        $found = false;
        foreach ($nodepool as $nodeid => $value) {
            if ($nodes[$nodeid]->name==$name) $found=true;
        }
        if ($found==true) {
            $nodeList = [];
            foreach ($nodepool as $nodeid => $value) {
                if ($nodes[$nodeid]->name!=$name) array_push($nodeList,$nodes[$nodeid]->name.$name);
            }
            return $nodeList;
        }
    }
    return [];
}
$data = file_get_contents(__DIR__ .'/inputs/20.txt').chr(0x0A);
$data = str_replace(chr(0x0D).chr(0x0A),chr(0x0A),$data);
$width = strpos($data,chr(0x0A));
$height = intdiv(strlen($data),$width+1)-1;
echo "Map size: $width x $height\n";
$data = str_replace(chr(0x0A),'',$data);

$map = [];
for ($j=0;$j<=$height;$j++) {
    $map[$j] = [];
    for ($i=0;$i<$width;$i++) {
        $map[$j][$i] = substr($data,$j*$width+$i,1);
    }
}
$width--;

$points = [];
$nodes=[];
$nodemap = [];
$nodepools = [];
$routes = [];
$mapdata = '';

map_optimize($map);
map_display();
map_fix();
map_display();
map_createmapdata();
//echo $mapdata;
nodes_calculate();
routes_calculate();
//var_dump($nodepools);
//var_dump(get_routes_from_node('{'));

// Part 2 (not working)
//recroute_process();


class clsHistory {
    public $route;
    public $level;
    private $lastNode;
    private $history;

    public function __construct() {
       $this->route = '';
       $this->level = 0;
       $this->lastNode = [];
       $this->history = []; 
    }
    public function getLastNode($level) {
        return $this->lastNode[$level];
    }
    public function setLastNode($level,$value){
        $this->lastNode[$level] = $value; 
    }
    public function hasPath($level,$path){
        if (isset($this->history[$level])==false) return false;
        if (strpos($this->history[$level],'['.$path.']')===FALSE) return false;
        return true;
    }
    public function addPath($level,$path) {
        if (isset($this->history[$level])==false) $this->history[$level]='';
        $this->history[$level] .= '['.$path.']';
    }
}



function recroute_process() {
    
    global $routes;
    $route_inner = 'S[]IJKLMdNtUfvwXycPQZbeghor';
    $route_outer = 'BCDEFGHsquxjzp{ORTVWYiklmn}';
    $route_possible = '';

    $unit = new clsHistory();
    $unit->route = '@';
    $unit->level = 0;
    $unit->setLastNode(0,'@');
    
    $units = [];
    
    array_push($units,$unit);
    $debug = true;
    while (count($units)>0) {

        $unit = array_shift($units);
        $level = $unit->level;
        $lastNode = $unit->getLastNode($level);

        if ($level==0) $route_possible = '!'.$route_inner;
        if ($level!=0) $route_possible = $route_inner.$route_outer;

        if ($debug==true) echo "route=$unit->route level=$level last=$lastNode \n";
    
        if ($lastNode=='!') {
            echo "Found route: $route->route length=".route_length($route->route)." ";
            return;
        }
        //if ($level > 4) die();
        $paths = [];
        $routes_list = get_routes_from_node($lastNode);
        if ($debug==true) echo "paths_all=".json_encode($routes_list).' ';
        //var_dump($routes_list);
        foreach ($routes_list as $route) {
            $route_from = substr($route,0,1);
            $route_to   = substr($route,1,1);
            $can_use = true;
            if (strpos($route_possible,$route_from)===false) $can_use = false;
            for ($i=$level;$i>=0;$i--) {
                if ($unit->hasPath($i,$route)==true) $can_use = false;
            }
            if ($unit->hasPath($level,$route) == true) $can_use = false;
            if ($can_use==true) array_push($paths,[$route_from,$route_to]);
        }
        if ($debug==true) echo "paths_ok=".json_encode($paths).' ';
        //var_dump($paths);
        //die();
        foreach ($paths as $path) {
            $route_from = $path[0];
            $route_to   = $path[1];

            $level_change = 0;
            if ($route_from == '!') $level_change = -1;
            if (strpos($route_outer,$route_from)!==FALSE) $level_change = -1;
            if (strpos($route_inner,$route_from)!==FALSE) $level_change = +1;

            $route_jump = '';
            if ($route_from>="A" && $route_from<="]") $route_jump = chr(ord($route_from)+32); 
            if ($route_from>='a' && $route_from<='}') $route_jump = chr(ord($route_from)-32);
            if ($debug==true) echo "\n +path: from=$route_from to=$route_to jump=$route_jump level_change=$level_change ";
            $unit_new = clone $unit; 
            // add this route (ex I@ ) to this level's history, so if we come back to 
            // this level, we won't go again this route in infinite loop
            $unit_new->addPath($unit_new->level,$route_from.$route_to);
            $unit_new->route .= $route_from.$route_jump;
            // set the new starting point for the level we're gonna jump to 
            $unit_new->setLastNode($unit_new->level + $level_change,$route_jump);
            // set the level
            $unit_new->level = $unit_new->level+$level_change;
            // push into queue 
            array_push($units,$unit_new);

        }
        if ($debug==true) echo "\n";
    }
}

?>