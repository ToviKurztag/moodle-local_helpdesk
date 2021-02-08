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
 * This script is for creating new tickets and handling the UI and entry level
 * functions of this task.
 *
 * @package     local_helpdesk
 * @copyright   2010
 * @author      Jonathan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We are moodle, so we should get necessary stuff.
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/filelib.php');

// We are the helpdesk, so we need the core library.
require_once($CFG->dirroot . '/local/helpdesk/lib.php');

require_login(0, false);
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/helpdesk/new.php');
$PAGE->set_pagelayout('standard');
// User should be logged in, no guests.

$baseurl = new moodle_url("$CFG->wwwroot/local/helpdesk/search.php");
$url = new moodle_url("/local/helpdesk/new.php");

$ticket = new stdClass();
$ticket->detail = "";
$ticket->priority = 2;
$ticket->detailformat = FORMAT_HTML;
$editoroptions = array('maxfiles' => 99, 'maxbytes' => $CFG->maxbytes, 'context' => $context);
$ticket = file_prepare_standard_editor($ticket, 'detail', $editoroptions, $context,
    'local_helpdesk', 'ticketdetail', 0);

$toform = array();
// We may have some special tags included in GET.
$tags = optional_param('tags', null, PARAM_TAGLIST);
$tagslist = array();
$ticket->tags = array();
if (!empty($tags)) {
    $tagssplit = explode(',', $tags);
    foreach ($tagssplit as $tag) {
        if (!($rval = optional_param($tag, null, PARAM_TEXT))) {
            echo $OUTPUT->notification(get_string('missingnewtickettag', 'local_helpdesk') . ": $tag");
        }
        $taglist[$tag] = $rval;
    }
    $url->param('tags', $tags);
    $ticket->tags = $taglist;
}

$hduserid = optional_param('hd_userid', 0, PARAM_INT);
if ($hduserid) {
    helpdesk_is_capable(HELPDESK_CAP_ANSWER, true);
    $url->param('hd_userid', $hduserid);
}
$ticket->hd_userid = $hduserid;

// Require a minimum of asker capability on the current user.
helpdesk_is_capable(HELPDESK_CAP_ASK, true);

$nav = array (
    array ('name' => get_string('helpdesk', 'local_helpdesk'), 'link' => $baseurl->out()),
    array ('name' => get_string('newticket', 'local_helpdesk')),
);
$title = get_string('helpdesknewticket', 'local_helpdesk');

// Meat and potatoes of the new ticket.
// Get plugin helpdesk.
$hd = helpdesk::get_helpdesk();

// Get new ticket form to get data or the form itself.
$form = $hd->new_ticket_form(array('ticket' => $ticket, 'editoroptions' => $editoroptions,
    'tags' => $ticket->tags, 'hd_userid' => $ticket->hd_userid));

// If the form is submitted (or not) we gotta do stuff.
if (!$form->is_submitted() or !($data = $form->get_data())) {
    // helpdesk_print_header($nav, $title);
    helpdesk::page_init($title, $nav);
    echo $OUTPUT->header();
    // print_heading(get_string('helpdesk', 'local_helpdesk'));
    echo $OUTPUT->heading(get_string('helpdesk', 'local_helpdesk'));
    if ($hduserid) {
        $user = helpdesk_get_hd_user($hduserid);
        echo $OUTPUT->heading(get_string('submittingas', 'local_helpdesk') . fullname_nowarnings($user));
    }
    $form->display();
    echo $OUTPUT->footer();
    exit;
}

// At this point we know that we have a ticket to add.
$ticket = $hd->new_ticket();
if (!$ticket->parse($data)) {
    print_error("cannotparsedata", 'local_helpdesk');
}
if ($hduserid) {
    $ticket->set_firstcontact($USER->id);
}
if (!$ticket->store()) {
    print_error('unabletostoreticket', 'local_helpdesk');
}
$id = $ticket->get_idstring();
// Save the editor files in the ticketdetail filearea and update the detail/detailformat field of the ticket.
$data->id = $id;
$data = file_postupdate_standard_editor($data, 'detail', $editoroptions, $context,
    'local_helpdesk', 'ticketdetail', $id);
$ticket->parse($data);
if (!$ticket->store()) {
    print_error('unabletoadddetailtoticket', 'local_helpdesk');
}
$hd = helpdesk::get_helpdesk();
$rval = $hd->email_assign($ticket, true);
if ($hduserid) {
    $ticket->add_assignment($USER->id);
    if ($CFG->local_helpdesk_assigned_auto_watch) {
        $user = helpdesk_get_user($USER->id);
        if (!$ticket->add_watcher($user->hd_userid)) {
            print_error('cannotaddwatcher', 'local_helpdesk');
        }
    }
}

if (!empty($data->tags)) {
    $taglist = array();
    $tags = explode(',', $data->tags);
    foreach ($tags as $tag) {
        if (!($rval = $data->$tag)) {
            echo $OUTPUT->notification(get_string('missingnewtickettag', 'local_helpdesk') . ": $tag");
        } else {
            $taglist[$tag] = $rval;
        }
    }

    foreach ($taglist as $key => $value) {
        $tagobject = new stdClass;
        $tagobject->ticketid = $id;
        $tagobject->name = $key;
        $tagobject->value = $value;
        if (!$ticket->add_tag($tagobject)) {
            echo $OUTPUT->notification(get_string('cannotaddtag', 'local_helpdesk') . ": $key");
        }
    }
}

$url = new moodle_url("$CFG->wwwroot/local/helpdesk/view.php");
$url->param('id', $id);
$url = $url->out();
redirect($url, get_string('newticketnotes', 'local_helpdesk'));
