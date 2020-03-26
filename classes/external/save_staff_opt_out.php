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

namespace block_markoff\external;

defined('MOODLE_INTERNAL') || die();

use external_function_parameters;

require_once($CFG->libdir . "/externallib.php");

trait save_staff_opt_out {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function save_staff_opt_out_parameters() {
        return new external_function_parameters(
            array()
        );
    }

    public static function save_staff_opt_out() {
        global $DB, $USER;

        // The user is marked off as soon as the block is render.
        // Update the status from 0 to 2 ( opt out).
        $sql = "UPDATE {block_markoff_roll}
                   SET surveystatus = 2
                 WHERE username = ?
                   AND markoffday = ?";
        $params = array($USER->username, date('Y-m-d', time()));
        $DB->execute($sql, $params);

        return array(
            'message' => get_string('optoutdesc', 'block_markoff'),
        );
    }

     /**
      * Returns description of method result value
      * @return external_description
      */
    public static function save_staff_opt_out_returns() {
        return new \external_single_structure(
            array(
                'message' => new \external_value(PARAM_RAW, 'A message for the result.'),
            )
        );
    }


}