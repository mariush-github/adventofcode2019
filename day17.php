<?php

include 'intCode.php';

$code = file_get_contents(__DIR__ .'/inputs/17.txt');

$robot = new intCodeComputer($code);
$robot->configure(array('pause_output'=>TRUE,'debug'=>FALSE));
$robot->run();
$i =-1;
$j =0;
$width=0;
$height=0;
$m = array();
$m[$j]=array();
$orientation = 'U';
$x = 0;
$y = 0;
while ($robot->pauseReason=='output') {
    $c = $robot->output();
    if ($c==35 || $c==46) { $i++;$m[$j][$i] = $c;if ($i>$width) $width=$i; if ($j>$height) $height=$j; }
    if ($c==10) {
        $j++;$i=-1;
        $m[$j] = array();
    }
    if ($c==ord('^')) { $i++;$x=$i;$y=$j; $orientation = 'U'; $m[$j][$i]=46;}
    if ($c==ord('v')) { $i++;$x=$i;$y=$j; $orientation = 'D'; $m[$j][$i]=46;}
    if ($c==ord('>')) { $i++;$x=$i;$y=$j; $orientation = 'L'; $m[$j][$i]=46;}
    if ($c==ord('<')) { $i++;$x=$i;$y=$j; $orientation = 'R'; $m[$j][$i]=46;}
    echo chr($c);
    $robot->run();
}

echo "width=$width height=$height x=$x y=$y o=$orientation\n";
$total = 0;
function m_get($y,$x) {
    global $m;
    if (isset($m[$y][$x])==false) return 0;
    return $m[$y][$x];
}
for ($j=1;$j<$height;$j++) {
    for ($i=1;$i<$width;$i++) {
        if (m_get($j,$i)==35) {
            if (m_get($j,$i-1)==35 && m_get($j-1,$i)==35 && 
                m_get($j,$i+1)==35 && m_get($j+1,$i)==35 ) {
                    echo "$i,$j: ".($i*$j)."\n";
                    $total = $total + $i*$j;
            }
        }
    }
}
echo "Total: $total\n";

$moves = [];
// x,y to check , which way it turns, new orientation
$moves['U'] = [ [-1,+0,'L','L'],[+1,+0,'R','R'] ];
$moves['L'] = [ [+0,+1,'L','D'],[+0,-1,'R','U'] ];
$moves['D'] = [ [+1,+0,'L','R'],[-1,+0,'R','L'] ];
$moves['R'] = [ [+0,-1,'L','U'],[+0,+1,'R','D'] ];
$continue = true;

// L followed by length encoded as ASCII character 0x60+length (1='a', 2='b',...)
$sequence = '';

while ($continue) {
    $continue=false;
    $result = FALSE;
    foreach ($moves[$orientation] as $move) {
        if (m_get($y+$move[1],$x+$move[0])==35) $result = $move;
    }
    if ($result===FALSE) {
        $continue=FALSE;
    } else {
        $move = $result;
        $segment_length = 0;
        while (m_get($y+($segment_length+1)*$move[1],$x+($segment_length+1)*$move[0])==35){
            $segment_length++;
        }
        $x = $x + ($segment_length)*$move[0];
        $y = $y + ($segment_length)*$move[1];
        $orientation=$move[3];
        //echo $move[2].','.$segment_length."\n";
        $sequence .= $move[2].chr(0x60 + $segment_length);
        $continue=true;
        //echo "x=$x y=$y o=$orientation\n";
    }

}
$sequence_len = strlen($sequence);
$segment_max = intdiv($sequence_len,2);
// build a "dictionary" with words (multiples of 2 chars) and how often they occur in the string 
$combos = array();
for ($i=0;$i<$sequence_len-2;$i=$i+2) {
    $seq = substr($sequence,$i,2);
    if (isset($combos[$seq])==false) $combos[$seq] = 0;
    $combos[$seq]++; 
}
for ($l=2;$l<$segment_max;$l=$l+2) {
    foreach ($combos as $combo => $value) {
        if (strlen($combo)==$l) {
            for ($i=0;$i<$sequence_len-$l-2;$i=$i+2) {
                if (substr($sequence,$i,$l)==$combo) {
                    $seq = substr($sequence,$i,$l+2);
                    if (isset($combos[$seq])==false) $combos[$seq] = 0;
                    $combos[$seq]++;
                    $i = $i + strlen($seq)-2;
                } 
            }
        }
    }
}

