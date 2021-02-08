<!-- <style>
div.warningclass {
   color:red !important;
   background-color: red !important;
  }
  </style> -->
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
 * Make sure that direct browsing to the helpdesk local directory does something
 * helpful.
 *
 * @package     local_helpdesk
 * @copyright   2010 VLACS
 * @author      Jonathan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once("$CFG->dirroot/local/helpdesk/lib.php");

$PAGE->set_context(context_system::instance());
$PAGE->set_heading(get_string('pluginname', 'local_helpdesk'));
$PAGE->set_title(get_string('pluginname', 'local_helpdesk'));
$PAGE->set_url('/local/helpdesk/myquestions.php');

// $PAGE->requires->js('/local/helpdesk/js/jquery.dataTables.js');
 $PAGE->requires->css('/local/helpdesk/styles.css');
// $PAGE->requires->css('/local/helpdesk/css/jquery.dataTables.css');
// $PAGE->requires->js('/local/helpdesk/js/sort.js');

require_login();
$PAGE->set_pagelayout('standard');
    global $CFG, $USER, $OUTPUT, $DB, $USER;
    $output = '';

    // Get objects initialized and variables declared.
    // First thing is first, user must have some form of capbility on the
    // helpdesk. Otherwise they shouldn't be able to access it.
    $cap = helpdesk_is_capable();
     $noticketstr = get_string('noticketstodisplay', 'local_helpdesk');
    if ($cap == false || empty($USER->id)) {
       return $output = '';
    }
    // Lets get the helpdesk initialized.
    $hd = helpdesk::get_helpdesk();

    $url = "$CFG->wwwroot/local/helpdesk/search.php";
    $text = get_string('viewalltickets', 'local_helpdesk');
    $output .= "<a class=\"btn btn-primary\" href=\"$url\">$text</a> &nbsp;";

    //Link for submitting a new ticket.
    $url = $hd->default_submit_url()->out();
    $text = !empty($CFG->local_helpdesk_submit_text) ?
                    $CFG->local_helpdesk_submit_text : get_string('submitnewticket', 'local_helpdesk');
                    $output .= "<a class=\"btn btn-primary\" href=\"$url\">$text</a><br /><br />";

   // Show assigned ticket if answerer.
    if ($cap == HELPDESK_CAP_ANSWER) {
        $title = '<h4>' . get_string('myassignedtickets', 'local_helpdesk') . '</h4>';
        $output .= $title;

        $tickets = $hd->search(
            $hd->get_ticket_relation_search($hd->get_default_relation(HELPDESK_CAP_ANSWER)),
            15, 0
        );
        if (!empty($tickets->count)) {
            $table = new html_table();
            $table->head = array(get_string('summary', 'local_helpdesk'),
                                 get_string('user', 'local_helpdesk'),
                                 get_string('supporter', 'local_helpdesk'),
                                 get_string('status', 'local_helpdesk'),
                                 get_string('timecreated', 'local_helpdesk'),
                                 get_string('timemodified', 'local_helpdesk'),
                                 get_string('institution', 'local_helpdesk'),
                                 get_string('priority', 'local_helpdesk'),
                                );
            $table->data = [];
            foreach ($tickets->results as $ticket) {
                $warningclass = '';
                $time = time() - 1209600;

                $hduser = helpdesk_get_user($USER->id);
                $hduseridsuporter = $hduser->hd_userid;
                //print_r($hduseridsuporter);die;
                if ($ticket->get_responded_ticket($ticket, $hduseridsuporter)) {
                    $warningclass .= 'respondedclass';
                }
                //print_r($exists);die;
                if ($time > $ticket->get_timemodified()) {
                    $warningclass = $warningclass . ' warningclass';
                }
                $url = new moodle_url("$CFG->wwwroot/local/helpdesk/view.php");
                $url->param('id', $ticket->get_idstring());
                $url = $url->out();
                $table->data[] = array("<div class= ".$warningclass.">" ."<a href=\"$url\">" . $ticket->get_summary() . '</a>'. "</div>",
                                       "<div class= ".$warningclass.">" .$ticket->get_user_name($ticket->hd_userid) . "</div>",
                                       "<div class= ".$warningclass.">" .$ticket->get_assigned_string($ticket->get_assigned()). "</div>",
                                       "<div class= ".$warningclass.">" .$ticket->get_status_string(). "</div>",
                                       "<div class= ".$warningclass.">" .helpdesk_get_date_string($ticket->get_timecreated()). "</div>",
                                       "<div class= ".$warningclass.">" .helpdesk_get_date_string($ticket->get_timemodified()). "</div>",
                                       "<div class= ".$warningclass.">" .$ticket->get_user_institution($ticket->hd_userid). "</div>",
                                       "<div class= ".$warningclass.">" .$ticket->get_priority_string($ticket->priority). "</div>"
                                       );
            }
            $output .= html_writer::table($table);
        } else {
            $output .= $OUTPUT->notification($noticketstr, 'notifyproblem');
        }
    }

    if (!helpdesk_is_capable(HELPDESK_CAP_ANSWER)) {
    // Print my tickets title. local itself just displays first 5 user
    // tickets. Other tickets are found in ticket listing.
    $output .= '<h4>' . get_string('mytickets', 'local_helpdesk') . '</h4>';

    // Grab the tickets to display and add to the content.
    $table = new html_table();
    $hduser = helpdesk_get_user($USER->id);
    $so = $hd->new_search_obj();
    $so->watcher = $hduser->hd_userid;
    $so->status = $hd->get_status_ids(true, false);
    $tickets = $hd->search($so, 15, 0);
    if (!empty($tickets->count)) {
        $table->head = array(get_string('summary', 'local_helpdesk'),
                             get_string('user', 'local_helpdesk'),
                             get_string('supporter', 'local_helpdesk'),
                             get_string('status', 'local_helpdesk'),
                             get_string('timecreated', 'local_helpdesk'),
                             get_string('timemodified', 'local_helpdesk'),
                             get_string('institution', 'local_helpdesk'),
                             get_string('priority', 'local_helpdesk'),
                             );
        $table->data = [];
        foreach ($tickets->results as $ticket) {
//print_r($ticket->hd_userid);die;
            $url = new moodle_url("$CFG->wwwroot/local/helpdesk/view.php");
            $url->param('id', $ticket->get_idstring());
            $url = $url->out();

            $table->data[] = array("<a href=\"$url\">" . $ticket->get_summary() . '</a>',
                                   $ticket->get_user_name($ticket->hd_userid),
                                   $ticket->get_assigned_string($ticket->get_assigned()),
                                   $ticket->get_status_string(),
                                   helpdesk_get_date_string($ticket->get_timecreated()),
                                   helpdesk_get_date_string($ticket->get_timemodified()),
                                   $ticket->get_user_institution($ticket->hd_userid),
                                   $ticket->get_priority_string($ticket->priority)
                                   );
        }
        $output  .= html_writer::table($table);
    } else {
        $output .= $OUTPUT->notification($noticketstr, 'notifyproblem');
    }
}


    //Tovi
    // Print my tickets title. local itself just displays first 5 user
    // tickets. Other tickets are found in ticket listing.
    if ($hd->get_cooperative_institution($USER->id)) {
    $output .= '<h4>' . get_string('myinstitutiontickets', 'local_helpdesk') . '</h4>';

    // Grab the tickets to display and add to the content.
    $table = new html_table();
    $hduser = helpdesk_get_user($USER->id);
    $userinstitution = $hd->get_user_institution($hduser->hd_userid);
    $sql = 'SELECT DISTINCT u.id 
            FROM mdl_user as u 
            JOIN mdl_local_helpdesk_hd_user as hu
            WHERE u.institution like "' . $userinstitution . '"';
    $users = $DB->get_records_sql($sql, null);
   // print_r($users);die;
    if(isset($users) && count($users) > 1) {
        $table->head = array(get_string('summary', 'local_helpdesk'),
                             get_string('user', 'local_helpdesk'),
                             get_string('supporter', 'local_helpdesk'),
                             get_string('status', 'local_helpdesk'),
                             get_string('timecreated', 'local_helpdesk'),
                             get_string('timemodified', 'local_helpdesk'),
                             get_string('institution', 'local_helpdesk'),
                             get_string('priority', 'local_helpdesk'),
                             );
    foreach ($users as $user) {
        if ($user->id == $USER->id) {
           continue;
        }
       
        if (helpdesk_is_capable(HELPDESK_CAP_ANSWER)) {
            $tickets = $hd->search(
            $hd->get_ticket_relation_search_user($hd->get_default_relation(HELPDESK_CAP_ANSWER), $user),
            5, 0);
        } 
        else {
            $hduser = helpdesk_get_user($user->id);
            $so = $hd->new_search_obj();
            $so->watcher = $hduser->hd_userid;
            $so->status = $hd->get_status_ids(true, false);
            $so->institution = $hd->get_user_institution($hduser->hd_userid);
            $tickets = $hd->search($so, 5, 0);

        }
      
    if (!empty($tickets->count)) {
        // $table = new html_table();
        // $table->head = array(get_string('summary', 'local_helpdesk'),
        //                      get_string('user', 'local_helpdesk'),
        //                      get_string('status', 'local_helpdesk'),
        //                      get_string('timecreated', 'local_helpdesk'),
        //                      get_string('timemodified', 'local_helpdesk'),
        //                      get_string('institution', 'local_helpdesk'),
        //                      get_string('priority', 'local_helpdesk'),
        //                      );
        $table->data = [];
        foreach ($tickets->results as $ticket) {
            $url = new moodle_url("$CFG->wwwroot/local/helpdesk/view.php");
            $url->param('id', $ticket->get_idstring());
            $url = $url->out();

            $table->data[] = array("<a href=\"$url\">" . $ticket->get_summary() . '</a>',
                                   $ticket->get_user_name($ticket->hd_userid),
                                   $ticket->get_assigned_string($ticket->get_assigned()),
                                   $ticket->get_status_string(),
                                   helpdesk_get_date_string($ticket->get_timecreated()),
                                   helpdesk_get_date_string($ticket->get_timemodified()),
                                   $ticket->get_user_institution($ticket->hd_userid),
                                   $ticket->get_priority_string($ticket->priority)
                                   );
        }
        $output  .= html_writer::table($table);
      // echo $output;
    }
}
    }else {
        $output .= $OUTPUT->notification($noticketstr, 'notifyproblem');
    }
}


    //Tovi

    //Link for viewing all kinds of tickets.
    $url = "$CFG->wwwroot/local/helpdesk/search.php";
    $text = get_string('viewalltickets', 'local_helpdesk');
    $output .= "<a class=\"btn btn-primary\" href=\"$url\">$text</a>&nbsp;";

    //Link for submitting a new ticket.
    $url = $hd->default_submit_url()->out();
    $text = !empty($CFG->local_helpdesk_submit_text) ?
                    $CFG->local_helpdesk_submit_text : get_string('submitnewticket', 'local_helpdesk');
                    $output .= "<a class=\"btn btn-primary\" href=\"$url\">$text</a><br /><br />";

    if (helpdesk_is_capable(HELPDESK_CAP_ANSWER)) {
        $submitasurl = new moodle_url("$CFG->wwwroot/local/helpdesk/userlist.php");
        $submitasurl->param('function', HELPDESK_USERLIST_SUBMIT_AS);
        $submitasurl = $submitasurl->out();
        $submitastext = get_string('submitas', 'local_helpdesk');

        $output .= "<a href=\"$submitasurl\">$submitastext</a><br />";

        if ($CFG->local_helpdesk_allow_external_users) {
            $manageurl = new moodle_Url("$CFG->wwwroot/local/helpdesk/userlist.php");
            $manageurl->param('function', HELPDESK_USERLIST_MANAGE_EXTERNAL);
            $manageurl = $manageurl->out();
            $managetext = get_string('manageexternallink', 'local_helpdesk');
            $output .= "<a href=\"$manageurl\">$managetext</a><br />";
        }
        $output .= "<br />";
    }

    // print a footer.
    $url = new moodle_url("$CFG->wwwroot/local/helpdesk/preferences.php");
    $url = $url->out();
    $translated = get_string('preferences');
    $prefhelp = $OUTPUT->help_icon('pref', 'local_helpdesk');
    $output .=  "<a href=\"$url\">$translated</a>$prefhelp";
    $pluginname = get_string('pluginname', 'local_helpdesk');
    echo $OUTPUT->header();
    echo $output;
    //echo $OUTPUT->heading($pluginname);
    echo $OUTPUT->footer();





