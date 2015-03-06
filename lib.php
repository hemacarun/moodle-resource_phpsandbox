<?php

/** PHPSANDBOX_MAX_NAME_LENGTH = 50 */
define("PHPSANDBOX_MAX_NAME_LENGTH", 50);

/**
 * @uses PHPSANDBOX_MAX_NAME_LENGTH
 * @param object $phpsandbox
 * @return string
 */
function get_phpsandbox_name($phpsandbox) {
    $name = strip_tags(format_string($phpsandbox->name, true));
    if (core_text::strlen($name) > PHPSANDBOX_MAX_NAME_LENGTH) {
        $name = core_text::substr($name, 0, PHPSANDBOX_MAX_NAME_LENGTH) . "...";
    }

    if (empty($name)) {
        // arbitrary name
        $name = get_string('modulename', 'phpsandbox');
    }

    return $name;
}

/**
 * @method phpsandbox_add_instances()
 * @todo it used to add new sandbox instance 
 */
function phpsandbox_add_instance($phpsandbox) {
    global $DB, $USER;

    $phpsandbox->name = get_phpsandbox_name($phpsandbox);
    $phpsandbox->userid = $USER->id;
    $phpsandbox->timemodified = time();
    return $DB->insert_record("phpsandbox", $phpsandbox);
}

/**
 * @method phpsandbox_update_instances()
 * @todo it used to update sandbox instance 
 */
function phpsandbox_update_instance($phpsandbox) {
    global $DB, $USER;

    $phpsandbox->userid = $USER->id;
    $phpsandbox->timemodified = time();
    $phpsandbox->id = $phpsandbox->instance;
    return $DB->update_record("phpsandbox", $phpsandbox);
}

/**
 * @method phpsandbox_delete_instance()
 * @todo it used to delete sandbox instance 
 */
function phpsandbox_delete_instance($id) {

    global $DB;
    if (!$phpsandbox = $DB->get_record("phpsandbox", array("id" => $id))) {
        return false;
    }
    $result = true;
    if (!$DB->delete_records("phpsandbox", array("id" => $phpsandbox->id))) {
        $result = false;
    }
    return $result;
}

/**
 * @method delete_phpsandbox_records()
 * @todo it used to delete sandbox record (which, code is saved the  user)
 * @param int  $recordid it holds record id
 * @return--it displays the tab
 */
function delete_phpsandbox_records($recordid) {
    global $DB;

    if (!$psrecords = $DB->get_record("phpsandbox_records", array("id" => $recordid))) {
        return false;
    }
    $result = true;
    if (!$DB->delete_records("phpsandbox_records", array("id" => $recordid))) {
        $result = false;
    }
    return $result;
}

/**
 * @uses FEATURE_IDNUMBER
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return bool|null True if module supports feature, false if not, null if doesn't know
 */
function phpsandbox_supports($feature) {
    switch ($feature) {
        case FEATURE_IDNUMBER: return false;
        case FEATURE_GROUPS: return false;
        case FEATURE_GROUPINGS: return false;
        case FEATURE_GROUPMEMBERSONLY: return false;
        case FEATURE_MOD_INTRO: return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
        case FEATURE_GRADE_HAS_GRADE: return false;
        case FEATURE_GRADE_OUTCOMES: return false;
        case FEATURE_MOD_ARCHETYPE: return MOD_ARCHETYPE_RESOURCE;
        // case FEATURE_BACKUP_MOODLE2:          return true;
        // case FEATURE_NO_VIEW_LINK:            return true;

        default: return null;
    }
}

/**
 * @method phpsandbox_tabs()
 * @todo it provides the tab view(perticularly for this plugin) 
 * @param string $currentab by default it hold the first tab name
 * @param string $dynamictab by default its null ,if passes the parameter it creates dynamic tab
 * @return--it displays the tab
 */
function phpsandbox_tabs($currenttab = 'view', $cm = null, $recordid = null, $dynamictab = null, $edit_label = null) {
    global $OUTPUT;
    $toprow = array();
    $toprow[] = new tabobject('view', new moodle_url('/mod/phpsandbox/view.php?id=' . $cm . ''), get_string('ps_view', 'mod_phpsandbox'));
    $toprow[] = new tabobject('runtime_et', new moodle_url('/mod/phpsandbox/runtime.php?id=' . $cm . ''), get_string('ps_runtime', 'mod_phpsandbox'));

    echo $OUTPUT->tabtree($toprow, $currenttab);
}

?>