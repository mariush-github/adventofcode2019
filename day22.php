<?php

$cards = [];
$cardsCnt = 10;

for ($i=0;$i<10;$i++) {
    $cards[$i]=$i;
}
function cards_display() {
    global $cards;
    echo json_encode($cards)."\n";
}
function cards_deal() {
    global $cards,$cardsCnt;
    $c = [];
    for ($i=$cardsCnt-1;$i>=0;$i--) array_push($c,$cards[$i]);
    $cards = $c;
}

function cards_cut($amount) {
    global $cards, $cardsCnt;
    $a=0;$b=0;$c=0;$d=0;
    if($amount>0) {
        $a=$amount;$b=$cardsCnt-1;
        $m=0;$n=$amount-1;
    }
    if ($amount<0) {
        $a = $cardsCnt+$amount; $b = $cardsCnt-1;
        $m = 0; $n=$cardsCnt+$amount-1;
    }
    $c = [];
    for ($i=$a;$i<=$b;$i++) array_push($c,$cards[$i]);
    for ($i=$m;$i<=$n;$i++) array_push($c,$cards[$i]);
    $cards = $c;
}
function cards_dealInc(int $amount) {
    global $cards,$cardsCnt;
    $c = $cards;
    $m=0;
    $i = 0;
    $m = intval(0);

    for ($i=0;$i<$cardsCnt;$i++) {
        $c[$m]=$cards[$i];
        $m=$m+$amount;
        if ($m>$cardsCnt) $m=$m-intval($cardsCnt);
    }
    $cards = $c;
}
//

$data = file_get_contents(__DIR__ .'/inputs/22.txt');
$data = str_replace(chr(0x0D).chr(0x0A),chr(0x0A),$data);

$data = str_replace('deal with increment','dealinc',$data);
$data = str_replace('deal into new stack','deal',$data);
$ops = explode(chr(0x0A),$data);
$cards = [];
$cardsCnt = 10007;
for ($i=0;$i<10007;$i++) array_push($cards,$i);

foreach($ops as $line) {
    $op = trim($line);
    if ($op!='') {
        $keywords = explode(' ',$op);
        $value = 0;

        if (count($keywords)>1) $value = intval($keywords[1]);
        $code = $keywords[0];
        echo $code.'::'.$value."\n";
        if ($code=='dealinc') cards_dealInc($value);
        if ($code=='deal') cards_deal();
        if ($code=='cut') cards_cut($value);

    }
    //var_dump($op);
}
for ($i=0;$i<10007;$i++) {
    if ($cards[$i]==2019) echo "The card is at offset $i\n";
}
?>