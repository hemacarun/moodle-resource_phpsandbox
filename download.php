<?php

require_once('../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE;

$record_id = required_param('recordid', PARAM_INT);    // Course Module ID
$record_info = $DB->get_record('phpsandbox_records', array('id' => $record_id));
$size = filesize($record_info->codecontent);
$mime_type = "application/octet-stream";

//ob_clean(); 
//flush();
//turn off output buffering to decrease cpu usage
@ob_end_clean();

// required for IE, otherwise Content-Disposition may be ignored
if (ini_get('zlib.output_compression'))
    ini_set('zlib.output_compression', 'Off');
header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment;');
header("Content-Transfer-Encoding: binary");
header('Accept-Ranges: bytes');

// multipart-download and download resuming support
if (isset($_SERVER['HTTP_RANGE'])) {
    list($a, $range) = explode("=", $_SERVER['HTTP_RANGE'], 2);
    list($range) = explode(",", $range, 2);
    list($range, $range_end) = explode("-", $range);
    $range = intval($range);
    if (!$range_end) {
        $range_end = $size - 1;
    } else {
        $range_end = intval($range_end);
    }

    $new_length = $range_end - $range + 1;
    header("HTTP/1.1 206 Partial Content");
    header("Content-Length: $new_length");
    header("Content-Range: bytes $range-$range_end/$size");
} else {
    $new_length = $size;
    header("Content-Length: " . $size);
}


$r = (json_decode($record_info->codecontent));
//($secondlayer = (json_decode($r)));
echo $r->code;
?>