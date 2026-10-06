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
 * Stores learner checkpoint responses.
 *
 * @package mod_videotrackerprime
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_videotrackerprime\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_videotrackerprime\checkpoint_manager;

/**
 * Class save_response.
 */
class save_response extends external_api {
    /**
     * Method execute_parameters.
     *
     * @return external_function_parameters Return value.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'checkpointid' => new external_value(PARAM_INT, 'Checkpoint id'),
            'sessionid' => new external_value(PARAM_ALPHANUMEXT, 'Client playback session id'),
            'videotimestamp' => new external_value(PARAM_FLOAT, 'Actual player timestamp'),
            'response' => new external_value(PARAM_RAW, 'Response payload', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Method execute.
     *
     * @param int $checkpointid Parameter checkpointid.
     * @param string $sessionid Parameter sessionid.
     * @param float $videotimestamp Parameter videotimestamp.
     * @param string $response Parameter response.
     * @return array Return value.
     */
    public static function execute(int $checkpointid, string $sessionid, float $videotimestamp, string $response = ''): array {
        global $DB, $USER;
        $params = self::validate_parameters(self::execute_parameters(), compact(
            'checkpointid', 'sessionid', 'videotimestamp', 'response'
        ));
        $cue = $DB->get_record('videotrackerprime_cues', ['id' => $params['checkpointid']], '*', MUST_EXIST);
        $activity = $DB->get_record('videotrackerprime', ['id' => $cue->videotrackerprimeid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('videotrackerprime', $activity->id, $activity->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_login($activity->course, true, $cm);
        require_capability('mod/videotrackerprime:view', $context);

        if (!checkpoint_manager::is_available($cue)) {
            throw new \invalid_parameter_exception('Checkpoint is not currently available.');
        }

        $capabilities = (new \local_video_bridge\source\manager())->get_capabilities(
            (string)$activity->videosource
        );
        if (empty($capabilities['tracking'])) {
            throw new \invalid_parameter_exception(get_string('notrackingsources', 'videotrackerprime'));
        }
        if (($cue->pausevideo || $cue->requireinteraction) && empty($capabilities['playbackcontrol'])) {
            throw new \invalid_parameter_exception(get_string('playbackcontrolrequired', 'videotrackerprime'));
        }

        // A checkpoint cannot be completed merely because a seek landed far beyond it.
        $tolerance = 3.5;
        if (abs((float)$params['videotimestamp'] - (float)$cue->timestamp) > $tolerance) {
            throw new \invalid_parameter_exception('Checkpoint timestamp was not reached by effective playback.');
        }

        [$cleanresponse, $completed] = checkpoint_manager::validate_response($cue, (string)$params['response']);
        $now = time();
        $key = [
            'checkpointid' => (int)$cue->id,
            'userid' => (int)$USER->id,
            'sessionid' => (string)$params['sessionid'],
        ];
        $record = $DB->get_record('videotrackerprime_answers', $key);
        if ($record) {
            $record->videotimestamp = (float)$params['videotimestamp'];
            $record->response = $cleanresponse;
            $record->completed = $completed ? 1 : 0;
            $record->timemodified = $now;
            $DB->update_record('videotrackerprime_answers', $record);
        } else {
            $record = (object)($key + [
                'videotimestamp' => (float)$params['videotimestamp'],
                'response' => $cleanresponse,
                'completed' => $completed ? 1 : 0,
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
            $record->id = $DB->insert_record('videotrackerprime_answers', $record);
        }

        if ($completed) {
            $event = \mod_videotrackerprime\event\checkpoint_completed::create([
                'objectid' => $record->id,
                'context' => $context,
                'relateduserid' => $USER->id,
                'other' => ['checkpointid' => (int)$cue->id],
            ]);
            $event->trigger();
        }

        $completion = new \completion_info(get_course($activity->course));
        if ($completion->is_enabled($cm)) {
            $completion->update_state($cm, COMPLETION_UNKNOWN, $USER->id);
        }

        return ['saved' => true, 'completed' => (bool)$completed];
    }

    /**
     * Method execute_returns.
     *
     * @return external_single_structure Return value.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'saved' => new external_value(PARAM_BOOL, 'Whether the response was stored'),
            'completed' => new external_value(PARAM_BOOL, 'Whether the checkpoint interaction is complete'),
        ]);
    }
}
