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
 * Provides the block_markoff/control module
 *
 * @package   block_markoff
 * @category  output
 * @copyright 2020 Veronica Bermegui, Michael Vangelovski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module block_markoff/control
 */
 define(['jquery', 'core/log', 'core/ajax'], function ($, Log, Ajax) {
    'use strict';

    /**
     * Initializes the block controls.
     */
    function init(instanceid,isstudent) {
        Log.debug('block_markoff/control: initializing controls of the block_markoff block instance ' + instanceid);

        var region = $('[data-region="block_markoff-instance-' + instanceid + '"]').first();

        if (!region.length) {
            Log.debug('block_markoff/control: wrapping region not found!');
            return;
        }

        var control = new MarkoffControl(region, instanceid,isstudent);
        control.main();
    }

    // Constructor.
    function MarkoffControl(region, instanceid,isstudent) {
        var self = this;
        self.region = region;
        self.instanceid = instanceid;
        self.isstudent = isstudent;
    }

    MarkoffControl.prototype.main = function () {
        var self = this;

        // Handle answer click.
        self.region.on('click', '.answer .option', function(e) {
            e.preventDefault();
            var option = $(this);
            self.saveSurveyResponse(option);
        });

        self.region.on('click', '.survey-exit', function(e){
            e.preventDefault();
            self.saveStaffOptOut();
            self.region.remove();
        });
        
        // Display Help Section to Student
        if(self.isstudent == true){
            self.region.find('.help').addClass('student');
        }        

    }


    MarkoffControl.prototype.saveStaffOptOut =  function(){
        Ajax.call([{
            methodname: 'block_markoff_save_staff_opt_out',
            args: {},
            done: function (response) {
              Log.debug(response);
            },
            fail: function (reason) {
              Log.error(reason);
            }
        }]);
    }

    MarkoffControl.prototype.saveSurveyResponse = function (option) {
        var self = this;

        // Check if already submiting.
        if (self.region.hasClass('submitting')) {
            return;
        }

        var question = option.closest('.question');
        var questionid = question.data('id');
        var questiontitle = question.data('title');
        var response = option.data('value');
        
        if (questionid == null || questiontitle == null || response == null) {
            return;
        }

        self.region.addClass('submitting');
        question.addClass('submitting');
        option.addClass('submitting');

        Ajax.call([{
            methodname: 'block_markoff_save_survey_response',
            args: {
              questionid: questionid,
              questiontitle: questiontitle,
              response: response
            },
            done: function (response) {
              if (response.completed) {
                self.region.find('.survey').html(response.message);
                self.region.delay(2000).fadeOut(400);
                ('body').removeClass('markoff-visible');
              }
            },
            fail: function (reason) {
              self.region.find('.survey').html('<h3>Error: Failed to save survey response.</h3>');
              Log.error(reason);
            }
        }]);
    }

    return {
        init: init
    };
 });