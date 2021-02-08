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
 * This script handles global settings for this Help Desk local.
 *
 * @package     local_helpdesk
 * @copyright   2010 VLACS
 * @author      Joanthan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die("Direct access to this location is not allowed.");

require_once("$CFG->dirroot/local/helpdesk/lib.php");

if ($hassiteconfig) {
    $pluginname = get_string('pluginname', 'local_helpdesk');

    $ADMIN->add('modules', new admin_externalpage('local_helpdesk',
            $pluginname,
            new moodle_url('/local/helpdesk/index.php')));

    $settings = new admin_settingpage('generalsettings', $pluginname);
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_heading('local_helpdesk_general',
    get_string('generalsettings', 'local_helpdesk'),
    get_string('generalsettingsdesc', 'local_helpdesk')));

    $settings->add(new admin_setting_configtext('local_helpdesk_local_name',
        get_string('localnameconfig', 'local_helpdesk'),
        get_string('localnameconfigdesc', 'local_helpdesk'),
        '', PARAM_TEXT, 100));

    $settings->add(new admin_setting_configtext('local_helpdesk_submit_text',
        get_string('submittextconfig', 'local_helpdesk'),
        get_string('submittextconfigdesc', 'local_helpdesk'),
        '', PARAM_TEXT));

    $settings->add(new admin_setting_configcheckbox('local_helpdesk_allow_external_users',
        get_string('allowexternal', 'local_helpdesk'),
        get_string('allowexternaldesc', 'local_helpdesk'),
        '0', '1', '0'));

    $settings->add(new admin_setting_configtext('local_helpdesk_user_types',
        get_string('usertypesconfig', 'local_helpdesk'),
        get_string('usertypesconfigdesc', 'local_helpdesk'),
        'student,teacher,guardian', PARAM_TEXT));

    $settings->add(new admin_setting_configcheckbox('local_helpdesk_external_user_tokens',
        get_string('allowexternaltokens', 'local_helpdesk'),
        get_string('allowexternaltokensdesc', 'local_helpdesk'),
        '0', '1', '0'));

    $settings->add(new admin_setting_configtext('local_helpdesk_token_exp',
        get_string('tokenexp', 'local_helpdesk'),
        get_string('tokenexpdesc', 'local_helpdesk'),
        HELPDESK_DEFAULT_TOKEN_EXP, PARAM_INT));

    $settings->add(new admin_setting_configcheckbox('local_helpdesk_external_updates',
        get_string('allowexternalupdates', 'local_helpdesk'),
        get_string('allowexternalupdatesdesc', 'local_helpdesk'),
        '0', '1', '0'));

    $hd = helpdesk::get_helpdesk();
    if (method_exists($hd, 'plugin_settings')) {
        $hd->plugin_settings($settings);
    }


}




















