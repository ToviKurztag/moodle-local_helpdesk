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
 * This is the moodle native plugin for the helpdesk. This plugin is a basic
 * helpdesk that is built into the helpdesk local. This is initially the only
 * option and will become the default option.
 *
 * @package     local_helpdesk
 * @copyright   2010 VLACS
 * @author      Jonathan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die("Direct access to this location is not allowed.");

class helpdesk_native extends helpdesk {
    /**
     * helpdesk_native constructor. Nothing special, but this isn't called
     * directly. Help Desk base class has a factory function for construction.
     *
     * @return null
     */
    function __construct() {
        // This is now empty. No more native viewer.
    }

    /**
     * install script. this runs after the tables have been made.
     *
     * @return bool
     */
    function install() {
        global $DB;

        // Lets define base statuses.
        $new = new stdClass;
        $new->name = 'new';
        $new->core = 1;
        $new->ticketdefault = 1;
        $new->active = 1;

        $wip = clone $new;
        $wip->name = 'workinprogress';
        $wip->ticketdefault = 0;

        $closed = clone $wip;
        $closed->name = 'closed';
        $closed->active = 0;

        $resolved = clone $closed;
        $resolved->name = 'resolved';

        $reopen = clone $wip;
        $reopen->name = 'reopened';

        $nmi = clone $wip;
        $nmi->name = 'needmoreinfo';

        $ip = clone $nmi;
        $ip->name = 'pending';

        // Lets add all of our statuses.
        $rval = true;
        $rval = $rval and $new->id = $DB->insert_record('local_helpdesk_status', $new);
        $rval = $rval and $wip->id = $DB->insert_record('local_helpdesk_status', $wip);
        $rval = $rval and $closed->id = $DB->insert_record('local_helpdesk_status', $closed);
        $rval = $rval and $resolved->id = $DB->insert_record('local_helpdesk_status', $resolved);
        $rval = $rval and $reopen->id = $DB->insert_record('local_helpdesk_status', $reopen);
        $rval = $rval and $nmi->id = $DB->insert_record('local_helpdesk_status', $nmi);
        $rval = $rval and $ip->id = $DB->insert_record('local_helpdesk_status', $ip);

        // If one failed, we're doomed.
        if (!$rval) {
            print_error('Error adding statuses to the status table.');
        }

        // Here is the complex part. We need to do some default mappings here.
        // From New
        // For Answerer
        $rval = $rval and $this->add_status_path($new, $wip, HELPDESK_CAP_ANSWER);
        $rval = $rval and $this->add_status_path($new, $nmi, HELPDESK_CAP_ANSWER);
        $rval = $rval and $this->add_status_path($new, $resolved, HELPDESK_CAP_ANSWER);
        $rval = $rval and $this->add_status_path($new, $closed, HELPDESK_CAP_ANSWER);
        // For Asker
        $rval = $rval and $this->add_status_path($new, $wip, HELPDESK_CAP_ASK);
        $rval = $rval and $this->add_status_path($new, $closed, HELPDESK_CAP_ASK);

        // From WIP
        // For Answerer.
        $rval = $rval and $this->add_status_path($wip, $nmi, HELPDESK_CAP_ANSWER);
        $rval = $rval and $this->add_status_path($wip, $closed, HELPDESK_CAP_ANSWER);
        $rval = $rval and $this->add_status_path($wip, $resolved, HELPDESK_CAP_ANSWER);
        // For Asker.
        $rval = $rval and $this->add_status_path($wip, $closed, HELPDESK_CAP_ASK);

        // From Need More Info.
        // For Answerer.
        $rval = $rval and $this->add_status_path($nmi, $ip, HELPDESK_CAP_ANSWER);
        $rval = $rval and $this->add_status_path($nmi, $wip, HELPDESK_CAP_ANSWER);
        $rval = $rval and $this->add_status_path($nmi, $closed, HELPDESK_CAP_ANSWER);
        // For Asker.
        $rval = $rval and $this->add_status_path($nmi, $ip, HELPDESK_CAP_ASK);
        $rval = $rval and $this->add_status_path($nmi, $closed, HELPDESK_CAP_ASK);

        // From Info Provided.
        // For Answerer
        $rval = $rval and $this->add_status_path($ip, $wip, HELPDESK_CAP_ANSWER);
        $rval = $rval and $this->add_status_path($ip, $nmi, HELPDESK_CAP_ANSWER);
        $rval = $rval and $this->add_status_path($ip, $closed, HELPDESK_CAP_ANSWER);
        $rval = $rval and $this->add_status_path($ip, $resolved, HELPDESK_CAP_ANSWER);
        // For Askers.
        $rval = $rval and $this->add_status_path($ip, $closed, HELPDESK_CAP_ANSWER);

        // From Closed.
        // For Answerers.
        $rval = $rval and $this->add_status_path($closed, $reopen, HELPDESK_CAP_ANSWER);
        // For Askers.
        $rval = $rval and $this->add_status_path($closed, $reopen, HELPDESK_CAP_ASK);

        // From Resolved.
        // For Answerers.
        $rval = $rval and $this->add_status_path($resolved, $reopen, HELPDESK_CAP_ANSWER);
        // For Askers.
        $rval = $rval and $this->add_status_path($resolved, $reopen, HELPDESK_CAP_ASK);

        // From reopen.
        // For Answerers.
        $rval = $rval and $this->add_status_path($reopen, $wip, HELPDESK_CAP_ANSWER);
        $rval = $rval and $this->add_status_path($reopen, $nmi, HELPDESK_CAP_ANSWER);
        $rval = $rval and $this->add_status_path($reopen, $closed, HELPDESK_CAP_ANSWER);
        $rval = $rval and $this->add_status_path($reopen, $resolved, HELPDESK_CAP_ANSWER);
        // For Askers.
        $rval = $rval and $this->add_status_path($reopen, $closed, HELPDESK_CAP_ASK);

        // We're also doomed if we can't add all of the mappings.
        if ($rval == false) {
            print_error('Error adding status paths.');
        }

        return $rval;
    }

