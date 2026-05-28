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
 * Remote Moodle REST client for Course Mirror.
 *
 * @package    local_coursemirror
 * @copyright  2026 Louisiana State University
 * @copyright  2026 Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_coursemirror;

defined('MOODLE_INTERNAL') || die();

/**
 * Client for communicating with a remote Moodle instance through REST web services.
 */
class external_client {

    /**
     * Remote Moodle base URL.
     *
     * @var string
     */
    private string $baseurl;

    /**
     * Remote Moodle web service token.
     *
     * @var string
     */
    private string $token;

    /**
     * Constructor.
     *
     * @param string $baseurl Remote Moodle base URL.
     * @param string $token Remote Moodle web service token.
     */
    public function __construct(string $baseurl, string $token) {
        $this->baseurl = rtrim($baseurl, '/');
        $this->token = $token;
    }

    /**
     * Execute a REST web service call against the remote Moodle instance.
     *
     * @param string $function Moodle external function name.
     * @param array $params Function parameters.
     * @return mixed Decoded JSON response.
     * @throws \moodle_exception If the remote Moodle call fails.
     */
    public function call(string $function, array $params = []): mixed {
        $url = $this->baseurl . '/webservice/rest/server.php';

        $params = array_merge($params, [
            'wstoken' => $this->token,
            'wsfunction' => $function,
            'moodlewsrestformat' => 'json',
        ]);

        $curl = new \curl();
        $response = $curl->post($url, http_build_query($params, '', '&'));
        $decoded = json_decode($response, true);

        if ($decoded === null) {
            throw new \moodle_exception(
                'errorremote',
                'local_coursemirror',
                '',
                null,
                'Invalid JSON response from remote Moodle.'
            );
        }

        if (isset($decoded['exception'])) {
            throw new \moodle_exception(
                'errorremote',
                'local_coursemirror',
                '',
                null,
                $decoded['message'] ?? $decoded['exception']
            );
        }

        return $decoded;
    }

    /**
     * Determine whether a course already exists in the remote Moodle.
     *
     * A course is considered existing if any one of these fields matches:
     * shortname or idnumber.
     *
     * @param \stdClass $course Local Moodle course object.
     * @return bool True if the course exists remotely.
     * @throws \moodle_exception If the remote lookup fails.
     */
    public function course_exists(\stdClass $course): bool {
        foreach (['shortname', 'idnumber'] as $field) {
            if (empty($course->{$field})) {
                continue;
            }

            $result = $this->call('core_course_get_courses_by_field', [
                'field' => $field,
                'value' => $course->{$field},
            ]);

            if (!empty($result['courses'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a course in the remote Moodle instance.
     *
     * The source course category is resolved on the remote Moodle by category
     * idnumber first, then by category name. If no matching category exists,
     * the category is created remotely.
     *
     * @param \stdClass $course Local Moodle course object.
     * @param \stdClass $category Local Moodle course category object.
     * @param bool $visible Whether the remote course should be visible.
     * @return void
     * @throws \moodle_exception If the remote course creation fails.
     */
    public function create_course(
        \stdClass $course,
        \stdClass $category,
        bool $visible
    ): void {

        $remotecategoryid = $this->get_or_create_category($category);

        $newcourse = [
            'fullname' => $course->fullname,
            'shortname' => $course->shortname,
            'categoryid' => $remotecategoryid,
            'visible' => $visible ? 1 : 0,
        ];

        if (!empty($course->idnumber)) {
            $newcourse['idnumber'] = $course->idnumber;
        }

        if (!empty($course->summary)) {
            $newcourse['summary'] = $course->summary;
            $newcourse['summaryformat'] = $course->summaryformat ?? FORMAT_HTML;
        }

        if (!empty($course->format)) {
            $newcourse['format'] = $course->format;
        }

        if (!empty($course->startdate)) {
            $newcourse['startdate'] = (int)$course->startdate;
        }

        if (!empty($course->enddate)) {
            $newcourse['enddate'] = (int)$course->enddate;
        }

        if (!empty($course->showgrades)) {
            $newcourse['showgrades'] = (int)$course->showgrades;
        }

        if (!empty($course->newsitems)) {
            $newcourse['newsitems'] = (int)$course->newsitems;
        }

        if (!empty($course->maxbytes)) {
            $newcourse['maxbytes'] = (int)$course->maxbytes;
        }

        if (!empty($course->groupmode)) {
            $newcourse['groupmode'] = (int)$course->groupmode;
        }

        if (!empty($course->groupmodeforce)) {
            $newcourse['groupmodeforce'] = (int)$course->groupmodeforce;
        }

        if (!empty($course->enablecompletion)) {
            $newcourse['enablecompletion'] = (int)$course->enablecompletion;
        }

        $this->call(
            'core_course_create_courses',
            [
                'courses' => [$newcourse],
            ]
        );
    }

    /**
     * Get or create a matching course category on the remote Moodle.
     *
     * Categories are matched by idnumber first. If the local category does not
     * have an idnumber, the remote category is matched by name.
     *
     * @param \stdClass $category Local Moodle course category object.
     * @return int Remote Moodle category ID.
     * @throws \moodle_exception If the category lookup or creation fails.
     */
    private function get_or_create_category(\stdClass $category): int {
        $remotecategory = $this->get_remote_category($category);

        if (!empty($remotecategory['id'])) {
            return (int)$remotecategory['id'];
        }
        return $this->create_category($category);
    }

    /**
     * Search for a matching remote Moodle category.
     *
     * @param \stdClass $category Local Moodle course category object.
     * @return array Matching remote category data, or empty array if not found.
     * @throws \moodle_exception If the category lookup fails.
     */
    private function get_remote_category(\stdClass $category): array {
        if (!empty($category->idnumber)) {
            $matches = $this->call('core_course_get_categories', [
                'criteria' => [
                    [
                        'key' => 'idnumber',
                        'value' => $category->idnumber,
                    ],
                ],
            ]);

            if (!empty($matches)) {
                return reset($matches);
            }
        }

        if (!empty($category->name)) {
            $matches = $this->call('core_course_get_categories', [
                'criteria' => [
                    [
                        'key' => 'name',
                        'value' => $category->name,
                    ],
                ],
            ]);

            if (!empty($matches)) {
                return reset($matches);
            }
        }

        return [];
    }

    /**
     * Create a matching category on the remote Moodle.
     *
     * This creates a top-level category unless the remote Moodle already contains
     * a matching parent category by idnumber or name.
     *
     * @param \stdClass $category Local Moodle course category object.
     * @return int Newly created remote category ID.
     * @throws \moodle_exception If category creation fails.
     */
    private function create_category(\stdClass $category): int {
        $newcategory = [
            'name' => $category->name,
        ];

        if (!empty($category->idnumber)) {
            $newcategory['idnumber'] = $category->idnumber;
        }

        if (!empty($category->description)) {
            $newcategory['description'] = $category->description;
            $newcategory['descriptionformat'] = $category->descriptionformat ?? FORMAT_HTML;
        }

        $result = $this->call('core_course_create_categories', [
            'categories' => [$newcategory],
        ]);

        if (empty($result[0]['id'])) {
            throw new \moodle_exception(
                'errorremote',
                'local_coursemirror',
                '',
                null,
                'Remote category creation succeeded but did not return a category ID.'
            );
        }

        return (int)$result[0]['id'];
    }
}
