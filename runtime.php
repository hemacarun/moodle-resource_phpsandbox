<?php

require_once('../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE;


$id = required_param('id', PARAM_INT);    // Course Module ID
$recordid = optional_param('recordid', -1, PARAM_INT);
// phpsandbox variable

$code = optional_param('code', null, PARAM_RAW);
//$save = optional_param('save', null, PARAM_RAW);
//$download= optional_param('download', null, PARAM_RAW);
$setup_code = optional_param('setup_code', null, PARAM_RAW);
$prepend_code = optional_param('prepend_code', null, PARAM_RAW);
$append_code = optional_param('append_code', null, PARAM_RAW);
$options = optional_param('options', null, PARAM_RAW);
$whitelist = optional_param('whitelist', null, PARAM_RAW);
$blacklist = optional_param('blacklist', null, PARAM_RAW);
$definitions = optional_param('definitions', null, PARAM_RAW);


if (!$cm = get_coursemodule_from_id('phpsandbox', $id)) {
    print_error('Course Module ID was incorrect'); // NOTE this is invalid use of print_error, must be a lang string id
}
if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error('course is misconfigured');  // NOTE As above
}
if (!$phpsandbox = $DB->get_record('phpsandbox', array('id' => $cm->instance))) {
    print_error('course module is incorrect'); // NOTE As above
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
$PAGE->set_context($context);

// Mark viewed if required
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Print header.
$PAGE->set_title(get_string('ps_runtime', 'phpsandbox'));
//$PAGE->requires->css('/mod/phpsandbox/css/jquery-ui.css');
 $PAGE->requires->jquery_plugin('ui-css');
    $PAGE->requires->jquery();
          $PAGE->requires->jquery_plugin('ui');

require_once('vendor/autoload.php');

if (isset($_REQUEST['load'])) {
    header('Content-type: text/html');
    die('<html><body><form id="load_form" action="./runtime.php?id=' . $id . '/" method="POST" enctype="multipart/form-data"><input type="file" name="load" onchange="javascript:document.getElementById(\'load_form\').submit();"/></form></body></html>');
}
if (isset($_FILES['load'])) {
    if ($_FILES['load']['error'] == 0 && ($data = file_get_contents($_FILES['load']['tmp_name']))) {

        header('Content-type: text/html');
        die('<html><body><script>window.parent.load_template(\'' . addslashes($data) . '\');window.location.href=\'./runtime.php?id=' . $id . '&load=true\';</script></body></html>');
    }
    header('Content-type: text/html');
    die('<html><body><script>alert("Template could not be loaded!");</script></body></html>');
}

function converting_objecttoarray($jsondata) {

    $jsondata = json_decode($jsondata);
    // if(is_array( $jsondata)){
    $jsondata = json_decode(json_encode($jsondata), true);
    $jsondata = array_filter($jsondata);
    // }    
    return $jsondata;
}

if (isset($_POST['code'])) {

    $code = json_decode($code);
    $setup_code = json_decode($setup_code);
    $prepend_code = json_decode($prepend_code);
    $append_code = json_decode($append_code);
    $options = converting_objecttoarray($options);
    $whitelist = converting_objecttoarray($whitelist);
    $blacklist = converting_objecttoarray($blacklist);
    $definitions = converting_objecttoarray($definitions);


    $sandbox = \PHPSandbox\PHPSandbox::create()->import(array(
        'setup_code' => $setup_code,
        'prepend_code' => $prepend_code,
        'append_code' => $append_code,
        'options' => $options,
        'whitelist' => $whitelist,
        'blacklist' => $blacklist,
        'definitions' => $definitions
    ));

    $sandbox->set_error_handler(function($errno, $errmsg, $errfile, $errline) {
        die('<h2 style="color: red;">Error: ' . $errmsg . ' on line ' . $errline . '</h2>');
    });
    $sandbox->set_exception_handler(function(\Exception $e) {
        die('<h2 style="color: red;">Exception: ' . $e->getMessage() . ' on line ' . $e->getLine() . '</h2>');
    });
    $sandbox->set_validation_error_handler(function(\PHPSandbox\Error $e) {
        die('<h2 style="color: red;">Validation Error: ' . $e->getMessage() . '</h2>');
    });
    try {
        ob_start();
        if ($setup_code) {
            @eval($setup_code);
        }

        $result = $sandbox->execute($code);
        //print_r($result);
        if ($result !== null) {
            echo (ob_get_contents() ? '<hr class="hr"/>' : '') . '<h3>The sandbox returned this value:</h3>';
            var_dump($result);
        }
        echo '<hr class="hr"/>Preparation time: ' . round($sandbox->get_prepared_time() * 1000, 2) .
        ' ms, execution time: ' . round($sandbox->get_execution_time() * 1000, 2) .
        ' ms, total time: ' . round($sandbox->get_prepared_time() * 1000, 2) . ' ms';
        $buffer = ob_get_contents();
        ob_end_clean();
        die('<pre>' . $buffer . '</pre>');
    } catch (\PHPSandbox\Error $e) {
        die('<h6 style="color: red;">' . $e->getMessage() . '</h6>');
    }
}

if (isset($_GET['template'])) {
    $template = stripslashes($_GET['template']);
    if (file_exists($template)) {
        header('Content-type: text/html');
        readfile($template);
    }
    exit;
}

if (isset($_REQUEST['download'])) {
    exit;
}
$data = json_decode(file_get_contents("templates/001 - Hello World.json"), true);
$output = $PAGE->get_renderer('mod_phpsandbox');

//$submissionrecord = get_record('workshop_submissions', array(...));
$psdata = new phpsandbox($data, $cm);
// while generating output or loading template,containers not include page header
if (!isset($code)) {
    $PAGE->set_heading($course->fullname);
    $PAGE->set_pagelayout('admin');
    $PAGE->set_url('/mod/phpsandbox/runtime.php?id=' . $cm->id . '');
    echo $output->header();

    $currenttab = 'runtime_et';
    phpsandbox_tabs($currenttab, $cm->id);
}
//echo $output->header();
echo $output->render($psdata);
echo $output->footer();
?>