    function is_installed() {
        global $DB;

        return $DB->record_exists('local_helpdesk_status', array('core' => 1));
    }

    /**
     * Gets language string associated with a relation.
     *
     * @param string    $rel is the basic string of the relation.
     * @return string
     */
    function get_relation_string($rel) {
        return get_string($rel, 'local_helpdesk');
    }

    /**
     * This creates a path from one status to another based on capability.
     *
     * @param object    $from is a status record. This defines the inital
     *                  status.
     * @param object    $to is a status record of the possible next status.
     * @param string    $capability is a capability string to check users by.
     * @return bool
     */
    function add_status_path($from, $to, $capability) {
        global $DB;

        $obj = new stdClass;
        $obj->fromstatusid = $from->id;
        $obj->tostatusid = $to->id;
        $obj->capabilityname = $capability;
        return $DB->insert_record('local_helpdesk_status_path', $obj);
    }

    /**
     * This method get the default ticket status from the database.
     *
     * @return object
     */
    function get_default_status() {
        global $DB;

        return $DB->get_record('local_helpdesk_status', array('ticketdefault' => 1));
    }

    /**
     * This method gets the possible status changes from a given status. Can
     * also manually specify a specific capability. User's capability will be
     * used if $cap is null.
     *
     * @param mixed     $status is a status id or status object.
     * @param string    $cap is the name of the capability.
     * @return array
     */
    function get_status_paths($status, $cap=null) {
        global $DB;

        $id = null;
        if (is_object($status)) {
            $id = $status->id;
        } else if (is_numeric($status)) {
            $id = $status;
        } else {
            return false;
        }

        if (is_null($cap)) {
            $cap = helpdesk_is_capable();
        }

        $capid = $DB->get_field('capabilities', 'id', array('name' => $cap));
    }

    /**
     * Cron method that runs with local's cron.
     *
     * @return true
     */
    function cron() {
        $this->email_idle();
        return true;
    }

    /**
     * Emails assignees of tickets for being idle.
     *
     * @return bool
     */
    private function email_idle() {
        $idle = get_config(null, 'local_helpdesk_ticket_idle_dur');
        if ($idle == 0) {
            return true;
        }

        // Fetches all idle tickets based on config settings.
        $tickets = $this->get_idle_tickets();

        // We must email assignees only.
        $admin = get_admin();
        $admin->hd_userid = helpdesk_ensure_hd_user($admin->id);
        foreach($tickets as $ticket) {
            $this->email_idle_notification($ticket);
            $update = new stdClass();
            $update->type = HELPDESK_UPDATE_TYPE_SYSTEM;
            $update->notes_editor['text'] = get_string('idleemailsent', 'local_helpdesk');
            $update->notes_editor['format'] = FORMAT_HTML;
            $update->status = HELPDESK_NATIVE_UPDATE_COMMENT;
            $update->hd_userid = $admin->hd_userid;
            $update->hidden = false;
            $ticket->add_update($update);
        }
        return true;
    }

    private function get_idle_tickets($hd_userid=null, $offset='', $count='') {
        global $USER, $DB;

        // Askers can get their own tickets only.
        $duration = get_config(null, 'local_helpdesk_ticket_idle_dur');
        if ($duration === 0 or $duration == false) {
            return true;
        }

        $hd_user = helpdesk_get_user($USER->id);
        if ($hd_user->hd_userid == $hd_userid) {
            helpdesk_is_capable(HELPDESK_CAP_ASK, true);
        } else {
            helpdesk_is_capable(HELPDESK_CAP_ANSWER, true);
        }

        $now = time();
        $before = $now - (3600 * $duration);

        $where = "timemodified <= $before";

        if ($hd_userid != null) {
            $where .= " AND hd_userid = $hd_userid";
        }

        $records = $DB->get_records_select('local_helpdesk_ticket', $where, 'timemodified DESC',
                                      'id, status', $offset, $count);

        if (empty($records)) {
            return false;
        }

        foreach($records as $record) {
            if ($record->status == HELPDESK_NATIVE_STATUS_CLOSED) {
                continue;
            }
            $ticket = $this->new_ticket();
            $ticket->get_ticket($record->id);
            $tickets[] = $ticket;
        }
        return $tickets;
    }

    /**
     * Checks to see if an update is hidden or not.
     *
     * @param object    $update that may be hidden.
     * @return bool
     */
    function is_update_hidden($update) {
        if (!is_object($update)) {
            return false;
        }
        if ($update->hidden == null or $update->hidden == false) {
            return false;
        }
        return true;
    }

    /**
     * Will email an idle notification for a particular ticket.
     *
     * @param object    $ticket is a ticket object.
     * @return bool
     */
    function email_idle_notification($ticket) {
        global $CFG, $OUTPUT;
        if (get_config(null, 'local_helpdesk_ticket_idle_dur') == false) {
            return true;
        }
        $supportuser = new stdClass;
        $supportuser = get_admin();
        $supportuser->email = get_config(null, 'local_helpdesk_email_addr');
        $supportuser->firstname = get_config(null, 'local_helpdesk_email_name');
        $supportuser->lastname = '';
        $text = get_config(null, 'local_helpdesk_idle_content');
        $html = get_config(null, 'local_helpdesk_idle_htmlcontent');
        $emailsubject = get_config(null, 'local_helpdesk_idle_subject');

        $users = $this->process_watchers_to_email($ticket->get_watchers());

        $userticketurl = "$CFG->wwwroot/local/helpdesk/view.php?id={$ticket->get_idstring()}";
        foreach($users as $user) {
            if (isset($user->token)) {
                $url = "{$userticketurl}&token={$user->token}";
            } else {
                $url = $userticketurl;
            }
            $link = "<a href=\"$url\">$url</a>";

            $emailtext = str_replace('!username!', fullname_nowarnings($user), $text);
            $emailhtml = str_replace('!username!', fullname_nowarnings($user), $html);
            $emailtext = str_replace('!ticketlink!', $url, $emailtext);
            $emailhtml = str_replace('!ticketlink!', $link, $emailhtml);
            $emailtext = str_replace('!supportname!', $supportuser->firstname, $emailtext);
            $emailhtml = str_replace('!supportname!', $supportuser->firstname, $emailhtml);

            if(empty($user->id)) {
                $rval = email_to_external_user($user, $supportuser, $emailsubject, $emailtext, $emailhtml);
            } else {
                $rval = email_to_user($user, $supportuser, $emailsubject, $emailtext, $emailhtml);
            }
            if ($rval === false) {
                echo $OUTPUT->notification(get_string('failedtosendemail', 'local_helpdesk'));
            }
        }

        return true;
    }

