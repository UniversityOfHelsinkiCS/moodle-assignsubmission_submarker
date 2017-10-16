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
 * This file contains the definition for the library class for submission marker plugin
 *
 * This class provides all the functionality for the new assign module.
 *
 * @package submissions_submissionmarker
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
// File area for online text submission assignment.
define('ASSIGNSUBMISSION_SUBMISSIONMARKER_FILEAREA', 'submissions_submissionmarker');

/**
 * library class for submissionmarker submission plugin extending submission plugin base class
 *
 * @package assignsubmission_submissionmarker
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_submissionmarker extends assign_submission_plugin {

    /**
     * Get the name of the online text submission plugin
     * @return string
     */
    public function get_name() {
        return get_string('submissionmarker', 'assignsubmission_submissionmarker');
    }


    /**
     * Get onlinetext submission information from the database
     *
     * @param  int $submissionid
     * @return mixed
     */
    private function get_submissionmarker_submission($submissionid) {
        global $DB;

        return $DB->get_record('assignsubmission_submissionmarker', array('submission'=>$submissionid));
    }

    /**
     * Return a list of the text fields that can be imported/exported by this plugin
     *
     * @return array An array of field names and descriptions. (name=>description, ...)
     */
    public function get_editor_fields() {
        return array('submissionmarker' => get_string('pluginname', 'assignsubmission_submissionmarker'));
    }

    /**
     * The assignment has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        $DB->delete_records('assignsubmission_submissionmarker',
                            array('assignment'=>$this->assignment->get_instance()->id));

        return true;
    }

    /**
     * Get file areas returns a list of areas this plugin stores files
     * @return array - An array of fileareas (keys) and descriptions (values)
     */
    public function get_file_areas() {
        return array(ASSIGNSUBMISSION_SUBMISSIONMARKER_FILEAREA=>$this->get_name());
    }

    /**
     * Return the plugin configs for external functions.
     *
     * @return array the list of settings
     * @since Moodle 3.2
     */
    public function get_config_for_external() {
        return (array) $this->get_config();
    }
    
    /**
     * Build submission
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
        $mform->addElement('advcheckbox', 'test1', 'Display 1', null, array(group => 1));
        $mform->addElement('advcheckbox', 'test2', 'Display 2', null, array(group => 1));
        $mform->addElement('advcheckbox', 'test3', 'Display 3', null, array(group => 1));
        $mform->addElement('advcheckbox', 'test4', 'Display 4', null, array(group => 1));
        
        return true;
    }
}