<?php

// see https://adventofcode.com/2019/day/19

include 'intCode.php';
$code = file_get_contents(__DIR__ .'/inputs/21.txt');

$robot = new intCodeComputer($code);
$robot->configure(array('pause_output'=>FALSE,'debug'=>FALSE));
$robot->run();
$commands = [];
$commands[0] = 'NOT C J';
$commands[1] = 'NOT B T';
$commands[2] = 'OR T J';
$commands[3] = 'NOT A T';
$commands[4] = 'OR T J';
$commands[5] = 'AND D J';
$commands[6] = 'WALK';

for ($i=0;$i<count($commands);$i++) {
    for ($j=0;$j<strlen($commands[$i]);$j++) {
        $c = substr($commands[$i],$j,1);
        $robot->input(ord($c));
    }
    $robot->input(10);
}
$last = 0;
while ($robot->hasOutput()==true) {
    $out = $robot->output();
    echo chr($out);
    $last= $out;
}

echo "total=$last\n";


$robot = new intCodeComputer($code);
$robot->configure(array('pause_output'=>FALSE,'debug'=>FALSE));
$robot->run();
$commands = [];
$commands[0] = 'NOT E T';
$commands[1] = 'NOT H J';
$commands[2] = 'AND T J';
$commands[3] = 'NOT J J';
$commands[4] = 'NOT C T';
$commands[5] = 'AND T J';
$commands[6] = 'NOT B T';
$commands[7] = 'OR T J';
$commands[8] = 'NOT A T';
$commands[9] = 'OR T J';
$commands[10] = 'AND D J';
$commands[11] = 'RUN';

for ($i=0;$i<count($commands);$i++) {
    for ($j=0;$j<strlen($commands[$i]);$j++) {
        $c = substr($commands[$i],$j,1);
        $robot->input(ord($c));
    }
    $robot->input(10);
}
$last = 0;
while ($robot->hasOutput()==true) {
    $out = $robot->output();
    echo chr($out);
    $last= $out;
}

echo "total=$last\n";
?>