    /**
     * Will email an update for a particular ticket.
     *
     * @param object    $to is a moodle {@link $USER} object.
     * @param string    $basic is the non-html version of the email.
     * @param string    $html is the html version of the email.
     * @return bool
     */
    function email_update($ticket) {
        global $CFG, $USER, $OUTPUT;
        if(get_config(null, 'local_helpdesk_send_update_email') == false) {
            return true;
        }
        $supportuser = new stdClass;
        $supportuser = get_admin();
        $address = get_config(null, 'local_helpdesk_email_addr');

        if (!empty($address)) {
            $supportuser->email = $address;
        }
        $firstname = get_config(null, 'local_helpdesk_email_name');
        if (!empty($firstname)) {
            $supportuser->firstname = $firstname;
        }
        $supportuser->lastname = '';

        $text = get_config(null, 'local_helpdesk_email_content');
        $html = get_config(null, 'local_helpdesk_email_htmlcontent');
        $emailsubject = get_config(null, 'local_helpdesk_email_subject');
        if (empty($text)) {
            $text = get_string('emaildefaultnotestext', 'local_helpdesk');
            //set_config('local_helpdesk_email_content', $text);
        }
        if (empty($html) or strlen($html) == 0) {
            $html = '';
        }
        if (empty($emailsubject)) {
            $emailsubject = get_string('emaildefaultsubject', 'local_helpdesk');
            //set_config('local_helpdesk_email_subject', $emailsubject);
        }
        $emailsubject = str_replace('!ticketid!', $ticket->get_idstring(), $emailsubject);
        $users = $this->process_watchers_to_email($ticket->get_watchers());
        $userticketurl = "$CFG->wwwroot/local/helpdesk/view.php?id={$ticket->get_idstring()}";

        $updates = $ticket->get_updates(true);
        $lastupdate = end($updates);
        $visibleupdates = $ticket->get_user_updates(false);
        $lastvisibleupdate = end($visibleupdates);
      // print_r($ticket->get_user_name($lastvisibleupdate->hd_userid));die;
       // print_r($lastupdate);die;
        foreach ($users as $user) {
            // Dont send an email to the person making the update.
            if (!empty($USER->id) && !empty($user->id) && $user->id == $USER->id) {
                continue;
            }
            // Don't send an email if the user can't see a hidden update.
            if ($lastupdate->hidden and !helpdesk_is_capable(HELPDESK_CAP_ANSWER, false, $user)) {
                continue;
            }
            if (isset($user->token)) {
                $url = "{$userticketurl}&token={$user->token}";
            } else {
                $url = $userticketurl;
            }
            $link = "<a href=\"$url\">$url</a>";

            $emailtext = str_replace('!username!', fullname_nowarnings($user), $text);
            $emailhtml = str_replace('!username!', fullname_nowarnings($user), $html);
            $emailtext = str_replace('!ticketlink!', $url, $emailtext);
            $emailhtml = str_replace('!ticketlink!', $link, $emailhtml);
           // $emailtext = str_replace('!supportname!', $supportuser->firstname, $emailtext);
           // $emailhtml = str_replace('!supportname!', $supportuser->firstname, $emailhtml);
            $emailtext = str_replace('!updatetime!', helpdesk_get_date_string(time()), $emailtext);
            $emailhtml = str_replace('!updatetime!', helpdesk_get_date_string(time()), $emailhtml);

            $emailtext = str_replace('!ticketid!', $ticket->get_idstring(), $emailtext);
            $emailtext = str_replace('!title!', $ticket->summary, $emailtext);
            $emailtext = str_replace('!description!', $ticket->detail, $emailtext);
            $emailtext = str_replace('!update!', $lastvisibleupdate->notes , $emailtext);
            $emailtext = str_replace('!supportname!', $ticket->get_user_name($lastvisibleupdate->hd_userid), $emailtext);
            $emailtext = str_replace('!client!', $ticket->get_user_name($ticket->hd_userid), $emailtext);

            // if it's an external user, then use a custom email_to_user function that just skip.
            if (empty($user->id)) {
                $rval = email_to_external_user($user, $supportuser, $emailsubject, $emailtext, $emailhtml);
            } else {
                $rval = email_to_user($user, $supportuser, $emailsubject, $emailtext, $emailhtml);
            }
            if ($rval === false) {
                echo $OUTPUT->notification(get_string('failedtosendemail', 'local_helpdesk'));
            }
        }
        return true;
    }

