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
 * Activity dashboard.
 *
 * @package mod_videotrackerprime
 */
require('../../config.php');

use local_video_bridge\progress\manager as progress_manager;
use mod_videotrackerprime\checkpoint_manager;

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('videotrackerprime', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videotrackerprime', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/videotrackerprime:viewreport', $context);

$PAGE->set_url('/mod/videotrackerprime/report.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('dashboard', 'videotrackerprime'));
$PAGE->set_heading(format_string($course->fullname));

$groupid = 0;
if (groups_get_activity_groupmode($cm) == SEPARATEGROUPS && !has_capability('moodle/site:accessallgroups', $context)) {
    $groupid = groups_get_activity_group($cm, true);
}
$users = get_enrolled_users($context, 'mod/videotrackerprime:view', $groupid, 'u.id,u.firstname,u.lastname,u.email');
$userids = array_map('intval', array_keys($users));

$mediahash = progress_manager::media_hash((string)$activity->videosource, (string)$activity->sourceconfig);
$progressrows = progress_manager::get_activity_progress(
    $context->id, 'mod_videotrackerprime', (int)$activity->id, $mediahash, $userids
);
$progress = [];
foreach ($progressrows as $row) {
    $progress[(int)$row->userid] = $row;
}

$cues = $DB->get_records('videotrackerprime_cues', ['videotrackerprimeid' => $activity->id], 'timestamp,id');
$answers = [];
if ($cues && $userids) {
    [$cuesql, $cueparams] = $DB->get_in_or_equal(array_keys($cues), SQL_PARAMS_NAMED, 'cue');
    [$usersql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'usr');
    $answers = $DB->get_records_select(
        'videotrackerprime_answers',
        "checkpointid {$cuesql} AND userid {$usersql}",
        $cueparams + $userparams,
        'checkpointid,userid,timecreated'
    );
}

$bycue = [];
$byuser = [];
foreach ($answers as $answer) {
    $bycue[(int)$answer->checkpointid][] = $answer;
    if (!empty($answer->completed)) {
        $byuser[(int)$answer->userid][(int)$answer->checkpointid] = true;
    }
}

$userrows = [];
foreach ($users as $user) {
    $p = $progress[$user->id] ?? null;
    $userrows[] = [
        'fullname' => fullname($user),
        'percent' => $p ? (int)$p->percent : 0,
        'completedcheckpoints' => isset($byuser[$user->id]) ? count($byuser[$user->id]) : 0,
        'totalcheckpoints' => count($cues),
    ];
}

$cuerows = [];
foreach ($cues as $cue) {
    $rows = $bycue[$cue->id] ?? [];
    $distinct = [];
    $reflections = [];
    $confidence = [];
    $distribution = [];
    foreach ($rows as $answer) {
        if ($answer->completed) {
            $distinct[$answer->userid] = true;
        }
        if ($cue->type === 'reflection' && trim((string)$answer->response) !== '') {
            $reflections[] = [
                'user' => fullname($users[$answer->userid] ?? (object)['firstname' => '', 'lastname' => '']),
                'response' => (string)$answer->response,
            ];
        } else if ($cue->type === 'confidence' && (int)$answer->response >= 1) {
            $confidence[] = (int)$answer->response;
        } else if ($cue->type === 'poll' && trim((string)$answer->response) !== '') {
            $key = (string)$answer->response;
            $distribution[$key] = ($distribution[$key] ?? 0) + 1;
        }
    }
    $cuerows[] = [
        'title' => format_string($cue->title),
        'type' => get_string('type:' . $cue->type, 'videotrackerprime'),
        'time' => gmdate((float)$cue->timestamp >= 3600 ? 'H:i:s' : 'i:s', (int)$cue->timestamp),
        'passed' => count($distinct),
        'totalusers' => count($users),
        'confidence' => $confidence ? round(array_sum($confidence) / count($confidence), 2) : null,
        'hasconfidence' => (bool)$confidence,
        'reflections' => $reflections,
        'hasreflections' => (bool)$reflections,
        'distribution' => array_map(static fn($label, $count) => ['label' => (string)$label, 'count' => $count],
            array_keys($distribution), array_values($distribution)),
        'hasdistribution' => (bool)$distribution,
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videotrackerprime/report', [
    'name' => format_string($activity->name),
    'users' => $userrows,
    'hasusers' => (bool)$userrows,
    'cues' => $cuerows,
    'hascues' => (bool)$cuerows,
    'backurl' => (new moodle_url('/mod/videotrackerprime/view.php', ['id' => $cm->id]))->out(false),
]);
echo $OUTPUT->footer();
