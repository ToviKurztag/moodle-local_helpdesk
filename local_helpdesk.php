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
 * This script extends a moodle local_base and is the entry point for all
 * helpdesk  ability.
 *
 * @package     local_helpdesk
 * @copyright   2010 VLACS
 * @author      Jonathan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die("Direct access to this location is not allowed.");

require_once("$CFG->dirroot/local/helpdesk/lib.php");

class local_helpdesk extends local_base {
    private $hd;
    /**
     * Overridden local_base method. All this method does is sets the local's
     * title and version.
     *
     * @return null
     */
    public function init() {
        global $CFG;
        $this->title = !empty($CFG->local_helpdesk_local_name) ?
            $CFG->local_helpdesk_local_name : get_string('helpdesk', 'local_helpdesk');
        $this->cron = 1;
    }

    /**
     * Overridden method that gets called every time. This is the only place to
     * make sure the help desk gets installed.
     *
     * @return null
     */
    public function specialization() {
        global $DB;
        // If no core statuses, install the plugin.
        // TODO: Make this less brain dead. (I agree, it should be moved into a install.php script).
        $hd = helpdesk::get_helpdesk();
        if (!$hd->is_installed()) {
            $hd->install();
        }
    }

    /**
     * Overridden local_base method. This generates the content in the body of
     * the local and returns it.
     *
     * @return string
     */
    public function get_content() {
        global $CFG, $USER, $OUTPUT;
        // Get objects initialized and variables declared.

        $this->content = new stdClass;

        // First thing is first, user must have some form of capbility on the
        // helpdesk. Otherwise they shouldn't be able to access it.
        $cap = helpdesk_is_capable();
        $this->content->text = '';
        $this->content->footer = '';
        $noticketstr = get_string('noticketstodisplay', 'local_helpdesk');
        if ($cap == false || empty($USER->id)) {
            return $this->content;
        }

        // Lets get the helpdesk initialized.
        $hd = helpdesk::get_helpdesk();

        // Show assigned ticket if answerer.
        if ($cap == HELPDESK_CAP_ANSWER) {
            $title = '<h4>' . get_string('myassignedtickets', 'local_helpdesk') . '</h4>';
            $this->content->text .= $title;

            $tickets = $hd->search(
                $hd->get_ticket_relation_search($hd->get_default_relation(HELPDESK_CAP_ANSWER)),
                5, 0
            );
            if (!empty($tickets->count)) {
                $table = new html_table();
                $table->head = array(get_string('summary', 'local_helpdesk'),
                                     get_string('status', 'local_helpdesk'),
                                     get_string('timecreated', 'local_helpdesk'),
                                     get_string('timemodified', 'local_helpdesk')
                                     //get_string('status', 'local_helpdesk'),
                                     );
                $table->data = [];
                foreach ($tickets->results as $ticket) {
                    $url = new moodle_url("$CFG->wwwroot/local/helpdesk/view.php");
                    $url->param('id', $ticket->get_idstring());
                    $url = $url->out();
                    $table->data[] = array($ticket->get_summary(),
                                           $ticket->get_status_string(),
                                           helpdesk_get_date_string($ticket->get_timecreated()),
                                           helpdesk_get_date_string($ticket->get_timemodified())
                                           );
                }
                $this->content->text .= html_writer::table($table);
            } else {
                $this->content->text .= $OUTPUT->notification($noticketstr, 'notifyproblem');
            }
        }

        // Print my tickets title. local itself just displays first 5 user
        // tickets. Other tickets are found in ticket listing.
        $this->content->text .= '<h4>' . get_string('mytickets', 'local_helpdesk') . '</h4>';

        // Grab the tickets to display and add to the content.
        $hduser = helpdesk_get_user($USER->id);
        $so = $hd->new_search_obj();
        $so->watcher = $hduser->hd_userid;
        $so->status = $hd->get_status_ids(true, false);
        $tickets = $hd->search($so, 5, 0);
        if (!empty($tickets->count)) {
            $table = new html_table();
            $table->head = array(get_string('summary', 'local_helpdesk'),
                                 get_string('status', 'local_helpdesk'),
                                 get_string('timecreated', 'local_helpdesk'),
                                 get_string('timemodified', 'local_helpdesk')
                                 //get_string('status', 'local_helpdesk'),
                                 );
            $table->data = [];
            foreach ($tickets->results as $ticket) {
                $url = new moodle_url("$CFG->wwwroot/local/helpdesk/view.php");
                $url->param('id', $ticket->get_idstring());
                $url = $url->out();

                $table->data[] = array($ticket->get_summary(),
                                       $ticket->get_status_string(),
                                       helpdesk_get_date_string($ticket->get_timecreated()),
                                       helpdesk_get_date_string($ticket->get_timemodified())
                                       );
            }
            $this->content->text .= html_writer::table($table);
        } else {
            $this->content->text .= $OUTPUT->notification($noticketstr, 'notifyproblem');
        }

        // Link for viewing all kinds of tickets.
        $url = "$CFG->wwwroot/local/helpdesk/search.php";
        $text = get_string('viewalltickets', 'local_helpdesk');
        $this->content->text .= "<a class=\"btn btn-primary\" href=\"$url\">$text</a>";

        // Link for submitting a new ticket.
        $url = $hd->default_submit_url()->out();
        $text = !empty($CFG->local_helpdesk_submit_text) ?
                        $CFG->local_helpdesk_submit_text : get_string('submitnewticket', 'local_helpdesk');
        $this->content->text .= "<a class=\"btn btn-primary\" href=\"$url\">$text</a><br /><br />";

        if (helpdesk_is_capable(HELPDESK_CAP_ANSWER)) {
            $submitasurl = new moodle_url("$CFG->wwwroot/local/helpdesk/userlist.php");
            $submitasurl->param('function', HELPDESK_USERLIST_SUBMIT_AS);
            $submitasurl = $submitasurl->out();
            $submitastext = get_string('submitas', 'local_helpdesk');





            $this->content->text .= "<a href=\"$submitasurl\">$submitastext</a><br />";

            if ($CFG->local_helpdesk_allow_external_users) {
                $manageurl = new moodle_Url("$CFG->wwwroot/local/helpdesk/userlist.php");
                $manageurl->param('function', HELPDESK_USERLIST_MANAGE_EXTERNAL);
                $manageurl = $manageurl->out();
                $managetext = get_string('manageexternallink', 'local_helpdesk');
                $this->content->text .= "<a href=\"$manageurl\">$managetext</a><br />";
            }
            $this->content->text .= "<br />";
        }

        // print a footer.
        $url = new moodle_url("$CFG->wwwroot/local/helpdesk/preferences.php");
        $url = $url->out();
        $translated = get_string('preferences');
        $prefhelp = $OUTPUT->help_icon('pref', 'local_helpdesk');
        $this->content->footer = "<a href=\"$url\">$translated</a>$prefhelp";

        return $this->content;
    }

    /**
     * This is an overriden method. This method is called when Moodle's cron
     * runs. Currently this method does nothing and returns nothing.
     *
     * @return null
     */
    public function cron() {
        global $OUTPUT;

        $hd = helpdesk::get_helpdesk();
        if (!$hd->cron()) {
            echo $OUTPUT->notification('Warning: Plugin cron returned non-true value.');
        }
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function has_config() {
        return true;
    }

    public function instance_allow_config() {
        return false;
    }
}
