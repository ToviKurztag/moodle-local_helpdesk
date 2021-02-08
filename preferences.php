<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This is a preferences script. This allows the user to change settings that
 * may alter how the helpdesk is viewed.
 *
 * @package     local_helpdesk
 * @copyright   2010
 * @author      Jonathan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We are moodle, so we shall become moodle.
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

// We are also Helpdesk, so we shall also become a helpdesk.
require_once("$CFG->dirroot/local/helpdesk/lib.php");
require_once("$CFG->dirroot/local/helpdesk/pref_form.php");

require_login(0, false);
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/helpdesk/preferences.php');
$PAGE->set_pagelayout('standard');

$baseurl = new moodle_url("$CFG->wwwroot/local/helpdesk/search.php");
$nav = array (
    array ('name' => get_string('helpdesk', 'local_helpdesk'), 'link' => $baseurl->out()),
    array ('name' => get_string('preferences')),
);
$title = get_string('pref', 'local_helpdesk');


require_login();

if(!helpdesk_is_capable()) {
    print_error('nocapabilities', 'local_helpdesk');
}

// By default, these are disabled (false).
$preferences = new stdClass();
$preferences->showsystemupdates = (bool) helpdesk_get_session_var('showsystemupdates');
$preferences->showdetailedupdates = (bool) helpdesk_get_session_var('showdetailedupdates');

$form = new helpdesk_pref_form(qualified_me(), null, 'post');

// If not submitted, show form with current values.
if (!$form->is_submitted()) {
//    helpdesk_print_header($nav);
    helpdesk::page_init($title, $nav);
    echo $OUTPUT->header();
    $form->set_data($preferences);
    $form->display();
    echo $OUTPUT->footer();
    exit;
}

// We have a submitted form, lets assume everything changed and update
// everything.
$data = $form->get_data();
$institutionslist = array();
if (isset($_SESSION['institutions'])) {
    $tmp = $_SESSION['institutions'];
    $sql = "SELECT t.*
    FROM {local_helpdesk_institution} AS t
    WHERE t.institutionname = ?";
    $institution = $DB->get_record_sql($sql, ['institutionname' => $tmp], null);
    $institution->cooperative = $data->cooperative;
    $DB->update_record('local_helpdesk_institution', $institution, $bulk = false);
}
foreach ($data as $key => $value) {
    helpdesk_set_session_var($key, $value);
}

redirect($CFG->wwwroot, get_string('preferencesupdated', 'local_helpdesk'));

//foreach ($data->institutionname as $key) {
    //         // if (!empty($data->institutionname[$key])) {
    //         //     print_r( $_SESSION['institutions'][$key]);die;
    //             array_push($institutionslist, $_SESSION['institutions'][$key]);
    //           //  unset($_SESSION['institutions'][$key]);
    //         }
        
    //    // print_r($institutionslist);die;
    //     foreach ($institutionslist as $name) {
    //       //  print_r($key);die;
    //         $institution                     = new stdClass;
    //         $institution->institutionname    = $name;
    //         $institution->cooperative             = $data->cooperative;
    //         if (!$DB->record_exists('local_helpdesk_institution', array('institutionname' => $name))) {
    //             $DB->insert_record('local_helpdesk_institution', $institution);
    //         }
    //         else {
    //             $tmp = $DB->get_record('local_helpdesk_institution',  array('institutionname' => $name), $fields = '*', $strictness=IGNORE_MISSING);
    //             $institution->id = $tmp->id;
    //             $DB->update_record('local_helpdesk_institution', $institution);
    //         }
    //     }