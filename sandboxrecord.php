<?php

global $CFG, $DB, $USER;
@require_once($CFG->dirroot . '../../config.php');
error_reporting(0);

$selectedrecordid = optional_param('selectedid',null, PARAM_INT);
if ($selectedrecordid) {
    // fetching record
    $selecteddata = $DB->get_record('phpsandbox_records', array('id' => $selectedrecordid));

    echo $selecteddata->codecontent;
} else {
    // inserting record
    if (isset($_POST['save']) ) {

            $code = $_POST['code'];
            $setup_code = isset($_POST['setup_code']) ? $_POST['setup_code'] : null;
            $prepend_code = isset($_POST['prepend_code']) ? $_POST['prepend_code'] : null;
            $append_code = isset($_POST['append_code']) ? $_POST['append_code'] : null;
            $options = isset($_POST['options']) ? $_POST['options'] : array();
            $whitelist = isset($_POST['whitelist']) ? $_POST['whitelist'] : null;
            $blacklist = isset($_POST['blacklist']) ? $_POST['blacklist'] : null;
            $definitions = isset($_POST['definitions']) ? $_POST['definitions'] : null;        


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

    $instanceid = $_POST['instanceid'];

    $template = $_POST['template'];
    $name = $_POST['title'];


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
