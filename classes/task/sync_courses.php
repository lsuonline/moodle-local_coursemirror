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
 * Scheduled task for Course Mirror synchronization.
 *
 * @package    local_coursemirror
 * @copyright  2026 Louisiana State University
 * @copyright  2026 Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_coursemirror\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task that mirrors configured local courses to a remote Moodle site.
 */
class sync_courses extends \core\task\scheduled_task {

    /**
     * Return the scheduled task name.
     *
     * @return string Task display name.
     */
    public function get_name(): string {
        return get_string('pluginname', 'local_coursemirror');
    }

    /**
     * Execute the scheduled sync.
     *
     * @return void
     */
    public function execute(): void {
        $summary = \local_coursemirror\local\sync::run(true);

        mtrace(
            "Course mirror complete. Checked: {$summary['checked']}; " .
            "Created: {$summary['created']}; " .
            "Skipped: {$summary['skipped']}; " .
            "Failed: {$summary['failed']}."
        );
    }
}
