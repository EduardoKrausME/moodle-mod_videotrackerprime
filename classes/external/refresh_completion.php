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
 * Refreshes Moodle completion.
 *
 * @package mod_videotrackerprime
 */
namespace mod_videotrackerprime\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_videotrackerprime\completion\evaluator;

class refresh_completion extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
        ]);
    }

    public static function execute(int $cmid): array {
        global $USER;
        $params = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid]);
        $cm = get_coursemodule_from_id('videotrackerprime', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_login($cm->course, true, $cm);
        require_capability('mod/videotrackerprime:view', $context);

        $state = evaluator::state($cm, (int)$USER->id);
        $completion = new \completion_info(get_course($cm->course));
        if ($completion->is_enabled($cm)) {
            $completion->update_state($cm, COMPLETION_UNKNOWN, $USER->id);
        }
        return [
            'complete' => (bool)$state['complete'],
            'percent' => (int)$state['percent'],
            'completedcheckpoints' => (int)$state['completed'],
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'complete' => new external_value(PARAM_BOOL, 'Completion state'),
            'percent' => new external_value(PARAM_INT, 'Watched percentage'),
            'completedcheckpoints' => new external_value(PARAM_INT, 'Completed checkpoints'),
        ]);
    }
}
