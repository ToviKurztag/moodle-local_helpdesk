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
require_once($CFG->libdir.'/tablelib.php');
require_once("$CFG->dirroot/local/helpdesk/lib.php");

$PAGE->set_context(context_system::instance());
$PAGE->set_heading(get_string('pluginname', 'local_helpdesk'));
$PAGE->set_title(get_string('pluginname', 'local_helpdesk'));
$PAGE->set_url('/local/helpdesk/myquestions.php');

// $PAGE->requires->js('/local/helpdesk/js/jquery.dataTables.js');
 $PAGE->requires->css('/local/helpdesk/styles.css');
// $PAGE->requires->css('/local/helpdesk/css/jquery.dataTables.css');
// $PAGE->requires->js('/local/helpdesk/js/sort.js');


$PAGE->set_pagelayout('standard');
global $CFG, $USER, $OUTPUT, $DB, $USER;
$output = '';
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
        10, 0
    );
    if (!empty($tickets->count)) {
        $sort = '';
        $startpage = false;
        $pagecount = false;
        ob_start();
        $table = new flexible_table('mytickets');
        $baseurl = new moodle_url("/local/helpdesk/myquestions.php");
       // $baseurl = new moodle_url('../helpdesk/view.php', array ('id'=>$id));
        $tablecolumns = array(
            'summary',
            'user',
            'supporter',
            'status',
            'timecreated',
            'timemodified',
            'institution',
            'priority'
        );
        $table->define_columns($tablecolumns);
        $tableheaders = array(get_string('summary', 'local_helpdesk'),
        get_string('user', 'local_helpdesk'),
        get_string('supporter', 'local_helpdesk'),
        get_string('status', 'local_helpdesk'),
        get_string('timecreated', 'local_helpdesk'),
        get_string('timemodified', 'local_helpdesk'),
        get_string('institution', 'local_helpdesk'),
        get_string('priority', 'local_helpdesk'),
       );
        $table->define_headers($tableheaders);
        $table->define_baseurl($baseurl);
        $table->set_attribute('cellspacing', '0');
        $table->set_attribute('id', 'tickets');
        $table->set_attribute('class', 'flexible generaltable generalbox');
        $table->text_sorting('user');
        $table->sortable(true, 'user', SORT_ASC);
        $table->sortable(true, 'supporter', SORT_ASC);
        $table->sortable(true, 'status', SORT_ASC);
        $table->sortable(true, 'timecreated', SORT_ASC);
        $table->sortable(true, 'timemodified', SORT_ASC);
        $table->sortable(true, 'institution', SORT_ASC);
        $table->sortable(true, 'priority', SORT_DESC);
        $table->pageable(true);
         
        //print_r($table->is_sortable('supporter'));die; 
        $table->set_control_variables(array(
            TABLE_VAR_SORT    => 'ssort',
            TABLE_VAR_IFIRST  => 'sifirst',
            TABLE_VAR_ILAST   => 'silast',
            TABLE_VAR_PAGE    => 'spage'
            ));
        // $table->no_sorting('status');
        // $table->no_sorting('select');
        $table->setup();
        $table->initialbars(true);
        if ($table->get_sql_sort()) {
            $sort = 'ORDER BY '. $table->get_sql_sort();
        } else {
            $sort = '';
        }
        // $table->pagesize($perpage, $countnonrespondents);
        // $startpage = $table->get_page_start();
        // $pagecount = $table->get_page_size();
        foreach ($tickets->results as $ticket) {
            $warningclass = '';
            $time = time() - 1209600;
            $hduser = helpdesk_get_user($USER->id);
            $hduseridsuporter = $hduser->hd_userid;
            if ($ticket->get_responded_ticket($ticket, $hduseridsuporter)) {
                $warningclass .= 'respondedclass';
            }
            if ($time > $ticket->get_timemodified()) {
                $warningclass = $warningclass . ' warningclass';
            }
            $url = new moodle_url("$CFG->wwwroot/local/helpdesk/view.php");
            $url->param('id', $ticket->get_idstring());
            $url = $url->out();
            $row = array("<div class= ".$warningclass.">" ."<a href=\"$url\">" . $ticket->get_summary() . '</a>'. "</div>",
                                   "<div class= ".$warningclass.">" .$ticket->get_user_name($ticket->hd_userid) . "</div>",
                                   "<div class= ".$warningclass.">" .$ticket->get_assigned_string($ticket->get_assigned()). "</div>",
                                   "<div class= ".$warningclass.">" .$ticket->get_status_string(). "</div>",
                                   "<div class= ".$warningclass.">" .helpdesk_get_date_string($ticket->get_timecreated()). "</div>",
                                   "<div class= ".$warningclass.">" .helpdesk_get_date_string($ticket->get_timemodified()). "</div>",
                                   "<div class= ".$warningclass.">" .$ticket->get_user_institution($ticket->hd_userid). "</div>",
                                   "<div class= ".$warningclass.">" .$ticket->get_priority_string($ticket->priority). "</div>"
                                   );
            $table->add_data($row);
            //print_r($row);die;
        }
      //  $output .= $table;
    //   $table->out($pagesize, $useinitialsbar);
        $output .= $table->finish_html();
        $output .= ob_get_clean();
    } else {
        $output .= $OUTPUT->notification($noticketstr, 'notifyproblem');
    }
    //echo $output;die;
}
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
        $manageurl = new moodle_url("$CFG->wwwroot/local/helpdesk/userlist.php");
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





