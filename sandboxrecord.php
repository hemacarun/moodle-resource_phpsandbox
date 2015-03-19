<?php

global $CFG, $DB, $USER;
@require_once($CFG->dirroot . '../../config.php');
error_reporting(0);

$code = optional_param('code', null, PARAM_RAW);
$setup_code = optional_param('setup_code', null, PARAM_TEXT);
$prepend_code = optional_param('prepend_code', null, PARAM_TEXT);
$append_code = optional_param('append_code', null, PARAM_TEXT);
$options = optional_param('options', null, PARAM_TEXT);
$whitelist = optional_param('whitelist', null, PARAM_TEXT);
$blacklist = optional_param('blacklist', null, PARAM_TEXT);
$definitions = optional_param('definitions', null, PARAM_TEXT);
$selectedrecordid = optional_param('selectedid',null, PARAM_INT);


function converting_objecttoarray($jsondata) {

    $jsondata = json_decode($jsondata);
    // if(is_array( $jsondata)){
    $jsondata = json_decode(json_encode($jsondata), true);
    $jsondata = array_filter($jsondata);
    // }    
    return $jsondata;
}


if ($selectedrecordid) {
    // fetching record
    $selecteddata = $DB->get_record('phpsandbox_records', array('id' => $selectedrecordid));

    echo $selecteddata->codecontent;
} else {
    // inserting record
    if (isset($_POST['save']) ) {

    $code = json_decode($code);
    $setup_code = json_decode($setup_code);
    $prepend_code = json_decode($prepend_code);
    $append_code = json_decode($append_code);
    $options = converting_objecttoarray($options);
    $whitelist = converting_objecttoarray($whitelist);
    $blacklist = converting_objecttoarray($blacklist);
    $definitions = converting_objecttoarray($definitions);     


        $data = array(
            'code' => $code,
            'setup_code' => $setup_code,
            'prepend_code' => $prepend_code,
            'append_code' => $append_code,
            'options' => null,
            'whitelist' => $whitelist,
            'blacklist' => $blacklist,
            'definitions' => $definitions
        );
    }
    
    
    $template = optional_param('template', null, PARAM_TEXT);
    $instanceid = optional_param('instanceid',null, PARAM_INT);
    $name = optional_param('title', null, PARAM_TEXT);

// saving code to as a phpsandbox record
    $temp = new stdClass();
    $temp->name = $name;
    $temp->sandboxinstanceid = $instanceid;
    $temp->userid = $USER->id;
    $temp->codecontent = json_encode($data);
    $temp->template = $template;
    $temp->timecreated = time();

    $recordid = $DB->insert_record('phpsandbox_records', $temp);

    if (!is_siteadmin())
        $count = $DB->count_records_sql("select COUNT(*) from {phpsandbox_records} where userid=$USER->id");
    else
        $count = $DB->count_records_sql("select COUNT(*) from {phpsandbox_records}");
    if ($count == 1)
        echo $count;
    else
        echo $recordid;
//echo $recordid;
}
//$phpsandbox=$DB->get_records('phpsandbox');
// print_object($phpsandbox);
?>
