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

        return $DB->get_record('assignsubmission_submarker', array('submission' => $submissionid));
    }

    /**
     * Return a list of the text fields that can be imported/exported by this plugin
     *
     * @return array An array of field names and descriptions. (name=>description, ...)
     */
    public function get_editor_fields() {
        return array('submissionmarker' => get_string('pluginname', 'assignsubmission_submissionmarker'));
    }

    private function get_edit_options() {
        $options = array(
            'exercisecount' => $this->get_config('exercisecount')
        );
        return $options;
    }

    /**
     * The assignment has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        $DB->delete_records('assignsubmission_submarker', array('assignment' => $this->assignment->get_instance()->id));

        return true;
    }

    /**
     * Get file areas returns a list of areas this plugin stores files
     * @return array - An array of fileareas (keys) and descriptions (values)
     */
    public function get_file_areas() {
        return array(ASSIGNSUBMISSION_SUBMISSIONMARKER_FILEAREA => $this->get_name());
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
        $checked = "";
        if ($submission) {
            $submissionmarkersubmission = $this->get_submissionmarker_submission($submission->id);
            if ($submissionmarkersubmission) {
                //exercises should be something like "10011"
                $checked = $submissionmarkersubmission->exercises;
            }
        }
        
        for ($i = 1; $i <= $this->get_config('exercisecount'); $i++) {
            $checked = $checked . "0";
            $mform->addElement('advcheckbox', 'exerchkbox' . ($i), 'Exercise  ' . ($i), null, array(group => 1));
            if ($checked[$i-1] == 1) {
                $mform->setDefault('exerchkbox' . ($i), true);
            }
        }
        return true;
    }

    public function get_settings(MoodleQuickForm $mform) {
        $settings = array();
        $options = array();
        for ($i = 1; $i <= get_config('assignsubmission_submissionmarker', 'exercisecount'); $i++) {
            $options[$i] = $i;
        }
        $name = get_string('exercisecount', 'assignsubmission_submissionmarker');

        $mform->addElement('select', 'assignsubmission_submissionmarker_exercisecount', $name, $options);
    }

    /**
     * Save the settings for file submission plugin
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        $this->set_config('exercisecount', $data->assignsubmission_submissionmarker_exercisecount);
        return true;
    }

    function debugObject(stdClass $obj) {
        $serialized = serialize($obj);
        $log = fopen("/tmp/debugfile.txt", "a");
        fwrite($log, "\nObject:\n\n");
        fwrite($log, $serialized);
        fclose($log);
    }

    function debug($vari) {
        $log = fopen("/tmp/debugfile.txt", "a");
        fwrite($log, "\nVariable:\n\n");
        fwrite($log, $vari);
        fclose($log);
    }

    function get_exercises_for_DB($data) {
        $checked = "";
        foreach($data as $key=>$value) {
            if (substr( $key, 0, 10 ) === "exerchkbox") {
                $checked = $checked . $value;
            }
        }
        return $checked;
    }
    
    /**
     * Save data to the database.
     *
     * @param stdClass $submission
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $submission, stdClass $data) {
        global $USER, $DB;

        /** $submission:
         * O:8:"stdClass":9:{s:2:"id";s:1:"6";s:10:"assignment";s:1:"3";s:6:"userid";s:1:"6";s:11:"timecreated";s:10:"1508317343";s:12:"timemodified";s:10:"1508324885";s:6:"status";s:9:"submitted";s:7:"groupid";s:1:"0";s:13:"attemptnumber";s:1:"0";s:6:"latest";s:1:"1";}
         */
        
        $options = $this->get_edit_options();

        $data = file_postupdate_standard_editor($data, 
                'submissionmarker', 
                $options, 
                $this->assignment->get_context(), 
                'assignsubmission_submissionmarker', 
                ASSIGNSUBMISSION_SUBMISSIONMARKER_FILEAREA, 
                $submission->id);

        /** $data 4 and 7 checked:
         * O:8:"stdClass":20:{s:12:"lastmodified";i:1508326015;s:17:"files_filemanager";i:126731301;s:5:"test1";s:1:"0";s:5:"test2";s:1:"0";s:5:"test3";s:1:"0";s:5:"test4";s:1:"1";s:5:"test5";s:1:"0";s:5:"test6";s:1:"0";s:5:"test7";s:1:"1";s:5:"test8";s:1:"0";s:5:"test9";s:1:"0";s:6:"test10";s:1:"0";s:2:"id";i:4;s:6:"userid";i:6;s:6:"action";s:14:"savesubmission";s:12:"submitbutton";s:12:"Save changes";s:5:"files";s:1:"1";s:21:"submissionmarkertrust";i:0;s:16:"submissionmarker";N;s:22:"submissionmarkerformat";N;}
         */
        
        $submissionmarkersubmission = $this->get_submissionmarker_submission($submission->id);

        $fs = get_file_storage();

        $files = $fs->get_area_files($this->assignment->get_context()->id, 'assignsubmission_submissionmarker', ASSIGNSUBMISSION_SUBMISSIONMARKER_FILEAREA, $submission->id, 'id', false);

        // Maybe check if none are checked idk

        $params = array(
          'context' => context_module::instance($this->assignment->get_course_module()->id),
          'courseid' => $this->assignment->get_course()->id,
          'objectid' => $submission->id,
          'other' => array(
            'content' => '',
            'pathnamehashes' => array_keys($files)
          )
        );
        if (!empty($submission->userid) && ($submission->userid != $USER->id)) {
          $params['relateduserid'] = $submission->userid;
        }
        $event = \assignsubmission_submissionmarker\event\assessable_uploaded::create($params);
        $event->trigger();

        $groupname = null;
        $groupid = 0;
        // Get the group name as other fields are not transcribed in the logs and this information is important.
        if (empty($submission->userid) && !empty($submission->groupid)) {
          $groupname = $DB->get_field('groups', 'name', array('id' => $submission->groupid), '*', MUST_EXIST);
          $groupid = $submission->groupid;
        } else {
          $params['relateduserid'] = $submission->userid;
        }

          // Unset the objectid and other field from params for use in submission events.
        unset($params['objectid']);
        unset($params['other']);
        $params['other'] = array(
          'submissionid' => $submission->id,
          'submissionattempt' => $submission->attemptnumber,
          'submissionstatus' => $submission->status,
          'groupid' => $groupid,
          'groupname' => $groupname
        );
        
        $exercises = $this->get_exercises_for_DB($data);

        if ($submissionmarkersubmission) {
          //Update
          $submissionmarkersubmission->exercises = $exercises;
          $params['objectid'] = $submissionmarkersubmission->id;
          $updatestatus = $DB->update_record('assignsubmission_submarker', $submissionmarkersubmission);
          $event = \assignsubmission_submissionmarker\event\submission_updated::create($params);
          $event->set_assign($this->assignment);
          $event->trigger();
          
          return $updatestatus;
        } else {
          //Create
          $submissionmarkersubmission = new stdClass();
          $submissionmarkersubmission->exercises = $exercises;

          $submissionmarkersubmission->submission = $submission->id;
          $submissionmarkersubmission->assignment = $this->assignment->get_instance()->id;
          $submissionmarkersubmission->id = $DB->insert_record('assignsubmission_submarker', $submissionmarkersubmission);
          $params['objectid'] = $submissionmarkersubmission->id;
          $event = \assignsubmission_submissionmarker\event\submission_created::create($params);
          $event->set_assign($this->assignment);
          $event->trigger();
          return $submissionmarkersubmission->id > 0;
        }
    }

}
