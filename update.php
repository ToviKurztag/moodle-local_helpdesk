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
 * This script handles the updating of tickets by managing the UI and entry
 * level functions for the task.
 *
 * @package     local_helpdesk
 * @copyright   2010 VLACS
 * @author      Jonathan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

// We are also Helpdesk, so we shall also become a helpdesk.
require_once("$CFG->dirroot/local/helpdesk/lib.php");

global $PAGE;
$id = required_param('id', PARAM_INT);
$token = optional_param('token', '', PARAM_ALPHANUM);
$PAGE->set_pagelayout('standard');

if (strlen($token) and !empty($CFG->local_helpdesk_external_updates)) {
    helpdesk_authenticate_token($id, $token);
    $nav = array ();
} else {
    require_login(0, false);
    $nav = array (
        array (
            'name' => get_string('helpdesk', 'local_helpdesk'),
            'link' => "$CFG->wwwroot/local/helpdesk/search.php",
        ),
        array (
            'name' => get_string('ticketview', 'local_helpdesk'),
            'link' => "$CFG->wwwroot/local/helpdesk/view.php?id=$id",
        ),
        array (
            'name' => get_string('updateticket', 'local_helpdesk')
        )
    );
}

$title = get_string('helpdeskupdateticket', 'local_helpdesk');
helpdesk::page_init($title, $nav);


$hd = helpdesk::get_helpdesk();

$ticket = $hd->get_ticket($id);
if (!$ticket) {
    print_error('invalidticketid', 'local_helpdesk');
}

$form = $hd->update_ticket_form($ticket);

if ($form->is_submitted() and ($data = $form->get_data())) {
    $data->type = HELPDESK_UPDATE_TYPE_USER;
    if($ticket->add_update($data)) {
        $url = "$CFG->wwwroot/local/helpdesk/view.php?id=$id";
        if (!empty($USER->helpdesk_token)) {
            $url .= "&token=$USER->helpdesk_token";
        }
        redirect($url, get_string('updateadded', 'local_helpdesk'));
    } else {
        print_error('cannotaddupdate', 'local_helpdesk');
    }
}

echo $OUTPUT->header();

$form->display();
$hd->display_ticket($ticket, true);

helpdesk_print_footer();
