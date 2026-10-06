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
 * Learner view.
 *
 * @package mod_videotrackerprime
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');

use local_video_bridge\source\manager as source_manager;
use mod_videotrackerprime\checkpoint_manager;
use mod_videotrackerprime\completion\evaluator;

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('videotrackerprime', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videotrackerprime', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/videotrackerprime:view', $context);

$PAGE->set_url('/mod/videotrackerprime/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($activity->name));
$PAGE->set_heading(format_string($course->fullname));

$completion = new completion_info($course);
if ($completion->is_enabled($cm)) {
    $completion->set_module_viewed($cm);
}
$event = \mod_videotrackerprime\event\course_module_viewed::create([
    'objectid' => $activity->id,
    'context' => $context,
]);
$event->add_record_snapshot('videotrackerprime', $activity);
$event->trigger();

$sources = new source_manager();
$player = $sources->get_player_config($activity, $context);
$playerclient = $player;
unset($playerclient['sourcetemplate']);
$player['sourcehtml'] = $OUTPUT->render_from_template($player['sourcetemplate'], ['player' => $player]);

$cues = [];
foreach (checkpoint_manager::get_active((int)$activity->id, (int)$USER->id) as $cue) {
    $cues[] = checkpoint_manager::serialise_for_client($cue);
}
$state = evaluator::state($cm, (int)$USER->id);
$config = [
    'cmid' => (int)$cm->id,
    'player' => $playerclient,
    'capabilities' => $playerclient['capabilities'] ?? [],
    'cues' => $cues,
    'sessionid' => bin2hex(random_bytes(16)),
];

$data = [
    'name' => format_string($activity->name),
    'intro' => format_module_intro('videotrackerprime', $activity, $cm->id),
    'hasintro' => trim((string)$activity->intro) !== '',
    'player' => $player,
    'configjson' => json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
    'state' => [
        'percent' => $state['percent'],
        'completed' => $state['completed'],
        'total' => $state['total'],
        'requiredcompleted' => $state['requiredcompleted'],
        'required' => $state['required'],
        'complete' => $state['complete'],
    ],
    'canmanage' => has_capability('mod/videotrackerprime:managecheckpoints', $context),
    'manageurl' => (new moodle_url('/mod/videotrackerprime/manage.php', ['id' => $cm->id]))->out(false),
    'canviewreport' => has_capability('mod/videotrackerprime:viewreport', $context),
    'reporturl' => (new moodle_url('/mod/videotrackerprime/report.php', ['id' => $cm->id]))->out(false),
];

$PAGE->requires->js_call_amd('mod_videotrackerprime/runtime', 'init');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videotrackerprime/view', $data);
echo $OUTPUT->footer();
