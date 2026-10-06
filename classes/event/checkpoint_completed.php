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
 * Checkpoint completion event.
 *
 * @package mod_videotrackerprime
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videotrackerprime\event;

/**
 * Class checkpoint_completed.
 */
class checkpoint_completed extends \core\event\base {
    /**
     * Method init.
     *
     * @return void Return value.
     */
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'videotrackerprime_answers';
    }

    /**
     * Method get_name.
     *
     * @return string Return value.
     */
    public static function get_name(): string {
        return get_string('eventcheckpointcompleted', 'videotrackerprime');
    }

    /**
     * Method get_description.
     *
     * @return string Return value.
     */
    public function get_description(): string {
        return "User {$this->userid} completed checkpoint {$this->other['checkpointid']}.";
    }

    /**
     * Method get_url.
     *
     * @return \moodle_url Return value.
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/mod/videotrackerprime/view.php', ['id' => $this->contextinstanceid]);
    }

    /**
     * Method get_objectid_mapping.
     *
     * @return array Return value.
     */
    public static function get_objectid_mapping(): array {
        return ['db' => 'videotrackerprime_answers', 'restore' => 'videotrackerprime_answer'];
    }
}
