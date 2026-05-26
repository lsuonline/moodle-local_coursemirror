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
 * Language strings for Course Mirror.
 *
 * @package    local_coursemirror
 * @copyright  2026 Louisiana State University
 * @copyright  2026 Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Course mirror';

$string['remoteurl'] = 'Remote Moodle URL';
$string['remoteurl_desc'] = 'Base URL of the destination Moodle instance. Example: https://external.example.edu';

$string['token'] = 'Remote web service token';
$string['token_desc'] = 'Token for a remote Moodle web service user with permission to search for and create courses.';

$string['academicperiods'] = 'Academic periods';
$string['academicperiods_desc'] = 'Select the academic periods whose Moodle courses should be mirrored to the external Moodle instance.';

$string['createvisible'] = 'Create courses as visible';
$string['createvisible_desc'] = 'If enabled, newly created remote courses will be visible. If disabled, they will be hidden. Sensible defaults: what a concept.';

$string['runsync'] = 'Run course mirror sync';
$string['runsyncnow'] = 'Run sync now';
$string['synccomplete'] = 'Course mirror sync complete.';
$string['viewlogs'] = 'Recent sync log entries';

$string['privacy:metadata'] = 'The Course Mirror plugin does not store personal user data.';

$string['coursemirror:manage'] = 'Manage and run course mirror synchronization';

$string['errorremote'] = 'Remote Moodle error';
$string['errorconfig'] = 'Course Mirror is not fully configured.';
