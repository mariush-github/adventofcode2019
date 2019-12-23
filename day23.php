<?php

include 'intCode.php';
$code = file_get_contents(__DIR__ .'/inputs/23.txt');
$robots = [];
$inputQueues = [];
for ($i=0;$i<50;$i++) {
    $robots[$i] = new intCodeComputer($code);
    $robots[$i]->configure(array('pause_output'=>FALSE,'debug'=>FALSE));
    $robots[$i]->run();
    $robots[$i]->input($i);
    $inputQueues[$i]=[]; 
}

$part = 1; // change this between 1 and 2 for answers

$NAT_previous = [-1,-1];
$NAT_current  = [0,0];
while (1==1) {

    for ($i=0;$i<50;$i++) {
        while ($robots[$i]->hasOutput()==true){
            $address = $robots[$i]->output();
            $x = $robots[$i]->output();
            $y = $robots[$i]->output();
            if ($address==255 && $part==1) die("\nFirst packet sent to address 255 is $x $y\n");
            if ($address==255) { $NAT_current = [$x,$y]; echo "\n".json_encode($NAT_current)."\n";/* sleep(1);*/}
            if ($address>=0 && $address <50) array_push($inputQueues[$address],[$x,$y]);
            echo "\n".str_pad($i,6,' ',STR_PAD_LEFT).' -> '.str_pad($address,6,' ',STR_PAD_LEFT).' '.str_pad($x,6,' ',STR_PAD_LEFT).' '.str_pad($y,6,' ',STR_PAD_LEFT);
            
        }
        if ($robots[$i]->pauseReason=='input') {
            if (count($inputQueues[$i])==0) {
                $robots[$i]->input(-1);
            } else {
                $data = array_shift($inputQueues[$i]);
                $robots[$i]->input($data[0]);
                $robots[$i]->input($data[1]);
                echo "\n".str_pad($i,6,' ',STR_PAD_LEFT).' <- '.str_pad($data[0],6,' ',STR_PAD_LEFT).' '.str_pad($data[1],6,' ',STR_PAD_LEFT);
            }
        }
    }
    $is_idle = true;
    for ($i=0;$i<50;$i++) {
        if (count($inputQueues[$i])>0) $is_idle=false;
        if ($robots[$i]->hasOutput()==true) $is_idle=false;
    }
    if ($is_idle==true) {
        array_push($inputQueues[0],[$NAT_current[0],$NAT_current[1]]);
        if ($NAT_current[1]==$NAT_previous[1] && $part==2) die("\nNAT twice in row: ".$NAT_current[1]);
        $NAT_previous = $NAT_current;
        $NAT_current = [0,0];
    }
}

?>