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
 * @package submissions_submarker
 * @copyright 2017 University of Helsinki
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
// File area for submarker submission assignment.
define('ASSIGNSUBMISSION_SUBMARKER_FILEAREA', 'submissions_submarker');
require_once('student_form.php');

/**
 * library class for submarker submission plugin extending submission plugin base class
 *
 * @package assignsubmission_submarker
 * @copyright 2017 University of Helsinki
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_submarker extends assign_submission_plugin {

    /**
     * Get the name of the submarker submission plugin
     * @return string
     */
    public function get_name() {
        return get_string('submarker', 'assignsubmission_submarker');
    }

    /**
     * Get submarker submission information from the database
     *
     * @param  int $submissionid
     * @return mixed
     */
    private function get_submarker_submission($submissionid) {
        global $DB;

        return $DB->get_record('assignsubmission_submarker', array('submission' => $submissionid));
    }

    /**
     * Return a list of the text fields that can be imported/exported by this plugin
     *
     * @return array An array of field names and descriptions. (name=>description, ...)
     */
    public function get_editor_fields() {
        return array('submarker' => get_string('pluginname', 'assignsubmission_submarker'));
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
        return array(ASSIGNSUBMISSION_SUBMARKER_FILEAREA => $this->get_name());
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
            $submarkersubmission = $this->get_submarker_submission($submission->id);
            if ($submarkersubmission) {
                //exercises should be something like "10011"
                $checked = $submarkersubmission->exercises;
            }
        }
        
        $groupid = 1;
        $i = 0;
        $max = $this->get_config('exercisecount');
        while (true) {
            $checked = $checked . "0";
            $checkboxarray=array();
            for ($j = 0; $j < 3; $j++) {
                $i++;
                if ($i <= $max) {
                    $checkboxarray[] =& $mform->createElement('advcheckbox', 'exerchkbox' . ($i),  get_string('exercise', 'assignsubmission_submarker') . ' ' . ($i), null, array('group' => $groupid));
                }
            }
            $mform->addGroup($checkboxarray, 'chkboxar', '', array(' '), false);
            if ($i > $max) {
                break;
            }          
        }
        // Select exercises that are already done.
        for ($i = 1; $i <= $this->get_config('exercisecount'); $i++) {
            $checked = $checked . "0";
            if ($checked[$i-1] == 1) {
                $mform->setDefault('exerchkbox' . ($i), true);
            }
        }
        // If there are more than 1 exercise, render a "select all" button.
        if ($max > 1) {
            $student_form = new student_form();
            $student_form->draw_checkbox_controller($mform, $groupid, get_string('selectall', 'assignsubmission_submarker'));
        }
        return true;
    }

    public function get_settings(MoodleQuickForm $mform) {
        $settings = array();
        $options = array();
        for ($i = 0; $i <= get_config('assignsubmission_submarker', 'exercisecount'); $i++) {
            $options[$i] = $i;
        }
        $name = get_string('exercisecount', 'assignsubmission_submarker');

        $mform->addElement('select', 'assignsubmission_submarker_exercisecount', $name, $options);
        $mform->addHelpButton('assignsubmission_submarker_exercisecount',
                'exerciseteacher_help',
                'assignsubmission_submarker');
        $mform->disabledIf('assignsubmission_submarker_exercisecount', 'assignsubmission_submarker_enabled', 'notchecked');
    }

    /**
     * Save the settings for file submission plugin
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        $this->set_config('exercisecount', $data->assignsubmission_submarker_exercisecount);
        return true;
    }


    /*
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
    */

    /**
     * Save data to the database.
     *
     * @param stdClass $submission
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $submission, stdClass $data) {
        global $USER, $DB;

        $options = $this->get_edit_options();

        $data = file_postupdate_standard_editor($data,
                'submarker',
                $options,
                $this->assignment->get_context(),
                'assignsubmission_submarker',
                ASSIGNSUBMISSION_SUBMARKER_FILEAREA,
                $submission->id);

        $submarkersubmission = $this->get_submarker_submission($submission->id);
        $exercises = $this->exercises_to_text($data);
        
        $fs = get_file_storage();

        $files = $fs->get_area_files($this->assignment->get_context()->id, 'assignsubmission_submarker', ASSIGNSUBMISSION_SUBMARKER_FILEAREA, $submission->id, 'id', false);

        $params = array(
          'context' => context_module::instance($this->assignment->get_course_module()->id),
          'courseid' => $this->assignment->get_course()->id,
          'objectid' => $submission->id,
          'other' => array(
            'content' => $exercises,
            'pathnamehashes' => array_keys($files)
          )
        );
        if (!empty($submission->userid) && ($submission->userid != $USER->id)) {
          $params['relateduserid'] = $submission->userid;
        }
        $event = \assignsubmission_submarker\event\assessable_uploaded::create($params);
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

        $creatednew = false;
        if ($submarkersubmission) {
          //Update
          $submarkersubmission->exercises = $exercises;
          $params['objectid'] = $submarkersubmission->id;
          $updatestatus = $DB->update_record('assignsubmission_submarker', $submarkersubmission);
          $event = \assignsubmission_submarker\event\submission_updated::create($params);
        } else {
          //Create
          $creatednew = true;
          $submarkersubmission = new stdClass();
          $submarkersubmission->exercises = $exercises;

          $submarkersubmission->submission = $submission->id;
          $submarkersubmission->assignment = $this->assignment->get_instance()->id;
          $submarkersubmission->id = $DB->insert_record('assignsubmission_submarker', $submarkersubmission);
          $params['objectid'] = $submarkersubmission->id;
          $event = \assignsubmission_submarker\event\submission_created::create($params);
        }
        $event->set_assign($this->assignment);
        $event->trigger();

        //Giving grade has to be after submission to get the submission and attemptnumber to match it from DB.
        $grade = $this->assignment->get_user_grade($USER->id, true, $submission->attemptnumber);
        $grade->grade = $this->get_completed_exercise_count($exercises);
        $this->assignment->update_grade($grade);

        if ($creatednew) {
            return $submarkersubmission->id > 0;            
        } else {
            return $updatestatus;            
        }
    }

    /**
     * Makes the exercises saveable to database. Used during save.
     * 
     * @param string $data is the data from submission
     * @return string The exercises in "binary" form: "00110"
     */
    function exercises_to_text($data) {
        $checked = "";
        foreach($data as $key=>$value) {
            if (substr( $key, 0, 10 ) === "exerchkbox") {
                $checked = $checked . $value;
            }
        }
        return $checked;
    }

    /**
     * Makes the exercise count human readable. Used to create the summary.
     * 
     * @param string $exercises is the "binary" format of the exercises
     * @return string The exercises in human readable form: "1, 2, 5"
     */
    public function exercises_readable($exercises) {
      $res = '';
      for ($i = 1; $i <= strlen($exercises); $i++){
        if ($exercises[$i - 1] == '1') {
            $res .= $i.', ';
        }
      }
      if(strlen($res) > 1) {
        $res = substr($res, 0, -2);
      } else {
        $res = get_string('no_exercises_returned', 'assignsubmission_submarker');
      }
      return $res;
    }

    /**
     * Counts the number of completed exercises. Used to grade.
     * 
     * @param string $exercises is the "binary" format of the exercises
     * @return string The exercise count.
     */
    function get_completed_exercise_count($exercises) {
        $count = 0;
        for ($i = 0; $i < strlen($exercises); $i++){
            if ($exercises[$i] == '1') {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Display the completed exercises in the submission status table
     *
     * @param stdClass $submission
     * @param bool $showviewlink Set this to true if the list of files is long
     * @return string
     */
    public function view_summary(stdClass $submission, & $showviewlink) {
      $submarkersubmission = $this->get_submarker_submission($submission->id);
      $exer = $submarkersubmission->exercises;
      return $this->exercises_readable($exer);
    }

    /**
     * Copy the student's submission from a previous submission. Used when a student opts to base their resubmission
     * on the last submission.
     * @param stdClass $sourcesubmission
     * @param stdClass $destsubmission
     */
    public function copy_submission(stdClass $sourcesubmission, stdClass $destsubmission) {
        global $DB;

        // Copy the files across.
        $contextid = $this->assignment->get_context()->id;
        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid,
                                     'assignsubmission_submarker',
                                     ASSIGNSUBMISSION_SUBMARKER_FILEAREA,
                                     $sourcesubmission->id,
                                     'id',
                                     false);
        foreach ($files as $file) {
            $fieldupdates = array('itemid' => $destsubmission->id);
            $fs->create_file_from_storedfile($fieldupdates, $file);
        }

        // Copy the assignsubmission_submarker record.
        if ($submarkersubmission = $this->get_submarker_submission($sourcesubmission->id)) {
            unset($submarkersubmission->id);
            $submarkersubmission->submission = $destsubmission->id;
            $DB->insert_record('assignsubmission_submarker', $submarkersubmission);
        }
        return true;
    }

    /**
     * Always false, because even if returning no exercises there will be a visible message
     * @param stdClass $submission
     */
    public function is_empty(stdClass $submission) {
        return false;
    }
    public function submission_is_empty(stdClass $data) {
        foreach($data as $key=>$value) {
            if (substr( $key, 0, 10 ) === "exerchkbox") {
                if ($value == '1') {
                    return false;
                }
            }
        }
        return true;
    }

}
