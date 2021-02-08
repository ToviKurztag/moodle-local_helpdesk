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
 * @copyright   2010 VLACS
 * @author      Jonathan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die("Direct access to this location is not allowed.");

require_once("$CFG->dirroot/local/helpdesk/lib.php");
require_once("$CFG->libdir/formslib.php");

class helpdesk_pref_form extends moodleform {
    function definition() {
        global $CFG, $OUTPUT, $DB, $USER;
        $hduser = helpdesk_get_user($USER->id);
        $institution = get_user_institution($hduser->hd_userid);
        $sql = "SELECT t.*
        FROM {local_helpdesk_institution} AS t
        WHERE t.institutionname = ?";
      //  print_r($institution);die;
        $institution = $DB->get_record_sql($sql, ['institutionname' => $institution->institution], null);
        // if (!helpdesk_is_capable(HELPDESK_CAP_ANSWER)) {
        //     $searchareas = get_user_institution($hduser->hd_userid);
        // } else {
        //     $sql = "SELECT DISTINCT institution FROM {user} as u
        //     JOIN {local_helpdesk_institution} as i WHERE u.institution = i.institutionname";
        //     $searchareas = $DB->get_fieldset_sql($sql, null);
        // }

        
        $_SESSION['institutions'] = $institution->institutionname;

        $mform =& $this->_form;
        $update_prefs = array();
        $mform->addElement('header', 'update_prefs_fieldset', get_string('updatepreferences',
                           'local_helpdesk'));
        $mform->closeHeaderBefore('save');
        $updateprefstr  = get_string('resetonlogout', 'local_helpdesk');
        $detailstr      = get_string('showdetailedupdates', 'local_helpdesk');
        $detailhelp     = $OUTPUT->help_icon('showdetailedupdates', 'local_helpdesk');
        $systemstr      = get_string('showsystemupdates', 'local_helpdesk');
        $systemhelp     = $OUTPUT->help_icon('showsystemupdates', 'local_helpdesk');
        $mform->addElement('html', "<p>$updateprefstr</p>");
        $mform->addElement('advcheckbox', 'showdetailedupdates', "$detailstr $detailhelp", '',
                            array('group' => 1), array(0, 1));
        if (helpdesk_is_capable(HELPDESK_CAP_ANSWER)) {
            // $mform->addElement('advcheckbox', 'showsystemupdates', "$systemstr $systemhelp", '',
            //                    array('group' => 2), array(0,1));
            $mform->addElement('advcheckbox', 'showsystemupdates', "$systemstr $systemhelp", '',
                              null, array(0,1));
        }

        $mform->addElement('header', 'update_prefs_fieldset', get_string('institutionpreferences',
        'local_helpdesk'));
        $mform->closeHeaderBefore('save');

       // $areanames = array();
       // print_r($searchareas);die;
        //foreach ($searchareas as $areaid => $searcharea) {
        // foreach ($searchareas as $areaid => $searcharea) {
        //     if(is_number($searcharea)) {
        //         continue;
        //     }
        //     $areanames[$areaid] = $searcharea;
        // }
        
        // $options = array(
        //     'multiple' => true,
        //    // 'noselectionstring' => get_string('allareas', 'search'),
        // );
        //$mform->addElement('autocomplete', 'institutionname', get_string('institutionname', 'local_helpdesk'), $areanames->institution, $options);
        
        $cooperativestr      = get_string('enablecooperative', 'local_helpdesk');
        $cooperativehelp     = $OUTPUT->help_icon('enablecooperative', 'local_helpdesk');

        $mform->addElement('advcheckbox', 'cooperative', $cooperativestr . $cooperativehelp, '',
                            array('group' => 1), array(0, 1));
        //$mform->addElement('advcheckbox', 'cooperative', "$cooperativestr $cooperativehelp",  get_string('cooperativedesc', 'local_helpdesk'), array('group' => 1, 'value' => 1), array(0, 1));
        $mform->setDefault('cooperative', $institution->cooperative);
        $mform->addElement('submit', 'save', get_string('savepreferences', 'local_helpdesk'));
    }
}
