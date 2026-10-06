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
 * Checkpoint domain logic.
 *
 * @package mod_videotrackerprime
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videotrackerprime;

use context_module;
use local_video_bridge\source\manager as source_manager;
use stdClass;

/**
 * Class checkpoint_manager.
 */
class checkpoint_manager {
    public const TYPES = [
        'message', 'confirmation', 'reflection', 'confidence',
        'poll', 'resource', 'alert', 'checkpoint',
    ];

    /**
     * Returns every checkpoint configured for an activity.
     *
     * Unlike get_active(), this method intentionally ignores availability
     * windows because teachers must still be able to edit future and expired
     * checkpoints.
     *
     * @param int $activityid Activity instance id.
     * @return array
     */
    public static function get_all(int $activityid): array {
        global $DB;

        return array_values($DB->get_records(
            'videotrackerprime_cues',
            ['videotrackerprimeid' => $activityid],
            'timestamp ASC, sortorder ASC, id ASC'
        ));
    }

    /**
     * Checks whether a checkpoint is currently inside its availability window.
     *
     * @param stdClass $cue Checkpoint record.
     * @param int|null $now Timestamp used for the check, mainly useful in tests.
     * @return bool
     */
    public static function is_available(stdClass $cue, ?int $now = null): bool {
        $now ??= time();
        $start = (int)($cue->timestart ?? 0);
        $end = (int)($cue->timeend ?? 0);

        return ($start === 0 || $start <= $now) && ($end === 0 || $end >= $now);
    }

    /**
     * Method get_active.
     *
     * @param int $activityid Parameter activityid.
     * @param int $userid Parameter userid.
     * @return array Return value.
     */
    public static function get_active(int $activityid, int $userid = 0): array {
        global $DB;
        $now = time();
        $records = $DB->get_records_select(
            'videotrackerprime_cues',
            'videotrackerprimeid = :id AND (timestart = 0 OR timestart <= :now1) AND (timeend = 0 OR timeend >= :now2)',
            ['id' => $activityid, 'now1' => $now, 'now2' => $now],
            'timestamp ASC, sortorder ASC, id ASC'
        );
        if (!$userid) {
            return array_values($records);
        }

        $seen = [];
        if ($records) {
            $ids = array_keys($records);
            [$insql, $params] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'cue');
            $params['userid'] = $userid;
            $sql = "SELECT checkpointid, MAX(completed) AS completed
                      FROM {videotrackerprime_answers}
                     WHERE userid = :userid AND checkpointid {$insql}
                  GROUP BY checkpointid";
            foreach ($DB->get_records_sql($sql, $params) as $row) {
                $seen[(int)$row->checkpointid] = !empty($row->completed);
            }
        }

