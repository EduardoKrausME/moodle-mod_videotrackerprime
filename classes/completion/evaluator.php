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
 * Completion evaluator.
 *
 * @package mod_videotrackerprime
 */
namespace mod_videotrackerprime\completion;

use local_video_bridge\progress\manager as progress_manager;
use mod_videotrackerprime\checkpoint_manager;

class evaluator {
    public static function state(\stdClass $cm, int $userid): array {
        global $DB;
        $activity = $DB->get_record('videotrackerprime', ['id' => $cm->instance], '*', MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $mediahash = progress_manager::media_hash((string)$activity->videosource, (string)$activity->sourceconfig);
        $progress = progress_manager::get_progress(
            $context->id, 'mod_videotrackerprime', (int)$activity->id, $mediahash, $userid
        );
        $percent = $progress ? (int)$progress->percent : 0;
        $counts = checkpoint_manager::completion_counts((int)$activity->id, $userid);

        $percentok = (int)$activity->completionpercent <= 0
            || $percent >= (int)$activity->completionpercent;
        $requiredok = empty($activity->completionallrequired)
            || $counts['requiredcompleted'] >= $counts['required'];
        $countok = (int)$activity->completioncheckpointcount <= 0
            || $counts['completed'] >= (int)$activity->completioncheckpointcount;

        return [
            'complete' => $percentok && $requiredok && $countok,
            'percent' => $percent,
            'percentok' => $percentok,
            'requiredok' => $requiredok,
            'countok' => $countok,
        ] + $counts;
    }

    public static function is_complete(\stdClass $cm, int $userid): bool {
        return self::state($cm, $userid)['complete'];
    }
}
