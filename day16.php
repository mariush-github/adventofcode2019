<?php
function text2array($text) {
    $a = array();
    $len = strlen($text);
    for ($i=0;$i<$len;$i++) {
        $a[$i] = ord(substr($text,$i,1))-0x30;
    }
    return $a;

}

$code = str_replace(array(' ',"\n"),'',file_get_contents(__DIR__ .'/inputs/16.txt'));
//$code = '12345678';

$part = 2; // change this to 1 or 2, to get answer to each part

if ($part == 2) $code = str_pad($code,strlen($code)*10000,$code);
$input = text2array($code);
$inputlen = strlen($code);
$output = array();
$pattern = array();
$pattern_values = [0,1,0,-1];
$pattern_pos = 0;
$pattern_counter = 0;

//echo json_encode($input)."\n";
for ($phase=1;$phase<101;$phase++) {
    for ($j=0;$j<$inputlen;$j++){
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
        //echo "\n";
        //echo '='.$output[$j];
        $output[$j] = abs($output[$j]) % 10;
        //echo '['.$output[$j]."]\n";

        //echo json_encode($pattern);
        //die();
    }
    echo "After phase $phase : ";
    for ($i=0;$i<8;$i++) echo $output[$i];
    echo "\n";
    $input = $output;
}
if ($part==2) {
    echo "Part 2: ";
    $offset = 0;
    for ($i=0;$i<7;$i++) $offset = $offset * 10 + $output[$i];
    echo "offset=".$offset." code=";
    for ($i=$offset-2;$i<$offset+12;$i++) echo $output[$i];
}
?>