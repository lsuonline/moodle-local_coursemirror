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
 * Course synchronization service for Course Mirror.
 *
 * @package    local_coursemirror
 * @copyright  2026 Louisiana State University
 * @copyright  2026 Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_coursemirror\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Synchronizes selected local courses to a remote Moodle instance.
 */
class sync {

    /**
     * Run the course synchronization process.
     *
     * @param bool $trace Whether to print progress using mtrace().
     * @return array Sync summary.
     */
    public static function run(bool $trace = true): array {
        global $DB;

        $summary = [
            'checked' => 0,
            'created' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        $remoteurl = get_config('local_coursemirror', 'remoteurl');
        $token = get_config('local_coursemirror', 'token');
        $academicperiods = get_config('local_coursemirror', 'academicperiods');
        $createvisible = (bool)get_config('local_coursemirror', 'createvisible');

        if (empty($remoteurl) || empty($token)) {
            self::trace($trace, get_string('errorconfig', 'local_coursemirror'));
            return $summary;
        }

        $academicperiods = self::normalise_academic_periods($academicperiods);

        if (empty($academicperiods)) {
            self::trace($trace, 'No academic periods selected.');
            return $summary;
        }

        $courses = self::get_courses_for_periods($academicperiods);
        $client = new \local_coursemirror\external_client($remoteurl, $token);

        foreach ($courses as $course) {
            $summary['checked']++;

            try {
                if ($client->course_exists($course)) {
                    $summary['skipped']++;
                    self::log($course, 'skipped', 'Course already exists remotely.');
                    self::trace($trace, "Skipped existing remote course: {$course->shortname}");
                    continue;
                }

                $client->create_course($course, $createvisible);

                $summary['created']++;
                self::log($course, 'created', 'Course created remotely.');
                self::trace($trace, "Created remote course: {$course->shortname}");
            } catch (\Throwable $e) {
                $summary['failed']++;
                self::log($course, 'failed', $e->getMessage());
                self::trace($trace, "Failed syncing {$course->shortname}: {$e->getMessage()}");
            }
        }

        return $summary;
    }

    /**
     * Normalize multiselect academic period config into an array.
     *
     * Moodle stores admin_setting_configmultiselect values as comma-separated text.
     * Naturally. Because arrays were apparently too direct.
     *
     * @param mixed $academicperiods Raw configured academic periods.
     * @return array Normalized period IDs.
     */
    private static function normalise_academic_periods(mixed $academicperiods): array {
        if (empty($academicperiods)) {
            return [];
        }

        if (is_array($academicperiods)) {
            return array_values(array_filter(array_map('trim', $academicperiods)));
        }

        return array_values(array_filter(array_map('trim', explode(',', $academicperiods))));
    }

    /**
     * Fetch local courses attached to selected Workday academic periods.
     *
     * @param array $academicperiods Academic period IDs.
     * @return array Local Moodle course records.
     */
    private static function get_courses_for_periods(array $academicperiods): array {
        global $DB;

        [$periodsql, $periodparams] = $DB->get_in_or_equal(
            $academicperiods,
            SQL_PARAMS_NAMED,
            'period'
        );

        $sql = "
            SELECT DISTINCT c.*
              FROM {course} c
              JOIN {enrol_wds_sections} sec ON sec.moodle_status = c.id
              JOIN {enrol_wds_periods} per ON sec.academic_period_id = per.academic_period_id
             WHERE per.academic_period_id {$periodsql}
          ORDER BY c.shortname ASC
        ";

        return $DB->get_records_sql($sql, $periodparams);
    }

    /**
     * Write a sync event to the Course Mirror log table.
     *
     * @param \stdClass $course Course record.
     * @param string $status Log status.
     * @param string $message Log message.
     * @return void
     */
    private static function log(\stdClass $course, string $status, string $message): void {
        global $DB;

        $record = (object)[
            'courseid' => $course->id ?? null,
            'shortname' => $course->shortname ?? null,
            'fullname' => $course->fullname ?? null,
            'idnumber' => $course->idnumber ?? null,
            'status' => $status,
            'message' => $message,
            'timecreated' => time(),
        ];

        $DB->insert_record('local_coursemirror_log', $record);
    }

    /**
     * Print a trace message when tracing is enabled.
     *
     * @param bool $trace Whether tracing is enabled.
     * @param string $message Message to print.
     * @return void
     */
    private static function trace(bool $trace, string $message): void {
        if ($trace) {
            mtrace($message);
        }
    }
}