function expand($text) {
    $s = $text;
    $s = str_replace('A',',A',$s);
    $s = str_replace('B',',B',$s);
    $s = str_replace('C',',C',$s);
    $s = str_replace('R',',R',$s);
    $s = str_replace('L',',L',$s);
    for ($i=0;$i<strlen($s);$i++){
        $c = substr($s,$i,1);
        if ($c>="a" && $c<="z") {
            $ascii = ord($c)-0x60;
            $s = str_replace($c,','.$ascii,$s);
        }
    }
    $s = trim($s,',');
    $s = str_replace(',,',',',$s).chr(10);
    return $s;
}
// throw out the words that show up only once as they won't help compress 
// also throw out sequences that are bigger than 20 characters
$dictionary = array();
foreach ($combos as $combo => $count) {
    $len = strlen(expand($combo));
    if ($count>1 && $len<=21) $dictionary[count($dictionary)] = array($combo,$count);
}

// sort dictionary by amount of savings we'd get
$continue = true;
while ($continue) {
    $continue=false;
    for ($i=0;$i<count($dictionary)-1;$i++) {
        $save1 = ($dictionary[$i][1]-1) * strlen($dictionary[$i][0]);
        $save2 = ($dictionary[$i+1][1]-1) * strlen($dictionary[$i+1][0]);
        if ($save1<$save2) {
            $temp = $dictionary[$i];
            $dictionary[$i] = $dictionary[$i+1];
            $dictionary[$i+1] = $temp;
            $continue = true;
        }
    }
}
//var_dump($dictionary);

function test_sequence($a,$b,$c,$sequence) {
    $s = $sequence;
    $s = str_replace($a,'A',$s);
    $s = str_replace($b,'B',$s);
    $s = str_replace($c,'C',$s);
    return $s;
}

$a_best = 0;
$b_best = 0;
$c_best = 0;
$s_best = '';
$l_best = $sequence_len;
for ($a =0;$a<count($dictionary);$a++) {
    for ($b=0;$b<count($dictionary);$b++) {
        for ($c=0;$c<count($dictionary);$c++) {
            if ($a!=$b && $b!=$c && $c!=$a) {
                $compressed = test_sequence($dictionary[$a][0],$dictionary[$b][0],$dictionary[$c][0],$sequence);
                if (strlen($compressed)<$l_best) {
                    $l_best = strlen($compressed);
                    $s_best = $compressed;
                    $a_best = $dictionary[$a][0];
                    $b_best = $dictionary[$b][0];
                    $c_best = $dictionary[$c][0];
                }
            }
        }
    }
}
echo "a=$a_best b=$b_best c=$c_best l=$l_best s=$s_best\n";

$robot = new intCodeComputer($code);
$robot->configure(array('pause_output'=>FALSE,'debug'=>FALSE));
$robot->set_value(0,2);
$robot->run();
//feed main routine
$data = expand($s_best);
echo "Sending main routine: $data \n"; 
for ($i=0;$i<strlen($data);$i++) {
    echo ord(substr($data,$i,1)).' ';
    $robot->input(ord(substr($data,$i,1)));
}
echo "\n";
$functions = array($a_best,$b_best,$c_best);
foreach ($functions as $f) {
    $data = expand($f);
    echo "Sending  routine $f: $data \n";
    for ($i=0;$i<strlen($data);$i++) {
        $c = substr($data,$i,1);
        echo ord($c).' ';
        $robot->input(ord($c));
    }
    echo "\n";
}
$robot->input(0x6E);
$robot->input(10);
$lastOutput = 0;
echo "Robot says: ";
while ($robot->hasOutput()==true) {
    $lastOutput =  $robot->output()."\n";
}
echo $lastOutput;
?>