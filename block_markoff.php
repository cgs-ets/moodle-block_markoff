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
 * @package     block_markoff
 * @copyright   2020 Veronica Bermegui, Michael Vangelovski
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class block_markoff extends block_base {

    public function init() {
        $this->title = get_string('markoff', 'block_markoff');
    }

    /**
     * Controls whether multiple instances of the block are allowed on a page
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Controls whether the block is configurable
     *
     * @return bool
     */
    public function instance_allow_config() {
        return false;
    }

    /**
     * Set where the block should be allowed to be added
     *
     * @return array
     */
    public function applicable_formats() {
        return array(
            'course-view'    => false,
            'site'           => true,
            'mod'            => false,
            'my'             => false,
        );
    }

    public function get_content() {
        global $OUTPUT, $USER, $DB;

        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        // Check that the question config is available.
        if (empty($this->config->questiontitle) || empty($this->config->questionbody)) {
            return null;
        }

        // If user is inpersonating another, don't show the block.
        if (\core\session\manager::is_loggedinas()) {
            return null;
        }

        // If user is in the exclude list, don't show the block.
        if (in_array($USER->username, explode(',', str_replace(' ', '', $this->config->excludeusers)))) {
            return null;
        }

        // Only continue processing and display block if user is a student or staff.
        preg_match('/(students|staff)/', strtolower($USER->profile['CampusRoles']), $matches);

        if ( ! $matches) {
            return null;
        }

        $role = false;
        if ($matches[0] == 'staff') {
            $role = true;
            // Check the day to display the survey
            if (!in_array(date('w', time()), $this->config->displaydaystaff)) {
                return null;
            }
        } else if ($matches[0] == 'students') {
            // Check the day to display the survey
            if (!in_array(date('w', time()), $this->config->displaydaystudent)) {
                return null;
            }
        }
    
        // Set up some vars.
        $now = time();
        $markoffday = date('Y-m-d', $now);
        $markofftime = date('H:i', $now);

        // Check if the user has already been marked off today.
        $sql = "SELECT *
                  FROM {block_markoff_roll}
                 WHERE username = ?
                   AND markoffday = ?";
        $params = array(
            $USER->username,
            $markoffday,
        );

        $record = $DB->get_record_sql($sql, $params);
        #var_dump($record); exit;

        if ( ! $record) {
            // Mark the user off.
            $table = 'block_markoff_roll';
            $record = new stdClass();
            $record->markoffday = $markoffday;
            $record->markofftime = $markofftime;
            $record->username = $USER->username;
            $record->surveystatus = 0;
            $record->timecreated = $now;
            $DB->insert_record($table, $record);
        }

        // If not within the display times don't show block.
        $showtimestart = new DateTime("0600");
        $showtimefinish = new DateTime("2359");
        $currenttime = new Datetime('now');
        if ($currenttime < $showtimestart || $currenttime > $showtimefinish) {
            return null;
        }

        // If survey already completed don't show the block.
        if ($record->surveystatus) {
            return null;
        }


        $data = array(
            'instanceid' => $this->instance->id,
            'questiontitle' => $this->config->questiontitle,
            'questionbody' => file_rewrite_pluginfile_urls($this->config->questionbody['text'], 'pluginfile.php', $this->context->id, 'block_markoff', 'block_html', 0),
            'staff' => $role,
            'reason' => $this->config->reason,            

        );
        //var_dump($data); exit;
        $this->content->text = $OUTPUT->render_from_template('block_markoff/survey', $data);

        return $this->content;
    }

    public function hide_header() {
        return true;
    }

    public function get_required_javascript() {
        parent::get_required_javascript();

        $this->page->requires->js_call_amd('block_markoff/controls', 'init', [
            'instanceid' => $this->instance->id,
            'isstudent' => $this->is_student(),
        ]);
    }

    private function is_student(){
        // Only continue processing and display block if user is a student or staff.
        global $USER;

        preg_match('/students/', strtolower($USER->profile['CampusRoles']), $matches);

        if ($matches) {
            return true;
        }
        return false;

    }
}