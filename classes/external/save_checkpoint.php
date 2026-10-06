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
 * Creates or updates checkpoints.
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
 * Class save_checkpoint.
 */
class save_checkpoint extends external_api {
    /**
     * Method execute_parameters.
     *
     * @return external_function_parameters Return value.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'id' => new external_value(PARAM_INT, 'Checkpoint id', VALUE_DEFAULT, 0),
            'timestamp' => new external_value(PARAM_FLOAT, 'Video timestamp'),
            'title' => new external_value(PARAM_TEXT, 'Title'),
            'body' => new external_value(PARAM_RAW, 'HTML body', VALUE_DEFAULT, ''),
            'type' => new external_value(PARAM_ALPHA, 'Checkpoint type'),
            'options' => new external_value(PARAM_RAW, 'Poll options, one per line', VALUE_DEFAULT, ''),
            'resourceurl' => new external_value(PARAM_URL, 'Resource URL', VALUE_DEFAULT, ''),
            'resourcelabel' => new external_value(PARAM_TEXT, 'Resource label', VALUE_DEFAULT, ''),
            'maxchars' => new external_value(PARAM_INT, 'Reflection maximum characters', VALUE_DEFAULT, 500),
            'required' => new external_value(PARAM_BOOL, 'Required'),
            'pausevideo' => new external_value(PARAM_BOOL, 'Pause video'),
            'requireinteraction' => new external_value(PARAM_BOOL, 'Interaction required before continuing'),
            'dismissible' => new external_value(PARAM_BOOL, 'Can dismiss'),
            'onceonly' => new external_value(PARAM_BOOL, 'Trigger once in a session'),
            'replaynewsession' => new external_value(PARAM_BOOL, 'May trigger again in a new session'),
            'visibleontimeline' => new external_value(PARAM_BOOL, 'Show marker'),
            'timestart' => new external_value(PARAM_INT, 'Availability start', VALUE_DEFAULT, 0),
            'timeend' => new external_value(PARAM_INT, 'Availability end', VALUE_DEFAULT, 0),
            'sortorder' => new external_value(PARAM_INT, 'Sort order', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Method execute.
     *
     * @param int $cmid Parameter cmid.
     * @param int $id Parameter id.
     * @param float $timestamp Parameter timestamp.
     * @param string $title Parameter title.
     * @param string $body Parameter body.
     * @param string $type Parameter type.
     * @param string $options Parameter options.
     * @param string $resourceurl Parameter resourceurl.
     * @param string $resourcelabel Parameter resourcelabel.
     * @param int $maxchars Parameter maxchars.
     * @param bool $required Parameter required.
     * @param bool $pausevideo Parameter pausevideo.
     * @param bool $requireinteraction Parameter requireinteraction.
     * @param bool $dismissible Parameter dismissible.
     * @param bool $onceonly Parameter onceonly.
     * @param bool $replaynewsession Parameter replaynewsession.
     * @param bool $visibleontimeline Parameter visibleontimeline.
     * @param int $timestart Parameter timestart.
     * @param int $timeend Parameter timeend.
     * @param int $sortorder Parameter sortorder.
     * @return array Return value.
     */
    public static function execute(
        int $cmid, int $id, float $timestamp, string $title, string $body, string $type,
        string $options, string $resourceurl, string $resourcelabel, int $maxchars,
        bool $required, bool $pausevideo, bool $requireinteraction, bool $dismissible,
        bool $onceonly, bool $replaynewsession, bool $visibleontimeline,
        int $timestart = 0, int $timeend = 0, int $sortorder = 0
    ): array {
        global $DB;
        $params = self::validate_parameters(self::execute_parameters(), compact(
            'cmid', 'id', 'timestamp', 'title', 'body', 'type', 'options', 'resourceurl',
            'resourcelabel', 'maxchars', 'required', 'pausevideo', 'requireinteraction',
            'dismissible', 'onceonly', 'replaynewsession', 'visibleontimeline',
            'timestart', 'timeend', 'sortorder'
        ));
        $cm = get_coursemodule_from_id('videotrackerprime', $params['cmid'], 0, false, MUST_EXIST);
        $activity = $DB->get_record('videotrackerprime', ['id' => $cm->instance], '*', MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_login($activity->course, true, $cm);
        require_capability('mod/videotrackerprime:managecheckpoints', $context);

        $cue = checkpoint_manager::normalise($params, $activity, $context);
        $now = time();
        if ($cue->id) {
            $existing = $DB->get_record('videotrackerprime_cues', ['id' => $cue->id], '*', MUST_EXIST);
            if ((int)$existing->videotrackerprimeid !== (int)$activity->id) {
                throw new \invalid_parameter_exception('Checkpoint does not belong to this activity.');
            }
            $cue->timecreated = $existing->timecreated;
            $cue->timemodified = $now;
            $DB->update_record('videotrackerprime_cues', $cue);
        } else {
            $cue->timecreated = $now;
            $cue->timemodified = $now;
            $cue->id = $DB->insert_record('videotrackerprime_cues', $cue);
        }
        return ['id' => (int)$cue->id, 'timestamp' => (float)$cue->timestamp];
    }

    /**
     * Method execute_returns.
     *
     * @return external_single_structure Return value.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Checkpoint id'),
            'timestamp' => new external_value(PARAM_FLOAT, 'Timestamp'),
        ]);
    }
}
