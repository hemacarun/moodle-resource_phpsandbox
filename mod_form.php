<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/phpsandbox/lib.php');

class mod_phpsandbox_mod_form extends moodleform_mod {

    function definition() {
        global $CFG, $DB, $OUTPUT;

        $mform = & $this->_form;

        $mform->addElement('text', 'name', get_string('psname', 'phpsandbox'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->add_intro_editor(true, get_string('psdescription', 'phpsandbox'));

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

}

?>