    /**
     * Will email for assign user to ticket.
     *
     * @param object    $to is a moodle {@link $USER} object.
     * @param string    $basic is the non-html version of the email.
     * @param string    $html is the html version of the email.
     * @return bool
    */
    function email_assign($ticket, $newticket = 0) {
//print_r($ticket);die;
        global $CFG, $USER, $OUTPUT;
        if (get_config(null, 'local_helpdesk_send_update_email') == false) {
            return true;
        }
        $supportuser = new stdClass;
        $supportuser = get_admin();
        $address = get_config(null, 'local_helpdesk_email_addr');

        if (!empty($address)) {
            $supportuser->email = $address;
        }
        $firstname = get_config(null, 'local_helpdesk_email_name');
        if (!empty($firstname)) {
            $supportuser->firstname = $firstname;
        }
        $supportuser->lastname = '';

        $text = get_config(null, 'local_helpdesk_email_content');
        $html = get_config(null, 'local_helpdesk_email_htmlcontent');
        $emailsubject = get_config(null, 'local_helpdesk_email_subject');
        if (empty($text)) {
            $text = get_string('emaildefaultnotestext', 'local_helpdesk');
            //set_config('local_helpdesk_email_content', $text);
        }
        if (empty($html) or strlen($html) == 0) {
            $html = '';
        }
        if (empty($emailsubject)) {
            if ($newticket) {
                $emailsubject = get_string('emailnewticketsubject', 'local_helpdesk');
            } else {
                $emailsubject = get_string('emailassignsubject', 'local_helpdesk');
            }
        }
        $emailsubject = str_replace('!ticketid!', $ticket->get_idstring(), $emailsubject);
        $userticketurl = "$CFG->wwwroot/local/helpdesk/view.php?id={$ticket->get_idstring()}";

       // $user = end($ticket->get_assigned());
       // print_r($user);die;
        if ($newticket) {
            $user = get_admin();
        } else {
            $user = end($ticket->get_assigned());
           // print_r($user);die;
            $user = $this->process_assign_to_email($user);
            $visibleupdates = $ticket->get_assign_updates(false);
            $lastassignupdate = end($visibleupdates);
        }
       
            if (isset($user->token)) {
                $url = "{$userticketurl}&token={$user->token}";
            } else {
                $url = $userticketurl;
            }
            $link = "<a href=\"$url\">$url</a>";
            $emailtext = str_replace('!username!', fullname_nowarnings($user), $text);
            $emailhtml = str_replace('!username!', fullname_nowarnings($user), $html);
            $emailtext = str_replace('!ticketlink!', $url, $emailtext);
            $emailhtml = str_replace('!ticketlink!', $link, $emailhtml);
           // $emailtext = str_replace('!supportname!', $supportuser->firstname, $emailtext);
           // $emailhtml = str_replace('!supportname!', $supportuser->firstname, $emailhtml);
            $emailtext = str_replace('!updatetime!', helpdesk_get_date_string(time()), $emailtext);
            $emailhtml = str_replace('!updatetime!', helpdesk_get_date_string(time()), $emailhtml);

            $emailtext = str_replace('!ticketid!', $ticket->get_idstring(), $emailtext);
            $emailtext = str_replace('!title!', $ticket->summary, $emailtext);
            $emailtext = str_replace('!description!', $ticket->detail, $emailtext);
            $emailtext = str_replace('!supportname!', 'helpdesk', $emailtext);
            $emailtext = str_replace('!client!', $ticket->get_user_name($ticket->hd_userid), $emailtext);
            // if it's an external user, then use a custom email_to_user function that just skip.
            if ($newticket) {
                $emailtext = str_replace('!update!', $emailsubject , $emailtext);
                //$emailtext = str_replace('!update!', get_string('newticket', 'local_helpdesk') , $emailtext); 
            } else {
                $emailtext = str_replace('!update!', $emailsubject , $emailtext);
                //$emailtext = str_replace('!update!', $lastassignupdate->notes , $emailtext);
            }
            if (empty($user->id)) {
               // print_r($user);die;
                $rval = email_to_external_user($user, $supportuser, $emailsubject, $emailtext, $emailhtml);
            } else {
                $rval = email_to_user($user, $supportuser, $emailsubject, $emailtext, $emailhtml);
            }
            if ($rval === false) {
                echo $OUTPUT->notification(get_string('failedtosendemail', 'local_helpdesk'));
            }
        return true;
    }

    private function process_watchers_to_email($watchers) {
        global $CFG, $DB;

        $processed = array();
        if (!empty($watchers)) {
            foreach($watchers as $w) {
                if (isset($w->userid)) {
                    $w = $DB->get_record('user', array('id' => $w->userid));
                } else {
                    if (empty($CFG->local_helpdesk_external_user_tokens)) {
                        continue;
                    }
                    if (!strlen($w->token)) {
                        $w->token = helpdesk_generate_token();
                    }
                    $w->token_last_issued = time();
                    $DB->update_record('local_helpdesk_watcher', $w);
                    unset($w->id);  # Moodle's email functions thinks this is a user.id

                    $w->mailformat = 1;             # it's 2013, send html messages
                    $w->helpdesk_external = true;
                }
                $processed[] = $w;
            }
        }
        return $processed;
    }

    private function process_assign_to_email($assign) {
        global $CFG, $DB;
        if (!empty($assign)) {
          //  print_r($assign);die;
                //if (isset($assign->userid)) {
                   // print_r($assign);die;
                    $assign = $DB->get_record('user', array('id' => $assign->userid));
                // } else {
                //     if (empty($CFG->local_helpdesk_external_user_tokens)) {
                //         continue;
                //     }
                //     if (!strlen($assign->token)) {
                //         $assign->token = helpdesk_generate_token();
                //     }
                //     $assign->token_last_issued = time();
                //     $DB->update_record('local_helpdesk_watcher', $assign);
                //     unset($assign->id);  # Moodle's email functions thinks this is a user.id

                //     $assign->mailformat = 1;             # it's 2013, send html messages
                //     $assign->helpdesk_external = true;
                // }
               // $processed[] = $assign;
        }
        return $assign;
    }

