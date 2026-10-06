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
 * Restore task.
 *
 * @package mod_videotrackerprime
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/videotrackerprime/backup/moodle2/restore_videotrackerprime_stepslib.php');

class restore_videotrackerprime_activity_task extends restore_activity_task {
    protected function define_my_settings(): void {
    }

    protected function define_my_steps(): void {
        $this->add_step(new restore_videotrackerprime_activity_structure_step(
            'videotrackerprime_structure',
            'videotrackerprime.xml'
        ));
    }

    public static function define_decode_contents(): array {
        return [];
    }

    public static function define_decode_rules(): array {
        return [];
    }

    public static function define_restore_log_rules(): array {
        return [];
    }

    public static function define_restore_log_rules_for_course(): array {
        return [];
    }
}
