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
 * Activity settings form.
 *
 * @package mod_videotrackerprime
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use local_video_bridge\source\manager as source_manager;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Class mod_videotrackerprime_mod_form.
 */
class mod_videotrackerprime_mod_form extends moodleform_mod {
    /**
     * Method definition.
     *
     * @return void Return value.
     */
    public function definition(): void {
        $mform = $this->_form;
        $sources = new source_manager();
        $options = $sources->get_options(['tracking']);
        if (!$options) {
            throw new moodle_exception('notrackingsources', 'videotrackerprime');
        }

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('videotrackerprimename', 'videotrackerprime'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $this->standard_intro_elements();

        $mform->addElement('header', 'videoheader', get_string('videoheader', 'videotrackerprime'));
        $mform->addElement('select', 'videosource', get_string('videosource', 'videotrackerprime'), $options);
        $mform->setType('videosource', PARAM_PLUGIN);
        $mform->setDefault('videosource', array_key_first($options));
        $sources->add_form_elements($mform, 'videosource');

        $mform->addElement('static', 'checkpointnotice', '', get_string('checkpointnotice', 'videotrackerprime'));

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Method validation.
     *
     * @param mixed $data Parameter data.
     * @param mixed $files Parameter files.
     * @return array Return value.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $manager = new source_manager();
        $source = clean_param((string)($data['videosource'] ?? ''), PARAM_PLUGIN);
        if (!array_key_exists($source, $manager->get_options(['tracking']))) {
            $errors['videosource'] = get_string('notrackingsources', 'videotrackerprime');
            return $errors;
        }
        $errors += $manager->validation((array)$data, (array)$files);
        return $errors;
    }

    /**
     * Method data_preprocessing.
     *
     * @param mixed $defaultvalues Parameter defaultvalues.
     * @return void Return value.
     */
    public function data_preprocessing(&$defaultvalues): void {
        if (!empty($this->current->instance)) {
            (new source_manager())->prepare_form_data($defaultvalues, $this->context);
        }
        foreach (['completionpercent', 'completionallrequired', 'completioncheckpointcount'] as $field) {
            if (array_key_exists($field, $defaultvalues)) {
                $defaultvalues[$this->get_suffixed_name($field)] = $defaultvalues[$field];
            }
        }
    }

    /**
     * Method add_completion_rules.
     *
     * @return array Return value.
     */
    public function add_completion_rules(): array {
        $mform = $this->_form;
        $percent = $this->get_suffixed_name('completionpercent');
        $allrequired = $this->get_suffixed_name('completionallrequired');
        $count = $this->get_suffixed_name('completioncheckpointcount');

        $mform->addElement('text', $percent, get_string('completionpercent', 'videotrackerprime'), ['size' => 4]);
        $mform->setType($percent, PARAM_INT);
        $mform->setDefault($percent, 0);
        $mform->addHelpButton($percent, 'completionpercent', 'videotrackerprime');

        $mform->addElement('advcheckbox', $allrequired, get_string('completionallrequired', 'videotrackerprime'));
        $mform->setDefault($allrequired, 0);

        $mform->addElement('text', $count, get_string('completioncheckpointcount', 'videotrackerprime'), ['size' => 4]);
        $mform->setType($count, PARAM_INT);
        $mform->setDefault($count, 0);

        return [$percent, $allrequired, $count];
    }

    /**
     * Method completion_rule_enabled.
     *
     * @param mixed $data Parameter data.
     * @return bool Return value.
     */
    public function completion_rule_enabled($data): bool {
        return (int)($data[$this->get_suffixed_name('completionpercent')] ?? 0) > 0
            || !empty($data[$this->get_suffixed_name('completionallrequired')])
            || (int)($data[$this->get_suffixed_name('completioncheckpointcount')] ?? 0) > 0;
    }

    /**
     * Method get_data.
     *
     * @return mixed Return value.
     */
    public function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return $data;
        }
        foreach (['completionpercent', 'completionallrequired', 'completioncheckpointcount'] as $field) {
            $suffixed = $this->get_suffixed_name($field);
            if (property_exists($data, $suffixed)) {
                $data->{$field} = $data->{$suffixed};
            }
        }
        return $data;
    }
}
