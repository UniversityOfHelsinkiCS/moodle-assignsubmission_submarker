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
     * Get the saved text content from the editor
     *
     * @param string $name
     * @param int $submissionid
     * @return string
     */
    public function get_editor_text($name, $submissionid) {
        if ($name == 'onlinetext') {
            $onlinetextsubmission = $this->get_onlinetext_submission($submissionid);
            if ($onlinetextsubmission) {
                return $onlinetextsubmission->onlinetext;
            }
        }

        return '';
    }

    /**
     * Get the content format for the editor
     *
     * @param string $name
     * @param int $submissionid
     * @return int
     */
    public function get_editor_format($name, $submissionid) {
        if ($name == 'onlinetext') {
            $onlinetextsubmission = $this->get_onlinetext_submission($submissionid);
            if ($onlinetextsubmission) {
                return $onlinetextsubmission->onlineformat;
            }
        }

        return 0;
    }


     /**
      * Display onlinetext word count in the submission status table
      *
      * @param stdClass $submission
      * @param bool $showviewlink - If the summary has been truncated set this to true
      * @return string
      */
    public function view_summary(stdClass $submission, & $showviewlink) {
        global $CFG;

        $onlinetextsubmission = $this->get_onlinetext_submission($submission->id);
        // Always show the view link.
        $showviewlink = true;

        if ($onlinetextsubmission) {
            $text = $this->assignment->render_editor_content(ASSIGNSUBMISSION_ONLINETEXT_FILEAREA,
                                                             $onlinetextsubmission->submission,
                                                             $this->get_type(),
                                                             'onlinetext',
                                                             'assignsubmission_onlinetext');

            $shorttext = shorten_text($text, 140);
            $plagiarismlinks = '';

            if (!empty($CFG->enableplagiarism)) {
                require_once($CFG->libdir . '/plagiarismlib.php');

                $plagiarismlinks .= plagiarism_get_links(array('userid' => $submission->userid,
                    'content' => trim($onlinetextsubmission->onlinetext),
                    'cmid' => $this->assignment->get_course_module()->id,
                    'course' => $this->assignment->get_course()->id,
                    'assignment' => $submission->assignment));
            }
            if ($text != $shorttext) {
                $wordcount = get_string('numwords', 'assignsubmission_onlinetext', count_words($text));

                return $plagiarismlinks . $wordcount . $shorttext;
            } else {
                return $plagiarismlinks . $shorttext;
            }
        }
        return '';
    }

    /**
     * Produce a list of files suitable for export that represent this submission.
     *
     * @param stdClass $submission - For this is the submission data
     * @param stdClass $user - This is the user record for this submission
     * @return array - return an array of files indexed by filename
     */
    public function get_files(stdClass $submission, stdClass $user) {
        global $DB;

        $files = array();
        $onlinetextsubmission = $this->get_onlinetext_submission($submission->id);

        if ($onlinetextsubmission) {
            $finaltext = $this->assignment->download_rewrite_pluginfile_urls($onlinetextsubmission->onlinetext, $user, $this);
            $formattedtext = format_text($finaltext,
                                         $onlinetextsubmission->onlineformat,
                                         array('context'=>$this->assignment->get_context()));
            $head = '<head><meta charset="UTF-8"></head>';
            $submissioncontent = '<!DOCTYPE html><html>' . $head . '<body>'. $formattedtext . '</body></html>';

            $filename = get_string('onlinetextfilename', 'assignsubmission_onlinetext');
            $files[$filename] = array($submissioncontent);

            $fs = get_file_storage();

            $fsfiles = $fs->get_area_files($this->assignment->get_context()->id,
                                           'assignsubmission_onlinetext',
                                           ASSIGNSUBMISSION_ONLINETEXT_FILEAREA,
                                           $submission->id,
                                           'timemodified',
                                           false);

            foreach ($fsfiles as $file) {
                $files[$file->get_filename()] = $file;
            }
        }

        return $files;
    }

    /**
     * Display the saved text content from the editor in the view table
     *
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission) {
        global $CFG;
        $result = '';

        $onlinetextsubmission = $this->get_onlinetext_submission($submission->id);

        if ($onlinetextsubmission) {

            // Render for portfolio API.
            $result .= $this->assignment->render_editor_content(ASSIGNSUBMISSION_ONLINETEXT_FILEAREA,
                                                                $onlinetextsubmission->submission,
                                                                $this->get_type(),
                                                                'onlinetext',
                                                                'assignsubmission_onlinetext');

            $plagiarismlinks = '';

            if (!empty($CFG->enableplagiarism)) {
                require_once($CFG->libdir . '/plagiarismlib.php');

                $plagiarismlinks .= plagiarism_get_links(array('userid' => $submission->userid,
                    'content' => trim($onlinetextsubmission->onlinetext),
                    'cmid' => $this->assignment->get_course_module()->id,
                    'course' => $this->assignment->get_course()->id,
                    'assignment' => $submission->assignment));
            }
        }

        return $plagiarismlinks . $result;
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
}