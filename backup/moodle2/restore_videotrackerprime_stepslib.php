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
 * Restore structure.
 *
 * @package mod_videotrackerprime
 */
class restore_videotrackerprime_activity_structure_step extends restore_activity_structure_step {
    protected function define_structure(): array {
        $paths = [
            new restore_path_element('videotrackerprime', '/activity/videotrackerprime'),
            new restore_path_element('videotrackerprime_cue', '/activity/videotrackerprime/cues/cue'),
        ];
        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element(
                'videotrackerprime_answer',
                '/activity/videotrackerprime/cues/cue/answers/answer'
            );
        }
        return $this->prepare_activity_structure($paths);
    }

    protected function process_videotrackerprime(array $data): void {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->id = $DB->insert_record('videotrackerprime', $data);
        $this->apply_activity_instance($data->id);
        $this->set_mapping('videotrackerprime', $oldid, $data->id, true);
    }

    protected function process_videotrackerprime_cue(array $data): void {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->videotrackerprimeid = $this->get_new_parentid('videotrackerprime');
        $data->id = $DB->insert_record('videotrackerprime_cues', $data);
        $this->set_mapping('videotrackerprime_cue', $oldid, $data->id);
    }

    protected function process_videotrackerprime_answer(array $data): void {
        global $DB;
        $data = (object)$data;
        $data->checkpointid = $this->get_new_parentid('videotrackerprime_cue');
        $data->userid = $this->get_mappingid('user', $data->userid, 0);
        if ($data->userid) {
            $DB->insert_record('videotrackerprime_answers', $data);
        }
    }

    protected function after_execute(): void {
        $this->add_related_files('local_video_bridge', 'video', null);
    }
}
