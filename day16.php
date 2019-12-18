<?php
function text2array($text) {
    $a = array();
    $len = strlen($text);
    for ($i=0;$i<$len;$i++) {
        $a[$i] = ord(substr($text,$i,1))-0x30;
    }
    return $a;
}

function process() {
    global $input,$output,$inputlen;
    $output = array();
    $pattern = array();
    $pattern_values = [0,1,0,-1];
    $pattern_pos = 0;
    $pattern_counter = 0;

    $half = intdiv($inputlen,2);

    // sorry, simply too tired (and a bit lazy) to figure out the trick and simplify the first half
    for ($j=0;$j<$half;$j++){
        $level=$j+1;
        $pattern_max = $level;
        $pattern_pos = ($level==1) ? 1 : 0;
        $pattern_counter = ($level==1)? $pattern_max : $pattern_max-1;
        $pattern_value = $pattern_values[$pattern_pos];
        $output[$j] = 0;
        for ($i=0;$i<$inputlen;$i++) {
            if ($pattern_value!=0) $output[$j] += ($pattern_value==1) ? $input[$i] : (0-$input[$i]);
            //echo $input[$i].' x '.$pattern_value.' ';
            $pattern_counter--;
            if ($pattern_counter==0) {
                $pattern_counter = $pattern_max;
                $pattern_pos++;
                if ($pattern_pos>3) $pattern_pos = 0;
                $pattern_value = $pattern_values[$pattern_pos];
            }
        }

        $output[$j] = abs($output[$j]) % 10;
    }
    $total =0;
    for ($j=$inputlen-1;$j>=$half;$j--) {
        $total += $input[$j];
        $output[$j] = $total % 10;
    }
}

function process2() {
    global $input,$output,$inputlen,$offset;
    $output = array();
    $total =0;
    for ($j=$inputlen-1;$j>=$offset-2;$j--) {
        $total += $input[$j];
        $output[$j] = $total % 10;
    }
}

$code = str_replace(array(' ',"\n"),'',file_get_contents(__DIR__ .'/inputs/16.txt'));
//$code = '123456789012';

$input = text2array($code);
$inputlen = strlen($code);
$output = array();

for ($phase=1;$phase<101;$phase++) {
    process();
    echo "After phase $phase : ";
    for ($i=0;$i<8;$i++) echo $output[$i];
    echo "\n";
    $input = $output;
}

$code = str_pad($code,strlen($code)*10000,$code);
$offset = intval(substr($code,0,7));
echo "offset=$offset\n";
$input = text2array($code);
$inputlen = strlen($code);
$output = array();

for ($phase=1;$phase<101;$phase++) {
    process2();
    echo ".";
    $input = $output;
}
echo "\n";
for ($i=$offset;$i<$offset+8;$i++) echo $output[$i];

?>