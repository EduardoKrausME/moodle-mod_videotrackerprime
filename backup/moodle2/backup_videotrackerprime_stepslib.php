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
 * Backup structure.
 *
 * @package mod_videotrackerprime
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class backup_videotrackerprime_activity_structure_step extends backup_activity_structure_step {
    /**
     * Method define_structure.
     *
     * @return backup_nested_element Return value.
     */
    protected function define_structure(): backup_nested_element {
        $userinfo = $this->get_setting_value('userinfo');

        $activity = new backup_nested_element('videotrackerprime', ['id'], [
            'name', 'intro', 'introformat', 'videosource', 'videourl', 'sourceconfig',
            'completionpercent', 'completionallrequired', 'completioncheckpointcount',
            'timecreated', 'timemodified',
        ]);
        $cues = new backup_nested_element('cues');
        $cue = new backup_nested_element('cue', ['id'], [
            'timestamp', 'title', 'body', 'bodyformat', 'type', 'configjson', 'required',
            'pausevideo', 'requireinteraction', 'dismissible', 'onceonly', 'replaynewsession',
            'visibleontimeline', 'timestart', 'timeend', 'sortorder', 'timecreated', 'timemodified',
        ]);
        $answers = new backup_nested_element('answers');
        $answer = new backup_nested_element('answer', ['id'], [
            'userid', 'sessionid', 'videotimestamp', 'response', 'completed', 'timecreated', 'timemodified',
        ]);

        $activity->add_child($cues);
        $cues->add_child($cue);
        $cue->add_child($answers);
        $answers->add_child($answer);

        $activity->set_source_table('videotrackerprime', ['id' => backup::VAR_ACTIVITYID]);
        $cue->set_source_table('videotrackerprime_cues', ['videotrackerprimeid' => backup::VAR_PARENTID]);
        if ($userinfo) {
            $answer->set_source_table('videotrackerprime_answers', ['checkpointid' => backup::VAR_PARENTID]);
        }
        $answer->annotate_ids('user', 'userid');
        $activity->annotate_files('local_video_bridge', 'video', null);

        return $this->prepare_activity_structure($activity);
    }
}
