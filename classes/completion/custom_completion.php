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
 * Moodle custom completion integration.
 *
 * @package mod_videotrackerprime
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_videotrackerprime\completion;

use core_completion\activity_custom_completion;

/**
 * Class custom_completion.
 */
class custom_completion extends activity_custom_completion {
    /**
     * Method get_state.
     *
     * @param string $rule Parameter rule.
     * @return int Return value.
     */
    public function get_state(string $rule): int {
        global $DB;
        $this->validate_rule($rule);
        $activity = $DB->get_record('videotrackerprime', ['id' => $this->cm->instance], '*', MUST_EXIST);
        $state = evaluator::state($this->cm, $this->userid);

        $complete = match ($rule) {
            'completionpercent' => (int)$activity->completionpercent <= 0 || $state['percentok'],
            'completionallrequired' => empty($activity->completionallrequired) || $state['requiredok'],
            'completioncheckpointcount' => (int)$activity->completioncheckpointcount <= 0 || $state['countok'],
            default => false,
        };
        return $complete ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Method get_defined_custom_rules.
     *
     * @return array Return value.
     */
    public static function get_defined_custom_rules(): array {
        return ['completionpercent', 'completionallrequired', 'completioncheckpointcount'];
    }

    /**
     * Method get_custom_rule_descriptions.
     *
     * @return array Return value.
     */
    public function get_custom_rule_descriptions(): array {
        global $DB;
        $activity = $DB->get_record('videotrackerprime', ['id' => $this->cm->instance], '*', MUST_EXIST);
        $descriptions = [];
        if ((int)$activity->completionpercent > 0) {
            $descriptions['completionpercent'] = get_string(
                'completiondetail:percent', 'videotrackerprime', $activity->completionpercent
            );
        }
        if (!empty($activity->completionallrequired)) {
            $descriptions['completionallrequired'] = get_string('completiondetail:allrequired', 'videotrackerprime');
        }
        if ((int)$activity->completioncheckpointcount > 0) {
            $descriptions['completioncheckpointcount'] = get_string(
                'completiondetail:count', 'videotrackerprime', $activity->completioncheckpointcount
            );
        }
        return $descriptions;
    }

    /**
     * Method get_sort_order.
     *
     * @return array Return value.
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionpercent',
            'completionallrequired',
            'completioncheckpointcount',
        ];
    }
}
