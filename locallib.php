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
      for ($i = 1; $i <= $this->get_config('exercisecount'); $i++) {
          $mform->addElement('advcheckbox', 'test1', 'Exercise  ' . ($i), null, array(group => 1));
      }

        // $mform->addElement('advcheckbox', 'test2', 'Display 2', null, array(group => 1));
        // $mform->addElement('advcheckbox', 'test3', 'Display 3', null, array(group => 1));
        // $mform->addElement('advcheckbox', 'test4', 'Display 4', null, array(group => 1));

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


    /**
     * Save data to the database and trigger plagiarism plugin,
     * if enabled, to scan the uploaded content via events trigger
     *
     * @param stdClass $submission
     * @param stdClass $data
     * @return bool
     */
    // public function save(stdClass $submission, stdClass $data) {
    //     global $USER, $DB;
    //
    //     $editoroptions = $this->get_edit_options();
    //
    //     $data = file_postupdate_standard_editor($data,
    //                                             'submissionmarker',
    //                                             $editoroptions,
    //                                             $this->assignment->get_context(),
    //                                             'assignsubmission_submissionmarker',
    //                                             ASSIGNSUBMISSION_SUBMISSIONMARKER_FILEAREA,
    //                                             $submission->id);
    //
    //     $onlinetextsubmission = $this->get_onlinetext_submission($submission->id);
    //
    //     $fs = get_file_storage();
    //
    //     $files = $fs->get_area_files($this->assignment->get_context()->id,
    //                                  'assignsubmission_submissionmarker',
    //                                  ASSIGNSUBMISSION_SUBMISSIONMARKER_FILEAREA,
    //                                  $submission->id,
    //                                  'id',
    //                                  false);
    //
    //     // Maybe check if none are checked idk
    //
    //     $params = array(
    //         'context' => context_module::instance($this->assignment->get_course_module()->id),
    //         'courseid' => $this->assignment->get_course()->id,
    //         'objectid' => $submission->id,
    //         'other' => array(
    //             'pathnamehashes' => array_keys($files),
    //             'content' => trim($data->onlinetext),
    //             'format' => $data->onlinetext_editor['format']
    //         )
    //     );
    //     if (!empty($submission->userid) && ($submission->userid != $USER->id)) {
    //         $params['relateduserid'] = $submission->userid;
    //     }
    //     $event = \assignsubmission_submissionrker\event\assessable_uploaded::create($params);
    //     $event->trigger();
    //
    //     $groupname = null;
    //     $groupid = 0;
    //     // Get the group name as other fields are not transcribed in the logs and this information is important.
    //     if (empty($submission->userid) && !empty($submission->groupid)) {
    //         $groupname = $DB->get_field('groups', 'name', array('id' => $submission->groupid), '*', MUST_EXIST);
    //         $groupid = $submission->groupid;
    //     } else {
    //         $params['relateduserid'] = $submission->userid;
    //     }
    //
    //     $count = count_words($data->onlinetext);
    //
    //     // Unset the objectid and other field from params for use in submission events.
    //     unset($params['objectid']);
    //     unset($params['other']);
    //     $params['other'] = array(
    //         'submissionid' => $submission->id,
    //         'submissionattempt' => $submission->attemptnumber,
    //         'submissionstatus' => $submission->status,
    //         'onlinetextwordcount' => $count,
    //         'groupid' => $groupid,
    //         'groupname' => $groupname
    //     );
    //
    //     if ($onlinetextsubmission) {
    //
    //         $onlinetextsubmission->onlinetext = $data->onlinetext;
    //         $onlinetextsubmission->onlineformat = $data->onlinetext_editor['format'];
    //         $params['objectid'] = $onlinetextsubmission->id;
    //         $updatestatus = $DB->update_record('assignsubmission_onlinetext', $onlinetextsubmission);
    //         $event = \assignsubmission_onlinetext\event\submission_updated::create($params);
    //         $event->set_assign($this->assignment);
    //         $event->trigger();
    //         return $updatestatus;
    //     } else {
    //
    //         $onlinetextsubmission = new stdClass();
    //         $onlinetextsubmission->onlinetext = $data->onlinetext;
    //         $onlinetextsubmission->onlineformat = $data->onlinetext_editor['format'];
    //
    //         $onlinetextsubmission->submission = $submission->id;
    //         $onlinetextsubmission->assignment = $this->assignment->get_instance()->id;
    //         $onlinetextsubmission->id = $DB->insert_record('assignsubmission_onlinetext', $onlinetextsubmission);
    //         $params['objectid'] = $onlinetextsubmission->id;
    //         $event = \assignsubmission_onlinetext\event\submission_created::create($params);
    //         $event->set_assign($this->assignment);
    //         $event->trigger();
    //         return $onlinetextsubmission->id > 0;
    //     }
    // }
}
