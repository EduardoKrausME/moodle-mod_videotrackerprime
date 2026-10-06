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
 * Visual checkpoint editor.
 *
 * @package mod_videotrackerprime
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');

use local_video_bridge\source\manager as source_manager;
use mod_videotrackerprime\checkpoint_manager;

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('videotrackerprime', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videotrackerprime', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/videotrackerprime:managecheckpoints', $context);

$PAGE->set_url('/mod/videotrackerprime/manage.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('managecheckpoints', 'videotrackerprime'));
$PAGE->set_heading(format_string($course->fullname));

$sources = new source_manager();
$player = $sources->get_player_config($activity, $context);
$client = $player;
unset($client['sourcetemplate']);
$player['sourcehtml'] = $OUTPUT->render_from_template($player['sourcetemplate'], ['player' => $player]);

$cues = [];
foreach (checkpoint_manager::get_active((int)$activity->id) as $cue) {
    $item = checkpoint_manager::serialise_for_client($cue);
    $item['rawbody'] = (string)$cue->body;
    $item['timestart'] = (int)$cue->timestart;
    $item['timeend'] = (int)$cue->timeend;
    $item['sortorder'] = (int)$cue->sortorder;
    $item['config'] = json_decode((string)$cue->configjson, true) ?: [];
    $cues[] = $item;
}
$config = [
    'cmid' => (int)$cm->id,
    'player' => $client,
    'capabilities' => $client['capabilities'] ?? [],
    'cues' => $cues,
    'types' => array_map(static fn($type) => [
        'value' => $type,
        'label' => get_string('type:' . $type, 'videotrackerprime'),
    ], checkpoint_manager::TYPES),
];

$PAGE->requires->js_call_amd('mod_videotrackerprime/editor', 'init');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videotrackerprime/manage', [
    'name' => format_string($activity->name),
    'player' => $player,
    'configjson' => json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
    'backurl' => (new moodle_url('/mod/videotrackerprime/view.php', ['id' => $cm->id]))->out(false),
]);
echo $OUTPUT->footer();