        $out = [];
        foreach ($records as $record) {
            $record->alreadycompleted = !empty($seen[$record->id]);
            $out[] = $record;
        }
        return $out;
    }

    /**
     * Method serialise_for_client.
     *
     * @param stdClass $cue Parameter cue.
     * @return array Return value.
     */
    public static function serialise_for_client(stdClass $cue): array {
        $config = json_decode((string)$cue->configjson, true);
        return [
            'id' => (int)$cue->id,
            'time' => (float)$cue->timestamp,
            'type' => (string)$cue->type,
            'title' => format_string($cue->title),
            'body' => format_text($cue->body ?? '', (int)$cue->bodyformat, ['noclean' => false]),
            'config' => is_array($config) ? $config : [],
            'required' => !empty($cue->required),
            'pausevideo' => !empty($cue->pausevideo),
            'requireinteraction' => !empty($cue->requireinteraction),
            'dismissible' => !empty($cue->dismissible),
            'onceonly' => !empty($cue->onceonly),
            'replaynewsession' => !empty($cue->replaynewsession),
            'visibleontimeline' => !empty($cue->visibleontimeline),
            'alreadycompleted' => !empty($cue->alreadycompleted),
        ];
    }

    /**
     * Method normalise.
     *
     * @param array $data Parameter data.
     * @param stdClass $activity Parameter activity.
     * @param context_module $context Parameter context.
     * @return stdClass Return value.
     */
    public static function normalise(array $data, stdClass $activity, context_module $context): stdClass {
        $cue = new stdClass();
        $cue->id = (int)($data['id'] ?? 0);
        $cue->videotrackerprimeid = (int)$activity->id;
        $cue->timestamp = max(0, (float)($data['timestamp'] ?? 0));
        $cue->title = clean_param((string)($data['title'] ?? ''), PARAM_TEXT);
        $cue->body = clean_text((string)($data['body'] ?? ''), FORMAT_HTML);
        $cue->bodyformat = FORMAT_HTML;
        $cue->type = clean_param((string)($data['type'] ?? 'message'), PARAM_ALPHA);
        if (!in_array($cue->type, self::TYPES, true)) {
            throw new \invalid_parameter_exception('Invalid checkpoint type.');
        }

        $cue->required = empty($data['required']) ? 0 : 1;
        $cue->pausevideo = empty($data['pausevideo']) ? 0 : 1;
        $cue->requireinteraction = empty($data['requireinteraction']) ? 0 : 1;
        $cue->dismissible = empty($data['dismissible']) ? 0 : 1;
        $cue->onceonly = empty($data['onceonly']) ? 0 : 1;
        $cue->replaynewsession = empty($data['replaynewsession']) ? 0 : 1;
        $cue->visibleontimeline = empty($data['visibleontimeline']) ? 0 : 1;
        $cue->timestart = max(0, (int)($data['timestart'] ?? 0));
        $cue->timeend = max(0, (int)($data['timeend'] ?? 0));
        $cue->sortorder = max(0, (int)($data['sortorder'] ?? 0));

        if ($cue->timeend && $cue->timestart && $cue->timeend < $cue->timestart) {
            throw new \invalid_parameter_exception('End date must be after start date.');
        }
        if ($cue->title === '') {
            throw new \invalid_parameter_exception('Checkpoint title is required.');
        }
        if (\core_text::strlen($cue->title) > 255) {
            throw new \invalid_parameter_exception('Checkpoint title is too long.');
        }

        $caps = (new source_manager())->get_capabilities((string)$activity->videosource);
        if (empty($caps['tracking'])) {
            throw new \invalid_parameter_exception(get_string('notrackingsources', 'videotrackerprime'));
        }
        if (($cue->pausevideo || $cue->requireinteraction) && empty($caps['playbackcontrol'])) {
            throw new \invalid_parameter_exception(
                get_string('playbackcontrolrequired', 'videotrackerprime')
            );
        }

        $config = [];
        if ($cue->type === 'poll') {
            $raw = preg_split('/\R/u', (string)($data['options'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
            $options = [];
            foreach ($raw as $option) {
                $option = clean_param(trim($option), PARAM_TEXT);
                if ($option !== '') {
                    $options[] = $option;
                }
            }
            $options = array_values(array_unique($options));
            if (count($options) < 2 || count($options) > 20) {
                throw new \invalid_parameter_exception('Poll requires between 2 and 20 options.');
            }
            $config['options'] = $options;
        } else if ($cue->type === 'resource') {
            $url = clean_param((string)($data['resourceurl'] ?? ''), PARAM_URL);
            if ($url === '') {
                throw new \invalid_parameter_exception('A resource URL is required.');
            }
            $config['url'] = $url;
            $config['label'] = clean_param((string)($data['resourcelabel'] ?? ''), PARAM_TEXT);
        } else if ($cue->type === 'reflection') {
            $config['maxchars'] = min(2000, max(50, (int)($data['maxchars'] ?? 500)));
        }
        $cue->configjson = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        return $cue;
    }

    /**
     * Method validate_response.
     *
     * @param stdClass $cue Parameter cue.
     * @param string $response Parameter response.
     * @return array Return value.
     */
    public static function validate_response(stdClass $cue, string $response): array {
        $config = json_decode((string)$cue->configjson, true);
        $config = is_array($config) ? $config : [];
        $clean = '';
        $completed = true;

        switch ($cue->type) {
            case 'confirmation':
                $clean = $response === '1' ? '1' : '';
                $completed = $clean === '1';
                break;
            case 'reflection':
                $limit = min(2000, max(50, (int)($config['maxchars'] ?? 500)));
                $clean = \core_text::substr(clean_param($response, PARAM_TEXT), 0, $limit);
                $completed = trim($clean) !== '';
                break;
            case 'confidence':
                $value = (int)$response;
                $clean = ($value >= 1 && $value <= 5) ? (string)$value : '';
                $completed = $clean !== '';
                break;
            case 'poll':
                $clean = clean_param($response, PARAM_TEXT);
                $completed = in_array($clean, $config['options'] ?? [], true);
                if (!$completed) {
                    $clean = '';
                }
                break;
            default:
                $clean = '';
                $completed = true;
        }

        if ($cue->requireinteraction && !$completed) {
            throw new \invalid_parameter_exception('A valid interaction is required.');
        }
        return [$clean, $completed];
    }

    /**
     * Method completion_counts.
     *
     * @param int $activityid Parameter activityid.
     * @param int $userid Parameter userid.
     * @return array Return value.
     */
    public static function completion_counts(int $activityid, int $userid): array {
        global $DB;
        $total = (int)$DB->count_records('videotrackerprime_cues', ['videotrackerprimeid' => $activityid]);
        $required = (int)$DB->count_records('videotrackerprime_cues', [
            'videotrackerprimeid' => $activityid,
            'required' => 1,
        ]);

        $sql = "SELECT COUNT(DISTINCT a.checkpointid)
                  FROM {videotrackerprime_answers} a
                  JOIN {videotrackerprime_cues} c ON c.id = a.checkpointid
                 WHERE c.videotrackerprimeid = :activityid
                   AND a.userid = :userid
                   AND a.completed = 1";
        $completed = (int)$DB->count_records_sql($sql, ['activityid' => $activityid, 'userid' => $userid]);

        $sql .= " AND c.required = 1";
        $requiredcompleted = (int)$DB->count_records_sql($sql, ['activityid' => $activityid, 'userid' => $userid]);

        return compact('total', 'required', 'completed', 'requiredcompleted');
    }
}
