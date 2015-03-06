<?php

global $CFG, $DB, $USER;
@require_once($CFG->dirroot . '../../config.php');
error_reporting(0);
$selectedrecordid = $_POST['selectedid'];
if ($selectedrecordid) {
    // fetching record
    $selecteddata = $DB->get_record('phpsandbox_records', array('id' => $selectedrecordid));

    echo $selecteddata->codecontent;
} else {
    // inserting record
    if (isset($_POST['save']) || isset($_POST['download'])) {
        if (isset($_POST['download'])) {
            $data = json_decode($_POST['download'], true);
            if (!is_array($data)) {
                header('Content-type: text/html');
                die('<html><body><script>alert("Template could not be saved!");</script></body></html>');
            }
            $code = $data['code'];
            $setup_code = isset($data['setup_code']) ? $data['setup_code'] : null;
            $prepend_code = isset($data['prepend_code']) ? $data['prepend_code'] : null;
            $append_code = isset($data['append_code']) ? $data['append_code'] : null;
            $options = isset($data['options']) ? $data['options'] : array();
            $whitelist = isset($data['whitelist']) ? $data['whitelist'] : null;
            $blacklist = isset($data['blacklist']) ? $data['blacklist'] : null;
            $definitions = isset($data['definitions']) ? $data['definitions'] : null;
            $filename = $template = stripslashes($data['save']);
        } else {
            $code = $_POST['code'];
            $setup_code = isset($_POST['setup_code']) ? $_POST['setup_code'] : null;
            $prepend_code = isset($_POST['prepend_code']) ? $_POST['prepend_code'] : null;
            $append_code = isset($_POST['append_code']) ? $_POST['append_code'] : null;
            $options = isset($_POST['options']) ? $_POST['options'] : array();
            $whitelist = isset($_POST['whitelist']) ? $_POST['whitelist'] : null;
            $blacklist = isset($_POST['blacklist']) ? $_POST['blacklist'] : null;
            $definitions = isset($_POST['definitions']) ? $_POST['definitions'] : null;
            $template = stripslashes(isset($_POST['save']) ? $_POST['save'] : $_POST['download']);
            $filename = isset($_POST['download']) ? $template : trim(preg_replace('/[^a-zA-Z0-9_ ]/', '_', $template), '_');
        }

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