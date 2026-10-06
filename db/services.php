<?php
// This file is part of Moodle - http://moodle.org/.
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
 * External functions.
 *
 * @package mod_videotrackerprime
 */
defined('MOODLE_INTERNAL') || die;

$functions = [
    'mod_videotrackerprime_save_response' => [
        'classname' => '\mod_videotrackerprime\external\save_response',
        'methodname' => 'execute',
        'description' => 'Stores a learner interaction with a timeline checkpoint.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/videotrackerprime:view',
    ],
    'mod_videotrackerprime_save_checkpoint' => [
        'classname' => '\mod_videotrackerprime\external\save_checkpoint',
        'methodname' => 'execute',
        'description' => 'Creates or updates a timeline checkpoint.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/videotrackerprime:managecheckpoints',
    ],
    'mod_videotrackerprime_delete_checkpoint' => [
        'classname' => '\mod_videotrackerprime\external\delete_checkpoint',
        'methodname' => 'execute',
        'description' => 'Deletes a timeline checkpoint.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/videotrackerprime:managecheckpoints',
    ],
    'mod_videotrackerprime_refresh_completion' => [
        'classname' => '\mod_videotrackerprime\external\refresh_completion',
        'methodname' => 'execute',
        'description' => 'Refreshes Moodle completion from Video Bridge progress and checkpoint state.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/videotrackerprime:view',
    ],
];
