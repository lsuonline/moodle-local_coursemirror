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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Administrative settings for the Course Mirror local plugin.
 *
 * @package    local_coursemirror
 * @copyright  2026 Louisiana State University
 * @copyright  2026 Robert Russo 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_coursemirror',
        get_string('pluginname', 'local_coursemirror')
    );

    $settings->add(new admin_setting_configtext(
        'local_coursemirror/remoteurl',
        get_string('remoteurl', 'local_coursemirror'),
        get_string('remoteurl_desc', 'local_coursemirror'),
        '',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_coursemirror/token',
        get_string('token', 'local_coursemirror'),
        get_string('token_desc', 'local_coursemirror'),
        ''
    ));

    $periods = [];

    if ($DB->get_manager()->table_exists('enrol_wds_periods')) {
        $records = $DB->get_records_sql("
            SELECT academic_period_id, academic_period_id AS label
                 FROM {enrol_wds_periods}
             WHERE enabled = 1
         ORDER BY period_year DESC,
             period_type ASC,
             academic_period_id ASC
        ");

        foreach ($records as $record) {
            $periods[$record->academic_period_id] = $record->academic_period_id;
        }
    }

    $settings->add(new admin_setting_configmultiselect(
        'local_coursemirror/academicperiods',
        get_string('academicperiods', 'local_coursemirror'),
        get_string('academicperiods_desc', 'local_coursemirror'),
        [],
        $periods
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_coursemirror/createvisible',
        get_string('createvisible', 'local_coursemirror'),
        get_string('createvisible_desc', 'local_coursemirror'),
        0
    ));

    $ADMIN->add('localplugins', $settings);

    $ADMIN->add('localplugins', new admin_externalpage(
        'local_coursemirror_run',
        get_string('runsync', 'local_coursemirror'),
        new moodle_url('/local/coursemirror/index.php'),
        'local/coursemirror:manage'
    ));
}
