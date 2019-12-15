<?php

$chemicals = array();
$f = array(); // formulas
$extras = array();

$text = file_get_contents(__DIR__ .'/inputs/14.txt');
$text = str_replace(chr(0x0D).chr(0x0A),chr(0x0A),$text);
$text = str_replace(', ',',',$text);
$text = str_replace(' => ',',',$text);
$textlines = explode(chr(0x0A),$text);
$lines = array();
foreach ($textlines as $line) {
    if (trim($line)!='') {
        $items = explode(',',$line);
        foreach ($items as $idx=>$item) { $t = explode(' ',$item); $items[$idx]= array($t[1],intval($t[0])); }
        array_push($lines,$items);
    }
}
// sort formulas

$continue = true;
while ($continue) {
    $continue = false;
    for ($i=0;$i<count($lines)-1;$i++) {
        $flip = false;
        $c1 = count($lines[$i]);
        $c2 = count($lines[$i+1]);
        if ($c1==$c2) { 
            if ($lines[$i+1][0][0]=='ORE' && $lines[$i][0][0]!='ORE') $flip=true;
        } else {
            if ($c1>$c2) $flip=true;
        }
        if ($flip==true) {
            $temp = $lines[$i];
            $lines[$i] = $lines[$i+1];
            $lines[$i+1] = $temp;
            $continue = true;
        }
    }
}
for ($i=0;$i<count($lines);$i++) echo json_encode($lines[$i])."\n";
// populate the chemicals array
foreach ($lines as $line) {
    foreach ($line as $item) {
        if (isset($chemicals[$item[0]])==false) $chemicals[$item[0]] = array('level'=>-1,'quantity'=>0);
    }
}
$chemicals['ORE']['level'] = 0;
// determine levels for each chemical
$continue = true;
while ($continue) {
    $continue=false;
    foreach ($lines as $line) {
        $maxlevel = 0;
        $all_have_level = true;
        for ($i=0;$i<count($line)-1;$i++) {
            $name = $line[$i][0];
            if ($chemicals[$name]['level']==-1) {
                $all_have_level = false;
            } else {
                if ($maxlevel < $chemicals[$name]['level']) $maxlevel = $chemicals[$name]['level'];
            }
        }
        $name = $line[count($line)-1][0];
        if ($all_have_level==true) $chemicals[$name]['level'] = $maxlevel+1;
        echo "\n set $name to level ".($maxlevel+1)."";
    }
    foreach ($chemicals as $chemical) if ($chemical['level']==-1) $continue=true;
}
foreach ($lines as $line) {
    $chem_name = $line[count($line)-1][0];
    $chem_qty  = $line[count($line)-1][1];
    $chemicals[$chem_name]['quantity'] = $chem_qty;
    for ($i=0;$i<count($line)-1;$i++) {
        $f[$chem_name][$line[$i][0]] = $line[$i][1];
    }
    
    echo "\n";
    foreach ($line as $item) {
        echo '['.$item[0].':'.$item[1].'] ('.$chemicals[$item[0]]['level'].':'.$chemicals[$item[0]]['quantity'].') ';
    }

}

function display($name) {
    global $chemicals, $f;
    echo $name.': ';
    foreach ($f[$name] as $item => $qty) echo $item.':'.($qty).' ';
    echo "\n";
}
function expand($name) {
    global $chemicals, $f,$extras;
    $maxlevel = 0;
    foreach ($f[$name] as $item => $value) {
        if ($chemicals[$item]['level']>$maxlevel) $maxlevel = $chemicals[$item]['level'];
    }
    //echo $name.': maxlevel= '.$maxlevel.' ';
    if ($maxlevel<2) return false;
    for ($level = $maxlevel;$level>=2;$level--){
        foreach ($f[$name] as $item =>$item_amount) {
            if ($chemicals[$item]['level']==$level && $item_amount!=0){
                //echo "expand=$item (level=$level) ";
                $qty_needed = $item_amount;
                //echo "qty_orig=$qty_needed qty_extra=".$extras[$item]." ";
                // were some of these made by a previous conversion?
                if ($extras[$item]>0) {
                    if ($extras[$item]<=$qty_needed) {
                        $qty_needed = $qty_needed - $extras[$item];
                        $extras[$item] = 0;
                    } else {
                        $extras[$item] = $extras[$item]-$qty_needed;
                        $qty_needed = 0;
                    }
                }
                //echo "qty_aextra=$qty_needed ";
                if ($qty_needed>0) {
                    $min_order = $chemicals[$item]['quantity'];
                    $batches = intdiv($qty_needed , $min_order);
                    if ($qty_needed % $min_order > 0) $batches++;
                    $qty_total = $batches * $min_order;
                    $qty_extra = $qty_total - $qty_needed;
                    //echo "order_min=$min_order order_batches=$batches order_total=$qty_total order_rest=$qty_extra ";
                    $extras[$item] += $qty_extra;
                    foreach ($f[$item] as $chem_name =>$chem_count) {
                        if (isset($f[$name][$chem_name])==false) $f[$name][$chem_name] = 0;
                        $f[$name][$chem_name] += $batches * $chem_count;
                        //echo ' +'.$chem_name.':'.$chem_count.' ';
                    }
                    //echo "\n";
                }
                $f[$name][$item] = 0;
                return true;
            }
        }
    }
}

function calculate_ore($number=1) {
    global $chemicals, $extras, $f;
    foreach ($chemicals as $chem => $data) $extras[$chem]=0;
    $original_formula = $f['FUEL'];
    foreach ($f['FUEL'] as $item_n => $item_c) {
        $f['FUEL'][$item_n] = $number * $item_c;
    }
    //var_dump($extras);
    //echo "\n";
    //display('FUEL');
    $result = expand('FUEL');
    while($result==true) {
        //display('FUEL');
        $result = expand('FUEL');
    }
    //display('FUEL');
    $ore = 0;
    foreach($f['FUEL'] as $chem_name => $chem_count ) {
        if ($chemicals[$chem_name]['level']==1) {
            $min_order = $chemicals[$chem_name]['quantity'];
            $batches = intdiv($chem_count,$min_order);
            if ($chem_count % $min_order > 0) $batches++;
            $ore += ($batches * $f[$chem_name]['ORE']); 
        }
    }
    $f['FUEL'] = $original_formula;
    return $ore;

}
$ore = calculate_ore(1);
echo "\nTotal ore: $ore \n";

// Part 2 : guess count for ore amount
// 
// Sort of brute force , start by calculating in huge steps, 
// then refine using smaller and smaller increments 
// works reasonable fast.
//

$min_ore = $ore;
$min_cnt = 1;
$inc = 10000000;
while ($inc>=1) {
    $i=0;
    $ore = 0;
    echo "ranging with $inc\n";
    $number_base = $min_cnt;
    while ($ore<1000000000000) {
        $number = $number_base + ($i*$inc);
        $ore = calculate_ore($number);
        echo $number.': '.$ore."\n";
        if ($ore > $min_ore && $ore < 1000000000000){
            $min_cnt = $number;
            $min_ore = $ore;
        }
        $i++;

    }
    if ($inc!=1) {
        $inc = intdiv($inc,10);
        if ($inc==0) $inc=1;
    } else {
        $inc = 0;
    }
    

}

?>