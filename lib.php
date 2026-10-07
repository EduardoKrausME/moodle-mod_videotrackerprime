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
 * Core activity callbacks.
 *
 * @package mod_videotrackerprime
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_video_bridge\progress\manager as progress_manager;
use local_video_bridge\source\manager as source_manager;

/**
 * Declares the Moodle features supported by the activity.
 *
 * @param string $feature Feature constant being queried.
 * @return mixed Support value for the requested feature.
 */
function videotrackerprime_supports($feature) {
    return match ($feature) {
        FEATURE_MOD_ARCHETYPE => MOD_ARCHETYPE_RESOURCE,
        FEATURE_GROUPS, FEATURE_GROUPINGS => true,
        FEATURE_MOD_INTRO => true,
        FEATURE_COMPLETION_TRACKS_VIEWS => true,
        FEATURE_COMPLETION_HAS_RULES => true,
        FEATURE_BACKUP_MOODLE2 => true,
        FEATURE_MOD_PURPOSE => MOD_PURPOSE_CONTENT,
        default => null,
    };
}

/**
 * Creates a Video Tracker Prime activity instance.
 *
 * @param stdClass $data Activity data.
 * @param mod_videotrackerprime_mod_form|null $mform Activity form instance.
 * @return int New activity instance id.
 */
function videotrackerprime_add_instance(stdClass $data, ?mod_videotrackerprime_mod_form $mform = null): int {
    global $DB;
    $now = time();
    $data->timecreated = $now;
    $data->timemodified = $now;
    (new source_manager())->normalise_record($data);
    $id = $DB->insert_record('videotrackerprime', $data);
    $data->id = $id;
    $cm = get_coursemodule_from_instance('videotrackerprime', $id, $data->course, false, MUST_EXIST);
    (new source_manager())->save_files($data, context_module::instance($cm->id));
    return $id;
}

/**
 * Updates a Video Tracker Prime activity instance.
 *
 * @param stdClass $data Activity data.
 * @param mod_videotrackerprime_mod_form|null $mform Activity form instance.
 * @return bool True when the activity record is updated.
 */
function videotrackerprime_update_instance(stdClass $data, ?mod_videotrackerprime_mod_form $mform = null): bool {
    global $DB;
    $data->id = $data->instance;
    $previoussource = $DB->get_field('videotrackerprime', 'videosource', ['id' => $data->id], MUST_EXIST);
    $data->timemodified = time();
    (new source_manager())->normalise_record($data);
    $result = $DB->update_record('videotrackerprime', $data);
    $cm = get_coursemodule_from_instance('videotrackerprime', $data->id, $data->course, false, MUST_EXIST);
    (new source_manager())->save_files($data, context_module::instance($cm->id), (string)$previoussource);
    return $result;
}

/**
 * Deletes a Video Tracker Prime activity instance and related data.
 *
 * @param int $id Activity instance id.
 * @return bool True when the activity is deleted, false when it does not exist.
 */
function videotrackerprime_delete_instance(int $id): bool {
    global $DB;
    $activity = $DB->get_record('videotrackerprime', ['id' => $id]);
    if (!$activity) {
        return false;
    }
    $cm = get_coursemodule_from_instance('videotrackerprime', $id, $activity->course, false, IGNORE_MISSING);
    if ($cm) {
        $context = context_module::instance($cm->id);
        progress_manager::delete_consumer($context->id, 'mod_videotrackerprime', $id);
        (new source_manager())->delete_files($context);
    }
    $cueids = $DB->get_fieldset_select('videotrackerprime_cues', 'id', 'videotrackerprimeid = :id', ['id' => $id]);
    if ($cueids) {
        [$insql, $params] = $DB->get_in_or_equal($cueids, SQL_PARAMS_NAMED, 'cue');
        $DB->delete_records_select('videotrackerprime_answers', "checkpointid {$insql}", $params);
    }
    $DB->delete_records('videotrackerprime_cues', ['videotrackerprimeid' => $id]);
    $DB->delete_records('videotrackerprime', ['id' => $id]);
    return true;
}

/**
 * Returns cached course-module information for the activity.
 *
 * @param stdClass $cm Course-module record.
 * @return cached_cm_info|null Cached information or null when the activity does not exist.
 */
function videotrackerprime_get_coursemodule_info(stdClass $cm): ?cached_cm_info {
    global $DB;
    $activity = $DB->get_record('videotrackerprime', ['id' => $cm->instance],
        'id,name,intro,introformat,completionpercent,completionallrequired,completioncheckpointcount');
    if (!$activity) {
        return null;
    }
    $info = new cached_cm_info();
    $info->name = $activity->name;
    if ($cm->showdescription) {
        $info->content = format_module_intro('videotrackerprime', $activity, $cm->id, false);
    }
    if ((int)$cm->completion === COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata['customcompletionrules'] = [
            'completionpercent' => (int)$activity->completionpercent,
            'completionallrequired' => (int)$activity->completionallrequired,
            'completioncheckpointcount' => (int)$activity->completioncheckpointcount,
        ];
    }
    return $info;
}

/**
 * Returns descriptions for the active custom completion rules.
 *
 * @param cached_cm_info $cm Cached course-module information.
 * @return array Human-readable completion rule descriptions.
 */
function videotrackerprime_get_completion_active_rule_descriptions(cached_cm_info $cm): array {
    $rules = $cm->customdata['customcompletionrules'] ?? [];
    $descriptions = [];
    if (!empty($rules['completionpercent'])) {
        $descriptions[] = get_string('completiondetail:percent', 'videotrackerprime', $rules['completionpercent']);
    }
    if (!empty($rules['completionallrequired'])) {
        $descriptions[] = get_string('completiondetail:allrequired', 'videotrackerprime');
    }
    if (!empty($rules['completioncheckpointcount'])) {
        $descriptions[] = get_string('completiondetail:count', 'videotrackerprime', $rules['completioncheckpointcount']);
    }
    return $descriptions;
}

/**
 * Determines whether the activity is complete for a user.
 *
 * @param stdClass $course Course record.
 * @param stdClass $cm Course-module record.
 * @param int $userid User id.
 * @param bool $type Expected completion state.
 * @return bool True when the custom completion conditions are satisfied.
 */
function videotrackerprime_get_completion_state($course, $cm, int $userid, bool $type): bool {
    return \mod_videotrackerprime\completion\evaluator::is_complete($cm, $userid);
}
