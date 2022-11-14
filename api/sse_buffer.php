<?php
date_default_timezone_set("America/New_York");
header("Cache-Control: no-store");
header("Content-Type: text/event-stream");

$counter = 0;
$roomNumber = (preg_match('~^[0-9]{5}$~', $_GET['room'])) ? $_GET['room'] : null;
if(!$roomNumber) {
    exit('invalid room number');
} else {
    $roomNumber = 5-(42073-intval($roomNumber)); // Yields a room number 1-5
}
while (true) {
    $lines = file('buffer_' . $roomNumber);
    // First we want to parse all lines in the file before jumping into ping mode
    if($counter == 0) {
        $numberlines = count($lines);
        for($i = 0; $i < $numberlines; $i++) {
            parseLine($lines[$i], $i);
            $counter++;
        }
        // Let the client know where we are in case they disconnected
        currentIDEvent($counter);
    }
    // We are now at the end of the file and scanning for new incoming lines
    if(!empty($lines[$counter])) {
        parseLine($lines[$counter], $counter);
        $counter++;
    }
    ob_end_flush();
    flush();
    if (connection_aborted()) break;
    usleep( 250000 ); // 0.25 Seconds is lowest we can go before consuming too much CPU
}

function parseLine($inputline, $counter) {
    $encodedLine = preg_replace('~\"(rock|paper|scissors|null)\"~', '\"$1\"', $inputline);
    $encodedLine = preg_replace('~\"p(1|2)cast\"\:\s\\\"(rock|paper|scissors)\\\"~', '"p$1cast": "$2"', $encodedLine);
    preg_match('~\"gamelog\"\:\s(.*)\}$~', $encodedLine, $jsonbuffer);
    $line = json_decode($encodedLine, true);
    $output = "id: {$counter}\n";
    $output .= "event: {$line['event']}\n";
    $output .= 'data: {"msg": "'.$line['msg'].'", "time": "'.$line['time'].'", "context": "'.$line['context'].'", ';
    $output .= '"p1score": "'.$line['p1score'].'", "p2score": "'.$line['p2score'].'"';
    if($line['event'] == "round_winner") {
        $output .= ', "p1cast": "' . $line['p1cast'] . '", "p2cast": "' . $line['p2cast'] . '"';
    }
    $output .= ', "gamelog": ' . $jsonbuffer[1];
    $output .= '}';
    $output .= "\n\n";
    echo $output;
}

function currentIDEvent($counter) {
    $output = "id: {$counter}\n";
    $output .= "event: current_id\n";
    $output .= 'data: {"msg": "'.$counter.'"}';
    $output .= "\n\n";
    echo $output;
}