    /**
     * This provides extra fields for the local configuration that are specific
     * to the plugin.
     *
     * @param object    $settings is a reference to the settings variable in the
     *                  help desk settings.php file.
     * @return bool
     */
    function plugin_settings(&$settings) {
        global $CFG;

        $settings->add(new admin_setting_heading('local_helpdesk_plugin',
                                                 get_string('pluginsettings', 'local_helpdesk'),
                                                 get_string('pluginsettingsdesc', 'local_helpdesk')));

        $settings->add(new admin_setting_configcheckbox('local_helpdesk_assigned_auto_watch',
                                                        get_string('assignedaswatchers', 'local_helpdesk'),
                                                        get_string('assignedaswatchersdesc', 'local_helpdesk'),
                                                        '1', '1', '0'));

        $settings->add(new admin_setting_configcheckbox('local_helpdesk_show_firstcontact',
                                                        get_string('showfirstcontact', 'local_helpdesk'),
                                                        get_string('showfirstcontactdesc', 'local_helpdesk'),
                                                        '0', '1', '0'));

        $settings->add(new admin_setting_configcheckbox('local_helpdesk_send_update_email',
                                                        get_string('sendemailupdate', 'local_helpdesk'),
                                                        get_string('sendemailupdatedesc', 'local_helpdesk'),
                                                        '0', '1', '0'));
        $settings->add(new admin_setting_configcheckbox('local_helpdesk_includeagent',
                                                        get_string('includeagent', 'local_helpdesk'),
                                                        get_string('includeagentdesc', 'local_helpdesk'),
                                                        '0', '1', '0'));

        //$settings->add(new admin_setting_configcheckbox('local_helpdesk_get_email_tickets',
        //                                                get_string('getemailtickets', 'local_helpdesk'),
        //                                                get_string('getemailticketsdesc', 'local_helpdesk'),
        //                                                '0', '1', '0'));

        $settings->add(new admin_setting_configtext('local_helpdesk_email_addr',
                                                    get_string('emailaddr', 'local_helpdesk'),
                                                    get_string('emailaddrdesc', 'local_helpdesk'),
                                                    '', PARAM_TEXT, 28));

        $settings->add(new admin_setting_configpasswordunmask('local_helpdesk_email_passwd',
                                                              get_string('emailpasswd', 'local_helpdesk'),
                                                              get_string('emailpasswddesc', 'local_helpdesk'),
                                                              '', PARAM_TEXT, 28));

        $settings->add(new admin_setting_configtext('local_helpdesk_email_name',
                                                    get_string('emailname', 'local_helpdesk'),
                                                    get_string('emailnamedesc', 'local_helpdesk'),
                                                    '', PARAM_TEXT));

        $settings->add(new admin_setting_configtext('local_helpdesk_email_subject',
                                                    get_string('emailsubject', 'local_helpdesk'),
                                                    get_string('emailsubjectdesc', 'local_helpdesk'),
                                                    get_string('emaildefaultsubject', 'local_helpdesk'),
                                                    PARAM_TEXT));

        $settings->add(new admin_setting_configtextarea('local_helpdesk_email_content',
                                                        get_string('emailcontent', 'local_helpdesk'),
                                                        get_string('emailcontentdesc', 'local_helpdesk'),
                                                        get_string('emaildefaultnotestext', 'local_helpdesk'),
                                                        PARAM_RAW));

        $base = 'admin_setting_config';
        $class = ($CFG->version >= 2007101590.00) ? "{$base}textarea" : "{$base}textarea";
        $settings->add(new admin_setting_confightmleditor('local_helpdesk_email_htmlcontent',
                                  get_string('emailrtfcontent', 'local_helpdesk'),
                                  get_string('emailrtfcontentdesc', 'local_helpdesk'),
                                  '', PARAM_RAW));

        return true;
    }

    /**
     * This is nice and all and will provide us with a method to get all the
     * useable relations. A $cap is required to get the right list. We don't
     * want users who can't be assigned to look at assigned tickets, that kind
     * of idea. We will do this now and not later.
     *
     * @param string    $cap is a capability of any particular user.
     * @return array
     */
    function get_ticket_relations($cap) {
        // We need a capability. We're not happy giving relation lists to just
        // anyone, so we have to test for this.
        if (empty($cap)) {
            return false;
        }
        // All users (except empty($cap) ones) get this.
        // Since we only have askers and answerers and answers have all the caps
        // that an asker has, we'll add these now.
        $relations = array(
            HELPDESK_NATIVE_REL_WATCHING,
//            HELPDESK_NATIVE_REL_REPORTEDBY,
            HELPDESK_NATIVE_REL_ALL,
            HELPDESK_NATIVE_REL_NEW,
            HELPDESK_NATIVE_REL_CLOSED,
            HELPDESK_NATIVE_REL_UNASSIGNED,
        );
        if ($cap == HELPDESK_CAP_ASK) {
            return $relations;
        }

        // Currently there should be no reason that a value other than
        // HELPDESK_CAP_ANSWER should get to this point.
        if ($cap != HELPDESK_CAP_ANSWER) {
            print_error('unexpectedcapability', 'local_helpdesk');
        }

        $relations[] = HELPDESK_NATIVE_REL_ASSIGNEDTO;
        return $relations;
    }
//Toviii
    /**
     * Specialized method to convert a relation into a set of search parameters.
     * This is the roadmap to being able to create custom search presets.
     *
     * @param int       $rel is a relation id.
     * @return object
     */
    function get_ticket_relation_search_user($rel, $user) {
        global $USER, $DB;
        // Setup base search criteria. We may want to make this a static method.
        $search = $this->new_search_obj();
        $currentuser = $user->id;
        $current_hd_user = helpdesk_get_user($user->id);
        switch($rel) {
        case HELPDESK_NATIVE_REL_REPORTEDBY:
            $search->status = $this->get_status_ids(true, false, false);
            $search->submitter = $current_hd_user->hd_userid;
            break;
        case HELPDESK_NATIVE_REL_WATCHING:
            $search->status = $this->get_status_ids(true, false, false);
            $search->watcher = $current_hd_user->hd_userid;
            break;
        case HELPDESK_NATIVE_REL_NEW:
            $search->status[] = $DB->get_field('local_helpdesk_status', 'id', array('name' => 'new'));
            break;
        case HELPDESK_NATIVE_REL_UNASSIGNED:
            $currentuser = 0;
        case HELPDESK_NATIVE_REL_ASSIGNEDTO:
            $search->status = $this->get_status_ids(true, false, false);
            $search->answerer = $currentuser;
            break;
        case HELPDESK_NATIVE_REL_CLOSED:
            $search->status = $this->get_status_ids(false, true, false);
            break;
        case HELPDESK_NATIVE_REL_ALL:
            $search->status = $this->get_status_ids(true, true, false);
            break;
        default:
            return false;
        }

        return $search;
    }

