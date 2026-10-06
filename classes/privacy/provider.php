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
 * Privacy API provider.
 *
 * @package mod_videotrackerprime
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_videotrackerprime\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Class provider.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Method get_metadata.
     *
     * @param collection $collection Parameter collection.
     * @return collection Return value.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('videotrackerprime_answers', [
            'userid' => 'privacy:metadata:answers:userid',
            'sessionid' => 'privacy:metadata:answers:sessionid',
            'videotimestamp' => 'privacy:metadata:answers:videotimestamp',
            'response' => 'privacy:metadata:answers:response',
            'completed' => 'privacy:metadata:answers:completed',
            'timecreated' => 'privacy:metadata:answers:timecreated',
            'timemodified' => 'privacy:metadata:answers:timemodified',
        ], 'privacy:metadata:answers');
        return $collection;
    }

    /**
     * Method get_contexts_for_userid.
     *
     * @param int $userid Parameter userid.
     * @return contextlist Return value.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm
                    ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {videotrackerprime} v ON v.id = cm.instance
                  JOIN {videotrackerprime_cues} c ON c.videotrackerprimeid = v.id
                  JOIN {videotrackerprime_answers} a ON a.checkpointid = c.id
                 WHERE a.userid = :userid";
        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'videotrackerprime',
            'userid' => $userid,
        ]);
        return $contextlist;
    }

    /**
     * Method get_users_in_context.
     *
     * @param userlist $userlist Parameter userlist.
     * @return void Return value.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $sql = "SELECT a.userid
                  FROM {videotrackerprime_answers} a
                  JOIN {videotrackerprime_cues} c ON c.id = a.checkpointid
                  JOIN {videotrackerprime} v ON v.id = c.videotrackerprimeid
                  JOIN {course_modules} cm ON cm.instance = v.id AND cm.id = :cmid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname";
        $userlist->add_from_sql('userid', $sql, [
            'cmid' => $context->instanceid,
            'modname' => 'videotrackerprime',
        ]);
    }

    /**
     * Method export_user_data.
     *
     * @param approved_contextlist $contextlist Parameter contextlist.
     * @return void Return value.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = (int)$contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('videotrackerprime', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $sql = "SELECT a.*, c.title, c.type, c.timestamp AS checkpointtime
                      FROM {videotrackerprime_answers} a
                      JOIN {videotrackerprime_cues} c ON c.id = a.checkpointid
                     WHERE c.videotrackerprimeid = :activityid AND a.userid = :userid
                  ORDER BY a.timecreated, a.id";
            $rows = [];
            foreach ($DB->get_records_sql($sql, ['activityid' => $cm->instance, 'userid' => $userid]) as $row) {
                unset($row->id, $row->userid, $row->checkpointid);
                $row->completed = transform::yesno($row->completed);
                $row->timecreated = transform::datetime($row->timecreated);
                $row->timemodified = transform::datetime($row->timemodified);
                $rows[] = $row;
            }
            if ($rows) {
                writer::with_context($context)->export_data(
                    [get_string('privacy:answerspath', 'videotrackerprime')],
                    (object)['interactions' => $rows]
                );
            }
        }
    }

    /**
     * Method delete_data_for_all_users_in_context.
     *
     * @param \context $context Parameter context.
     * @return void Return value.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('videotrackerprime', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $cueids = $DB->get_fieldset_select(
            'videotrackerprime_cues', 'id', 'videotrackerprimeid = :id', ['id' => $cm->instance]
        );
        if (!$cueids) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($cueids, SQL_PARAMS_NAMED, 'cue');
        $DB->delete_records_select('videotrackerprime_answers', "checkpointid {$insql}", $params);
    }

    /**
     * Method delete_data_for_user.
     *
     * @param approved_contextlist $contextlist Parameter contextlist.
     * @return void Return value.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $userid = (int)$contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('videotrackerprime', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $cueids = $DB->get_fieldset_select(
                'videotrackerprime_cues', 'id', 'videotrackerprimeid = :id', ['id' => $cm->instance]
            );
            if ($cueids) {
                [$insql, $params] = $DB->get_in_or_equal($cueids, SQL_PARAMS_NAMED, 'cue');
                $params['userid'] = $userid;
                $DB->delete_records_select(
                    'videotrackerprime_answers',
                    "userid = :userid AND checkpointid {$insql}",
                    $params
                );
            }
        }
    }

    /**
     * Method delete_data_for_users.
     *
     * @param approved_userlist $userlist Parameter userlist.
     * @return void Return value.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        $context = $userlist->get_context();
        if (!$context instanceof \context_module || !$userlist->get_userids()) {
            return;
        }
        $cm = get_coursemodule_from_id('videotrackerprime', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $cueids = $DB->get_fieldset_select(
            'videotrackerprime_cues', 'id', 'videotrackerprimeid = :id', ['id' => $cm->instance]
        );
        if (!$cueids) {
            return;
        }
        [$cuesql, $cueparams] = $DB->get_in_or_equal($cueids, SQL_PARAMS_NAMED, 'cue');
        [$usersql, $userparams] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED, 'usr');
        $DB->delete_records_select(
            'videotrackerprime_answers',
            "checkpointid {$cuesql} AND userid {$usersql}",
            $cueparams + $userparams
        );
    }
}
