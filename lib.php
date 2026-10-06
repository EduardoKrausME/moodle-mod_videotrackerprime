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
 * Core activity callbacks.
 *
 * @package mod_videotrackerprime
 */
use local_video_bridge\source\manager as source_manager;

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

function videotrackerprime_delete_instance(int $id): bool {
    global $DB;
    $activity = $DB->get_record('videotrackerprime', ['id' => $id]);
    if (!$activity) {
        return false;
    }
    $cm = get_coursemodule_from_instance('videotrackerprime', $id, $activity->course, false, IGNORE_MISSING);
    if ($cm) {
        (new source_manager())->delete_files(context_module::instance($cm->id));
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

function videotrackerprime_get_completion_state($course, $cm, int $userid, bool $type): bool {
    return \mod_videotrackerprime\completion\evaluator::is_complete($cm, $userid);
}
