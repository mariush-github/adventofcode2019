<?php
include 'intCode.php';

$history = '';
if (isset($_REQUEST['history'])==true) $history = base64_decode($_REQUEST['history']);
$command = '';
if (isset($_REQUEST['command'])==true) $command = trim($_REQUEST['command']);
if (strtolower($command)=='n') $command='north';
if (strtolower($command)=='s') $command='south';
if (strtolower($command)=='w') $command='west';
if (strtolower($command)=='e') $command='east';

// just to show a map on the screen to keep track of rooms easier
$x = 32;
$y = 32;
$x_min = 32;
$x_max = 32;
$y_min = 32;
$y_max = 32;

$records = [];
for ($j=0;$j<65;$j++) {
    $records[$j] = [];
    for ($i=0;$i<65;$i++) {
        $records[$j][$i] = '';
    }
}
if (isset($_REQUEST['info'])==true) {
    $info = json_decode(base64_decode($_REQUEST['info']),true);
    //var_dump($info);
    $x = $info['x'];
    $y = $info['y'];
    $x_min = $info['x_min'];
    $x_max = $info['x_max'];
    $y_min = $info['y_min'];
    $y_max = $info['y_max'];
    $entries = explode(',',$info['data']);
    $offset = 0;
    $records = [];
    for ($j=0;$j<65;$j++) {
        for ($i=0;$i<65;$i++) {
            $records[$j][$i] = $entries[$offset]; $offset++;
        }
    }
}

$code = file_get_contents(__DIR__ .'/inputs/25.txt');
$robot = new intCodeComputer($code);
$robot->configure(array('pause_output'=>FALSE,'debug'=>FALSE));



echo '<html><head><title>Game</title></head><body style="font-family:courier new;">';
echo '<div style="float:right;width:500px;">'.$history.'</div>';
echo '<p style="font-family:courier new;">';

if (isset($_REQUEST['submitted'])==true) {
    $data = base64_decode($_REQUEST['data']);
    $robot->import($data);
    echo "Sent: <strong>$command</strong>";
    if (strlen($command)>0) {
        for ($i=0;$i<strlen($command);$i++) {
            $robot->input(ord(substr($command,$i,1)));
        }
    }
    $robot->input(10);
} else {
    $robot->run();
}
$text = '';
while ($robot->hasOutput()==true) {
    $text .= chr($robot->output());
}
// let's try to parse a bit what the output says 
if (strpos($text,'==')!==FALSE) {
    $pos1 = strpos($text,'==');
    $pos2 = strpos($text,'==',$pos1+2);
    $room_name = trim(substr($text,$pos1+2,$pos2-$pos1-2));
    $directions = '';
    $directions .= (strpos($text,'- west')!==FALSE) ? 'W' : '-';
    $directions .= (strpos($text,'- north')!==FALSE) ? 'N' : '-';
    $directions .= (strpos($text,'- south')!==FALSE) ? 'S' : '-';
    $directions .= (strpos($text,'- east')!==FALSE) ? 'E' : '-';
    if ($command=='north') $y--;
    if ($command=='south') $y++;
    if ($command=='west') $x--;
    if ($command=='east') $x++;
    if ($x_min>$x) $x_min = $x;
    if ($x_max<$x) $x_max = $x;
    if ($y_min>$y) $y_min = $y;
    if ($y_max<$y) $y_max = $y;
    $records[$y][$x] = $directions.' '.$room_name;
    //echo "Room: $room_name Dir: $directions ".$records[$y][$x] ;
}

$text = str_replace(array(chr(0x0A),chr(0x0D)),"<br/>",$text);
$text = str_replace('<br/><br/>','<br/>',$text);
echo $text;
$text = str_replace('Command?','',$text);
$text = str_replace('<br/><br/>','<br/>',$text);
$history = $text.'<hr/>'.$history;

if ($robot->running==false) echo '<strong>You are dead! Hit Reload to continue.</strong>';

echo '</p>';

echo '<p style="font-size:9px;overflow-x:none;">';

for ($j=$y_min;$j<=$y_max;$j++) {
    for ($i=$x_min;$i<=$x_max;$i++) {
        if ($i==$x && $j==$y) echo '<span style="font-weight:bold;color:red;">';
        echo '[ <span title="'.$records[$j][$i].'">';
        echo str_replace(' ','&nbsp;',str_pad(substr($records[$j][$i],0,24),24,' '));
        echo '</span> ]';
        if ($i==$x && $j==$y) echo '</span>';
    }
    echo '<br/>';
}

echo '</p>';

$data = $robot->export();

// now let's prepare the info stuff to send it back

    $info = [];
    $x = $info['x'] = $x;
    $y = $info['y'] = $y;
    $info['x_min']=$x_min;
    $info['x_max']=$x_max;
    $info['y_min']=$y_min;
    $info['y_max']=$y_max;
    $entries = '';
    
    for ($j=0;$j<65;$j++) {
        for ($i=0;$i<65;$i++) {
            $entries .= ','.((isset($records[$j][$i])==true) ? $records[$j][$i] : '');
        }
    }
    $info['data'] = substr($entries,1);
    $info_encoded = json_encode($info);



echo '<form name="form" method="post" action="">
<input type="hidden" name="data" value="'.base64_encode($data).'" />
<input type="hidden" name="submitted" value="1" />
<p>Please type your next command:<br/>
Hint: north  east south west inv take [item] drop [item]</p>
<input type="text" name="command" value="" autofocus />
<input type="submit" name="go" value="Send" />
<input type="hidden" name="history" value="'.base64_encode($history).'" />
<input type="hidden" name="info" value="'.base64_encode($info_encoded).'" />
</form>';
echo '</body></html>';

?>