    function get_ticket_relation_search($rel) {
        global $USER, $DB;
        // Setup base search criteria. We may want to make this a static method.
        $search = $this->new_search_obj();
        $currentuser = $USER->id;
        $current_hd_user = helpdesk_get_user($USER->id);
        switch($rel) {
        case HELPDESK_NATIVE_REL_REPORTEDBY:
            $search->status = $this->get_status_ids(true, false, false);
            $search->submitter = $current_hd_user->hd_userid;
            break;
        case HELPDESK_NATIVE_REL_WATCHING:
            $search->status = $this->get_status_ids(true, false, false);
            $search->watcher = $current_hd_user->hd_userid;
            break;
        case HELPDESK_NATIVE_REL_NEW:
            $search->status[] = $DB->get_field('local_helpdesk_status', 'id', array('name' => 'new'));
            break;
        case HELPDESK_NATIVE_REL_UNASSIGNED:
            $currentuser = 0;
        case HELPDESK_NATIVE_REL_ASSIGNEDTO:
            $search->status = $this->get_status_ids(true, false, false);
            $search->answerer = $currentuser;
            break;
        case HELPDESK_NATIVE_REL_CLOSED:
            $search->status = $this->get_status_ids(false, true, false);
            break;
        case HELPDESK_NATIVE_REL_ALL:
            $search->status = $this->get_status_ids(true, true, false);
            break;
        default:
            return false;
        }

        return $search;
    }

     /**
     * Get method that returns user institution.
     *
     * @return string
     */
    function get_cooperative_institution($userid) {
        global $DB;
        $sql = 'SELECT DISTINCT hi.*
        FROM mdl_user as u
        JOIN mdl_local_helpdesk_institution as hi
        on u.institution = hi.institutionname
        where u.id = ?';

        $user = $DB->get_record_sql($sql, [$userid], $strictness = IGNORE_MISSING);
        return $user->cooperative;
    }
    
    /**
     * Get status ids based on criteria.
     *
     * @param bool      $active flag, get active statuses. default true.
     * @param bool      $inactive flag, opposite as active, default true.
     * @param bool      $core flag, get core statuses only, default false.
     * @return array
     */
    function get_status_ids($active=true, $inactive=true, $core=false) {
        global $DB;
        // not active and not inactive is nothing.
        $where = '';
        if($active == $inactive and $active == false) {
            return false;
        }
        if($active and !$inactive) {
            $where .= 'active = 1';
        }
        if(!$active and $inactive) {
            $where .= 'active = 0';
        }
        if($core) {
            $where .= !empty($where) ? ' ' : '';
            $where .= 'core = 1';
        }
        $sql = "
            SELECT id, name
            FROM {local_helpdesk_status}
        ";
        if(!empty($where)) {
            $sql .= "WHERE {$where}";
        }
        $stat = $DB->get_records_sql($sql);
        $ids = array();
        foreach($stat as $s) {
            $ids[] = $s->id;
        }
        return $ids;
    }

    /**
     * Determine which relation should be used by default to list
     * tickets for a user to see.
     *
     * @return string
     */
    function get_default_relation($cap=null) {
        switch($cap) {
        case HELPDESK_CAP_ANSWER:
            return HELPDESK_NATIVE_REL_ASSIGNEDTO;
        default:
            return HELPDESK_NATIVE_REL_REPORTEDBY;
        }
        return HELPDESK_NATIVE_REL_REPORTEDBY;
    }

    /**
     * This is an overriden method which returns a newly constructed
     * helpdesk_ticket_native object.
     *
     * @return object
     */
    function new_ticket() {
        return new helpdesk_ticket_native();
    }

    /**
     * Gets a ticket object with a given idstring, false otherwise.
     *
     * @param string    $id idstring of a ticket.
     * @return mixed
     */
    function get_ticket($id) {
        $ticket = $this->new_ticket();
        if(!$ticket->get_ticket($id)) {
            return false;
        }
        return $ticket;
    }

    /**
     * Get method that returns user institution.
     *
     * @return string
     */
    function get_user_institution($hduserid) {
        global $DB;
        //print_r($hduserid);die;
        $sql = 'select u.id, u.institution
        from mdl_local_helpdesk_hd_user as hu
        join mdl_user u
        on hu.userid = u.id
        where hu.id = ?';
        $user = $DB->get_record_sql($sql, [$hduserid], $strictness = IGNORE_MISSING);
        return $user->institution;
    }

