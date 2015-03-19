<?php
//
//
// This software is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This Moodle block is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The PHP sandbox
 *
 * It provide the runtime environment to run php script
 *
 * @package resource
 * @copyright 2015 hemalatha c arun < hemalatha@eabyas.in >
* @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

require_once('../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE;


$id = required_param('id', PARAM_INT);    // Course Module ID
$recordid = optional_param('recordid', -1, PARAM_INT);
// phpsandbox variable

$code = optional_param('code', null, PARAM_RAW);
$setup_code = optional_param('setup_code', null, PARAM_TEXT);
$prepend_code = optional_param('prepend_code', null, PARAM_TEXT);
$append_code = optional_param('append_code', null, PARAM_TEXT);
$options = optional_param('options', null, PARAM_TEXT);
$whitelist = optional_param('whitelist', null, PARAM_TEXT);
$blacklist = optional_param('blacklist', null, PARAM_TEXT);
$definitions = optional_param('definitions', null, PARAM_TEXT);


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
 $PAGE->requires->jquery_plugin('ui-css');
    $PAGE->requires->jquery();
          $PAGE->requires->jquery_plugin('ui');

require_once('vendor/autoload.php');


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

if (isset($code)) {

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


