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
 * New ticket form which extends a standard moodleform.
 *
 * @package     local_helpdesk
 * @copyright   2010 VLACS
 * @author      Jonathan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die("Direct access to this location is not allowed.");

require_once("$CFG->libdir/formslib.php");

class search_form extends moodleform {
    function definition() {
        global $DB, $OUTPUT, $USER;

        $context = context_system::instance();

        // Return all that have rows, but not on the join itself.
        // We want to populate the answerers list with only users who have 
        $answerers = $DB->get_records_sql("
        select DISTINCT u.*
        from mdl_user as u
        join mdl_role_assignments as a on u.id = a.userid
        join mdl_role as r on a.roleid = r.id
        where r.shortname = 'helpdeskmanager'
        ");

        $statuses = get_ticket_statuses();
        $statuslist = array();
        $statusdefault = array();
        foreach ($statuses as $s) {
            $statuslist[$s->id] = get_status_string($s);
            $statusdefault[] = $s->id;
        }
        $sql = 'select i.id from mdl_user as u
        join mdl_local_helpdesk_institution as i
        on u.institution = i.institutionname
        where u.id=?';

        $clientinstitution = $DB->get_record_sql($sql, ['userid' => $USER->id], $strictness = IGNORE_MISSING);
        //print_r($clientinstitution);
        $institutionlist = $DB->get_records('local_helpdesk_institution', null, null, $fields = '*');
        $institutionlist = $DB->get_records('local_helpdesk_institution', null, null, $fields = '*');
        $institutiondefault = array();
        foreach ($institutionlist as $s) {
            $institutionlist[$s->id] = $s->institutionname;
            $institutiondefault[] = $s->id;
        }
        $answererlist = array(
            -1 => get_string('anyanswerer', 'local_helpdesk'),
            0 => get_string('noanswerers', 'local_helpdesk')
        );
        foreach ($answerers as $a) {
            $answererlist[$a->id] = fullname_nowarnings($a);
        }
        $mform =& $this->_form;
        $help = $OUTPUT->help_icon('search', 'local_helpdesk');
        $searchphrase = get_string('searchphrase', 'local_helpdesk');
        $statusstr = get_string('status', 'local_helpdesk');
        $institutionstr = get_string('institution', 'local_helpdesk');
        $answererstr = get_string('answerer', 'local_helpdesk');

        // Elements
        $mform->addElement('header', 'frm', get_string('search'));
        $mform->setExpanded('frm', false);
        $mform->addElement('text', 'searchstring', $searchphrase . $help);
        $mform->setType('searchstring', PARAM_TEXT);

        $adv = array();
        $statuselement =& $mform->createElement('select', 'status', $statusstr, $statuslist);
        $statuselement->setMultiple(true);
        $mform->addElement($statuselement);

        $mform->addElement('date_selector', 'timecreatedfrom', get_string('from'));
        $mform->addElement('date_selector', 'timecreatedto', get_string('to'));

        if (helpdesk_is_capable(HELPDESK_CAP_ANSWER)) {
        $statuselement =& $mform->createElement('select', 'institution', $institutionstr, $institutionlist);
        $statuselement->setMultiple(true);
        $mform->addElement($statuselement);
        $mform->setDefault('institution', $institutionlist);
        }
        //today
        else {
            $mform->addElement('hidden', 'institution', intval($clientinstitution->id));
            $mform->setDefault('institution', intval($clientinstitution->id));
           // $mform->addElement('hidden', 'answerer', '');
        }
        $mform->addElement('select', 'answerer', $answererstr, $answererlist);
        $mform->addElement('submit', 'submitbutton', get_string('search'));
        $mform->addElement('hidden', 'submitter', '');
        $mform->setType('submitter', PARAM_INT);
        // $mform->closeHeaderBefore('submitbutton');
        // Rules
       // $mform->addRule('status', null, 'required', 'server');
        $mform->setAdvanced('answerer', true);
        $mform->setAdvanced('status', true);
        $mform->setDefault('answerer', -1);
        $mform->setDefault('status', $statusdefault);
       
        //$mform->setDefault('institution', 0);
        $mform->setDefault('timecreatedfrom', strtotime("-1 year", time()));
        $mform->setDefault('timecreatedto', time());
      // $mform->setDefault('institution', $institutiondefault);
      // if (!helpdesk_is_capable(HELPDESK_CAP_ANSWER)) {

    //   }
    }

    function validation($date, $files) {
        // Add something at some point.
    }

    function set_multiselect_default($array) {
        $mform =& $this->_form;
        $mform->setDefault('status', $array);
    }
}