    /**
     * This method searches tickets across multiple fields to find a match
     * according to a specific string. Basically we're "and"ing all the words
     * together and checking a bunch of stuff. We will get a mixed result, false
     * if unsucessful, or an array of tickets if we find matches.
     *
     * @param object    $data is an object with search attributes.
     * @return object   contains 3 attributes, results, count, and httpdata (or
     *                  false for no failed search.)
     */
    function search($data, $count=10, $page=0) {
        global $OUTPUT, $DB, $USER;
        $hd             = helpdesk::get_helpdesk();
        //print_r($data);die;
        if(is_string($data)) {
            print_error('deprecated search call parameter. I want an object, not a string. ROAR!');
        }

        if(!is_object($data)) {
            print_error('searchobjectexpected', 'local_helpdesk');
            return false;
        }

        $tmp                    = new stdClass;
        $tmp->searchstring      = $data->searchstring;
        $tmp->status            = $data->status;
        $tmp->submitter         = $data->submitter;
        if (!empty($data->watcher)) {
            $tmp->watcher           = $data->watcher;
        }
        //$tmp->institution       = 0;
        $tmp->answerer          = $data->answerer;
       // $tmp->institution       = $data->institution[0];
        //today
        if (helpdesk_is_capable(HELPDESK_CAP_ANSWER)) {
            $tmp->institution       = $data->institution[0];
        } else {
            $tmp->institution       = $data->institution;
        }
       // print_r($tmp->institution);die;
        $tmp->timecreatedfrom       = $data->timecreatedfrom;
        $tmp->timecreatedto       = $data->timecreatedto;
        $data                   = $tmp;
//print_r($data);die;
        // We need to search tickets and related values for the anded values of
        // these 'words'. We're going to pull it apart based on word.
        //$hduser = helpdesk_get_user($USER->id);
        //$userinstitution = $this->get_user_institution($hduser->hd_userid);
        $cname          = '[' . sha1(rand()) . ']';

        $words          = explode(' ', $data->searchstring);
        if (!$words) {
            echo $OUTPUT->notification(get_string('nosearchstring', 'local_helpdesk'));
            return false;
        }

        $selectsearch   = 'SELECT DISTINCT t.id, t.summary, t.detail, t.timemodified, t.priority';
        $selectcount    = 'SELECT COUNT(DISTINCT t.id)';

        $sqltickets = "
            FROM {local_helpdesk_ticket} AS t
            JOIN {local_helpdesk_hd_user} AS hu ON t.hd_userid = hu.id
            LEFT JOIN {user} AS u ON hu.userid = u.id
            LEFT JOIN {local_helpdesk_institution} AS hi ON u.institution = hi.institutionname
            LEFT JOIN {local_helpdesk_ticket_tag} AS tt ON t.id = tt.ticketid
        ";

        if($data->answerer <= 0) {
            $sqltickets .= "LEFT ";
        }
        $sqltickets    .= "JOIN {local_helpdesk_ticket_assign} AS hta ON t.id = hta.ticketid";
        $sqltickets    .= $data->answerer > 0 ? " AND hta.userid = $data->answerer " : '';
        $wheretickets   = array('t.summary', 't.detail', 'tt.value', 'u.firstname', 'u.lastname', 'hu.name');

        $params = array();
        $ticketsearchgroups = array();
        foreach ($words as $word) {
            if(empty($word)) {
                continue;
            }
            $ticketsubwhere = array();
            foreach ($wheretickets as $column) {
                $ticketsubwhere[] = $DB->sql_like($column, '?', false);
                $params[] = "%{$word}%";
            }
            if (is_numeric($word)) {
                $ticketsubwhere[] = "t.id = $word";
            }
            $ticketsearchgroups[] = implode(' OR ', $ticketsubwhere);
        }

        // Now lets finish our search query.
        // START BEWARE - The order of these checks is critical to performance.
        $andwhere = array();
        if (!empty($data->watcher)) {
            $andwhere[] = "EXISTS (SELECT 1 FROM {local_helpdesk_watcher} w WHERE w.ticketid = t.id AND w.hd_userid = $data->watcher)";
        }
        //Tovi
        if (!empty($data->timecreatedfrom)) {
            $andwhere[] = "t.timecreated > $data->timecreatedfrom";
        }
        if (!empty($data->timecreatedto)) {
            $data->timecreatedto = strtotime("+1 day", $data->timecreatedto);
            $andwhere[] = "t.timecreated < $data->timecreatedto";
        }

        if ($data->institution != 0) {
        $andwhere[] = "hi.id = $data->institution";
        }
        //Tovi>

        if(!empty($data->submitter)) {
            $andwhere[] = "t.hd_userid = $data->submitter";
        }
        if(!empty($data->status)) {
            $insql = implode(', ', $data->status);
            // Check that the $date array didn't contain only "false" values.
            if ($insql !== '') {
                $andwhere[] = 't.status IN ('.$insql.')';
            }
        }

        if($data->answerer == 0) {
            $andwhere[] = "hta.ticketid IS NULL";
        }

        if(!empty($ticketsearchgroups)) {
            $andwhere[] = '(' . implode(') AND (', $ticketsearchgroups) . ')';
        }

        $ticketwheresql = '';
        if (!empty($data->watcher) || !empty($data->institution)  || !empty($data->submitter) || $data->answerer == 0
            || !empty($ticketsearchgroups) || (!empty($data->status) && $insql !== '')) {
            $ticketwheresql = 'WHERE ' . implode(' AND ', $andwhere);
        }
        $result                 = new stdClass;
        // END BEWARE

        $orderby        = "ORDER BY timemodified DESC";
        $searchquery    = "SELECT * FROM ({$selectsearch} {$sqltickets} {$ticketwheresql}) AS foo {$orderby}";
        $countquery     = "{$selectcount} {$sqltickets} {$ticketwheresql}";

        $tidbitcount    = $DB->count_records_sql($countquery, $params);
        $offset         = $page * $count;
        //$DB->set_debug(true);
        $tidbits        = $DB->get_records_sql($searchquery, $params, $offset, $count);
        //$DB->set_debug(false);die;
        // print_r($tidbits);die;
        // var_dump($tidbits);die;
        $bigtickets     = array();
        if(!empty($tidbits)) {
            foreach($tidbits as $small_ticket) {
                $ticketobj = $this->new_ticket();
                $ticketobj->set_idstring($small_ticket->id);
                $ticketobj->fetch();
                $bigtickets[] = $ticketobj;
            }
        }

        //$result                 = new stdClass;
        $result->results        = $bigtickets;
        $result->count          = $tidbitcount;
        $result->httpdata       = base64_encode(serialize($data));
        return $result;
    }

