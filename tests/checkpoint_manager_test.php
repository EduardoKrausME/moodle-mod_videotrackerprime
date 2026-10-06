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
 * Tests checkpoint response validation.
 *
 * @package mod_videotrackerprime
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videotrackerprime;

/**
 * Class checkpoint_manager_test.
 */
final class checkpoint_manager_test extends \advanced_testcase {
    /**
     * Method test_reflection_requires_non_empty_text.
     *
     * @covers \mod_videotrackerprime\checkpoint_manager::validate_response
     * @return void Return value.
     */
    public function test_reflection_requires_non_empty_text(): void {
        $cue = (object)[
            'type' => 'reflection',
            'configjson' => json_encode(['maxchars' => 100]),
            'requireinteraction' => 0,
        ];
        [$response, $completed] = checkpoint_manager::validate_response($cue, '<b>Hello</b>');
        $this->assertSame('Hello', $response);
        $this->assertTrue($completed);

        [$response, $completed] = checkpoint_manager::validate_response($cue, '   ');
        $this->assertSame('', trim($response));
        $this->assertFalse($completed);
    }

    /**
     * Method test_confidence_accepts_only_one_to_five.
     *
     * @covers \mod_videotrackerprime\checkpoint_manager::validate_response
     * @return void Return value.
     */
    public function test_confidence_accepts_only_one_to_five(): void {
        $cue = (object)['type' => 'confidence', 'configjson' => '{}', 'requireinteraction' => 0];
        [, $completed] = checkpoint_manager::validate_response($cue, '5');
        $this->assertTrue($completed);
        [, $completed] = checkpoint_manager::validate_response($cue, '6');
        $this->assertFalse($completed);
    }

    /**
     * Method test_poll_must_match_configured_option.
     *
     * @covers \mod_videotrackerprime\checkpoint_manager::validate_response
     * @return void Return value.
     */
    public function test_poll_must_match_configured_option(): void {
        $cue = (object)[
            'type' => 'poll',
            'configjson' => json_encode(['options' => ['A', 'B']]),
            'requireinteraction' => 0,
        ];
        [$response, $completed] = checkpoint_manager::validate_response($cue, 'B');
        $this->assertSame('B', $response);
        $this->assertTrue($completed);
        [$response, $completed] = checkpoint_manager::validate_response($cue, 'C');
        $this->assertSame('', $response);
        $this->assertFalse($completed);
    }

    /**
     * Checkpoint availability honours both optional boundaries.
     *
     * @covers \mod_videotrackerprime\checkpoint_manager::is_available
     * @return void
     */
    public function test_checkpoint_availability_window(): void {
        $this->assertTrue(checkpoint_manager::is_available(
            (object)['timestart' => 0, 'timeend' => 0],
            1000
        ));
        $this->assertTrue(checkpoint_manager::is_available(
            (object)['timestart' => 900, 'timeend' => 1100],
            1000
        ));
        $this->assertFalse(checkpoint_manager::is_available(
            (object)['timestart' => 1001, 'timeend' => 0],
            1000
        ));
        $this->assertFalse(checkpoint_manager::is_available(
            (object)['timestart' => 0, 'timeend' => 999],
            1000
        ));
    }
}
