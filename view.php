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

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/mod/phpsandbox/lib.php');

global $CFG, $DB, $OUTPUT, $PAGE;

$id = required_param('id', PARAM_INT);    // Course Module ID
$delete = optional_param('delete', 0, PARAM_BOOL);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$recordid = optional_param('recordid', -1, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_BOOL);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$notifysuccess = optional_param('notifysuccess', 0, PARAM_INT);
$frdelete = optional_param('frdelete', 0, PARAM_INT);
$perpage = 6;
$spage = $page * $perpage;


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

if ($delete) {
    //$PAGE->url->param('delete', 1);
    if ($confirm and confirm_sesskey()) {

        $deleterecords = delete_phpsandbox_records($recordid);
        if ($deleterecords) {
            $message = get_string('deletepsyes', 'mod_phpsandbox');
            $style = array('style' => 'notifysuccess');
            $notifysuccess = 1;
        } else {
            $message = get_string('deletepsno', 'mod_phpsandbox');
            $style = array('style' => 'notifyproblem');
            $notifysuccess = -1;
        }
        $returnurl = new moodle_url('/mod/phpsandbox/view.php', array('id' => $cm->id, 'notifysuccess' => $notifysuccess, 'frdelete' => 1));
        // $hierarchy->set_confirmation($message,$returnurl,$style);
        redirect($returnurl, $message);
    }
    $strheading = get_string('deleteps', 'mod_phpsandbox');
    $PAGE->set_url('/mod/phpsandbox/view.php?id=' . $cm->id . '');
    $PAGE->navbar->add(get_string('pluginname', 'mod_phpsandbox'), new moodle_url('/mod/phpsandbox/view.php'));
    $PAGE->navbar->add($strheading);
    $PAGE->set_title($strheading);

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('deleteps', 'mod_phpsandbox'));

    $yesurl = new moodle_url('/mod/phpsandbox/view.php', array('id' => $cm->id, 'recordid' => $recordid, 'delete' => 1, 'confirm' => 1, 'sesskey' => sesskey()));

    $message = get_string('deletepsrecords', 'mod_phpsandbox');
    echo $OUTPUT->confirm($message, $yesurl, new moodle_url('/mod/phpsandbox/view.php', array('id' => $cm->id)));

    echo $OUTPUT->footer();
    die;
}

$PAGE->set_title(get_string('pluginname', 'phpsandbox'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/mod/phpsandbox/view.php?id=' . $cm->id . '');
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('ps_view_headings', 'mod_phpsandbox'));
echo $OUTPUT->box(format_module_intro('phpsandbox', $phpsandbox, $cm->id), 'generalbox', 'intro');

//adding tabs using prefix_tabs function
$currenttab = 'view';
phpsandbox_tabs($currenttab, $id);
if ($frdelete) {
    if ($notifysuccess)
        echo $OUTPUT->notification(get_string('deletepsyes', 'mod_phpsandbox'), 'notifysuccess');
    else {
        if ($notifysuccess == -1)
            echo $OUTPUT->notification(get_string('deletepsno', 'mod_phpsandbox'), 'notifyproblem');
    }
}
if (is_siteadmin()) {
    $rlist = $DB->get_records('phpsandbox_records', array('sandboxinstanceid' => $cm->instance));
    $condition = " ";
} else {
    $rlist = $DB->get_records('phpsandbox_records', array('sandboxinstanceid' => $cm->instance, 'userid' => $USER->id));
    $condition = " AND userid = $USER->id";
}

$totalcount = sizeof($rlist);

$sql = "select * from  {phpsandbox_records} where sandboxinstanceid=$cm->instance $condition LIMIT $spage,$perpage";

$records_list = $DB->get_records_sql($sql);

$data = array();
foreach ($records_list as $record) {
    $line = array();
    $line[] = $record->name;
    // $line[] = html_writer::tag('a',$record->name, array('href' => ''.$CFG->wwwroot.'/mod/phpsandbox/runtime.php?id='.$cm->id.'&recordid='.$record->id.''));
    $button = html_writer::link(new moodle_url('/mod/phpsandbox/download.php', array('recordid' => $record->id, 'mode' => $currenttab, 'sesskey' => sesskey())), html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/download'), 'title' => get_string('download'), 'alt' => get_string('download'), 'class' => 'iconsmall')));
    $button .= html_writer::link(new moodle_url('/mod/phpsandbox/view.php', array('id' => $cm->id, 'recordid' => $record->id, 'mode' => $currenttab, 'delete' => 1, 'sesskey' => sesskey())), html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/delete'), 'title' => get_string('delete'), 'alt' => get_string('delete'), 'class' => 'iconsmall')));
    //  $button.= html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/delete'), 'title' => get_string('delete'), 'alt' => get_string('delete'), 'onclick'=>'checkall()'));
    $line[] = $button;
    $data[] = $line;
}
//View Part starts
//start the table
$table = new html_table();
$table->id = 'phpsandbox';
$table->head = array(
    get_string('ps_name', 'mod_phpsandbox'),
    get_string('ps_action', 'mod_phpsandbox'));

$table->size = array('45%', '55%');
$table->align = array('left', 'left');
$table->width = '99%';
$table->data = $data;
echo html_writer::table($table);

if (empty($records_list))
    echo get_string('emptyps_msg', 'mod_phpsandbox');

$baseurl = new moodle_url($CFG->wwwroot . '/mod/phpsandbox/view.php?id=' . $id . '', array('perpage' => $perpage, 'page' => $page));
echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $baseurl);

echo $OUTPUT->footer();
?>    