    function change_overview_form($ticket) {
        global $CFG;
        $id = $ticket->get_idstring();
        if (empty($id)) {
            return false;
        }
        $url = new moodle_url("$CFG->wwwroot/local/helpdesk/edit.php");
        $url->param('id', $ticket->get_idstring());

        $user = helpdesk_get_hd_user($ticket->get_hd_userid());

        $context = context_system::instance();

        // Prepare the form file area.
        $editoroptions = array('maxfiles'=> 99, 'maxbytes'=>$CFG->maxbytes, 'context'=>$context);
        $ticket = file_prepare_standard_editor($ticket, 'detail', $editoroptions, $context,
                'local_helpdesk', 'ticketdetail', $id);
        $ticket->notes = '';
        $ticket->notesformat = FORMAT_HTML;
        $ticket = file_prepare_standard_editor($ticket, 'notes', $editoroptions, $context,
                'local_helpdesk', 'ticketnotes', 0);
        $ticket->username = fullname_nowarnings($user);
        $form = new change_overview_form($url->out(), array('ticket' => $ticket, 'editoroptions' => $editoroptions));

        return $form;
    }

    /**
     * Overridden method to return a moodle form object for a new ticket.
     *
     * @return object
     */
    function new_ticket_form($data=null) {
        global $CFG;
        $url = new moodle_url("$CFG->wwwroot/local/helpdesk/new.php");
        $form = new new_ticket_form($url, $data);
        if (!is_array($data)) {
            // Do nothing.
        } else {
            if (!empty($data['tags'])) {
                foreach ($data['tags'] as $key => $tag) {
                    $form->addHidden($key, $tag);
                    #$url->param($key, $tag);
                }
                $tags = implode(',', array_flip($data['tags']));
                $form->addHidden('tags', $tags);
                #$url->param('tags', $tags);
            }
            if (isset($data['hd_userid'])) {
                $form->addHidden('hd_userid', $data['hd_userid']);
            }
        }
        return $form;
    }

    /**
     * Overridden method to return a moodle form for searching.
     *
     * @return object
     */
    function search_form() {
        global $CFG;
        return new search_form("$CFG->wwwroot/local/helpdesk/search.php", null, 'post');
    }

    /**
     * Overridden method which creates and returns a moodle form for updating
     * a ticket. This method takes in one parameter, where $data is a ticket
     * object that belongs to the ticket that the update is being added to.
     *
     * @param object    $data Ticket that is being updated.
     * @return object
     */
    function update_ticket_form($data) {
        global $CFG, $USER;

        $url = new moodle_url($CFG->wwwroot . "/local/helpdesk/update.php",
            array('id' => $data->get_idstring()));
        if (!empty($USER->helpdesk_token)) {
            $url->param('token', $USER->helpdesk_token);
        }
        $context = context_system::instance();
        $editoroptions = array('maxfiles'=> 99, 'maxbytes'=>$CFG->maxbytes, 'context'=>$context);
        $data->notes = '';
        $data->notesformat = FORMAT_HTML;
        if (!empty($USER->id)) {
            $data = file_prepare_standard_editor($data, 'notes', $editoroptions, $context,
                    'local_helpdesk', 'ticketnotes', 0);
        }
        $form = new update_ticket_form($url->out(false),
                array('ticket' => $data, 'editoroptions' => $editoroptions), 'post');
        return $form;
    }

    /**
     * Help Desk method which creates and returns a moodle form for adding a tag
     * to a ticket.
     *
     * @param object    $ticket is a ticket object that this tag will belong to.
     * @return object
     */
    function tag_ticket_form($ticketid) {
        global $CFG;
        $url = new moodle_url("$CFG->wwwroot/local/helpdesk/tag.php");
        $url->param('tid', $ticketid);
        $context = context_system::instance();
        $editoroptions = array('maxfiles'=> 99, 'maxbytes'=>$CFG->maxbytes, 'context'=>$context);
        $tag = new stdClass();
        $tag->value = '';
        $tag->valueformat = FORMAT_HTML;
        file_prepare_standard_editor($tag, 'value', $editoroptions, $context,
            'local_helpdesk', 'tickettag', 0);
        $form = new tag_ticket_form($url->out(), array('editoroptions' => $editoroptions), 'post');
        return $form;
    }

    /**
     * This is an overridden method which takes in an array of records returned
     * by moodle and turns them into ticket objects. Only the ID field is
     * required in the record param. Returns false if none, or an array of
     * ticket objects.
     *
     * @param array     $records Records of tickets with an id field.
     * @return mixed
     */
    function parse_db_tickets($records) {
        if ($records == false or !is_array($records)) {
            return false;
        }
        $tickets = array();
        foreach($records as $record) {
            $ticket = $this->new_ticket();
            $ticket->set_idstring($record->id);
            if(!$ticket->fetch()) {
                return false;
            }
            $tickets[] = $ticket;
        }
        if (empty($tickets)) {
            return false;
        }
        return $tickets;
    }

    /**
     * This plugin supports tags, and includes some by default.
     *
     * Returns a moodle_url object for the 'submitnewticket' link in the local.
     *
     * @return object
     */
    function default_submit_url() {
        global $COURSE, $CFG;
        $url = new moodle_url("$CFG->wwwroot/local/helpdesk/new.php");
        $site = get_site();
        if ($site->id != $COURSE->id) {
            $url->param('tags', 'url,coursename');
            $url->param('url', qualified_me());
            $url->param('coursename', $COURSE->fullname);
        }
        return $url;
    }
}
