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
 * Manual Course Mirror synchronization page.
 *
 * @package    local_coursemirror
 * @copyright  2026 Louisiana State University
 * @copyright  2026 Robert Russo 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_coursemirror_run');

$context = context_system::instance();
require_capability('local/coursemirror:manage', $context);

$run = optional_param('run', 0, PARAM_BOOL);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$url = new moodle_url('/local/coursemirror/index.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('runsync', 'local_coursemirror'));
$PAGE->set_heading(get_string('runsync', 'local_coursemirror'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('runsync', 'local_coursemirror'));

if ($run && confirm_sesskey() && $confirm) {
    echo html_writer::tag('pre', '');

    ob_start();
    $summary = \local_coursemirror\local\sync::run(true);
    $trace = ob_get_clean();

    echo html_writer::tag('pre', s($trace));

    echo $OUTPUT->notification(
        get_string('synccomplete', 'local_coursemirror') .
        " Checked: {$summary['checked']};" .
        " Created: {$summary['created']};" .
        " Skipped: {$summary['skipped']};" .
        " Failed: {$summary['failed']}.",
        \core\output\notification::NOTIFY_SUCCESS
    );
} else if ($run && confirm_sesskey()) {
    $continueurl = new moodle_url('/local/coursemirror/index.php', [
        'run' => 1,
        'confirm' => 1,
        'sesskey' => sesskey(),
    ]);

    echo $OUTPUT->confirm(
        get_string('runsyncnow', 'local_coursemirror'),
        $continueurl,
        $url
    );
} else {
    $runurl = new moodle_url('/local/coursemirror/index.php', [
        'run' => 1,
        'sesskey' => sesskey(),
    ]);

    echo html_writer::link(
        $runurl,
        get_string('runsyncnow', 'local_coursemirror'),
        ['class' => 'btn btn-primary']
    );
}

echo $OUTPUT->heading(get_string('viewlogs', 'local_coursemirror'), 3);
$sql = "SELECT * FROM {local_coursemirror_log} WHERE status != 'skipped' ORDER BY timecreated DESC LIMIT 100";
$logs = $DB->get_records_sql($sql);

$table = new html_table();
$table->head = [
    'Time',
    'Status',
    'Shortname',
    'Fullname',
    'ID Number',
    'Message',
];

foreach ($logs as $log) {
    $table->data[] = [
        userdate($log->timecreated),
        s($log->status),
        s($log->shortname),
        s($log->fullname),
        s($log->idnumber),
        s($log->message),
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
