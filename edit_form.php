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
 * A block to mark off students and display a survey.
 *
 * @package   block_emergency_alerts
 * @copyright Michael Vangelovski, Canberra Grammar School <michael.vangelovski@cgs.act.edu.au>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/lib/formslib.php');

define('DEFAULT_NUMBER_ALERTS', 1);

/**
 * Edit form class
 *
 * @package   block_markoff
 * @copyright 2020 Michael Vangelovski, Canberra Grammar School
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_markoff_edit_form extends block_edit_form {

    /**
     * Form definition
     *
     * @param \moodleform $mform
     * @return void
     */
    protected function specific_definition($mform) {

        $mform->addElement('header', 'configheader', get_string('pluginname', 'block_markoff'));

        $mform->addElement('text', 'config_questiontitle', get_string('questiontitle', 'block_markoff'));
        $mform->setType('config_questiontitle', PARAM_TEXT);

        $type = 'editor';
        $name = 'config_questionbody';
        $label = get_string('questionbody', 'block_markoff');
        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $this->block->context);
        $attributes = 'rows="12"';
        $mform->addElement($type, $name, $label, $attributes, $editoroptions);
        $mform->setType('config_questionbody', PARAM_RAW);

        $mform->addElement('textarea', 'config_reason', get_string('reason', 'block_markoff'), 'wrap="virtual" rows="4" cols="50"');
        $mform->setType('config_reason', PARAM_TEXT);

        $mform->addElement('text', 'config_excludeusers', get_string('excludeusers', 'block_markoff'));
        $mform->setType('config_excludeusers', PARAM_TEXT);

        }

    /**
     * Return submitted data.
     *
     * @return object submitted data.
     */
    public function get_data() {
        $data = parent::get_data();

        if ($data) {
            // Save message files to a permanent file area.
            if ( ! empty($data->config_questionbody) ) {
                $html = file_save_draft_area_files(
                    $data->config_questionbody['itemid'],
                    $this->block->context->id,
                    'block_markoff',
                    'block_html',
                    0,
                    array('maxfiles' => 20, 'maxbytes' => 5000000, 'trusttext'=> true, 'subdirs' => 0),
                    $data->config_questionbody['text']
                );
                $data->config_questionbody['text'] = $html;
            }

        }
        return $data;
    }

    /**
     * Set form data.
     *
     * @param array $defaults
     * @return void
     */
    public function set_data($defaults) {
        global $USER;

        if (isset($this->block->config->questionbody)) {
            //$itemid = ''; // Empty string force creates a new area and copy existing files into.

            // Fetch the draft file areas. On initial load this is empty and new draft areas are created.
            // On subsequent loads the draft areas are retreived.
            //if (isset($_REQUEST['config_questionbody'])) {
            //    $itemid = $_REQUEST['config_questionbody']['itemid'];
            //}

            $itemid = file_get_submitted_draft_itemid('config_questionbody');

            // Copy all the files from the 'real' area, into the draft areas.
            $html = file_prepare_draft_area(
                $itemid,
                $this->block->context->id,
                'block_markoff',
                'block_html',
                0,
                array('maxfiles' => 10, 'maxbytes' => 5000000, 'trusttext'=> true, 'subdirs' => 0),
                $this->block->config->questionbody['text']
            );

            $this->block->config->questionbody['itemid'] = $itemid;
            $this->block->config->questionbody['text'] = $html;
        }
        // Set form data.
        parent::set_data($defaults);
    }
}