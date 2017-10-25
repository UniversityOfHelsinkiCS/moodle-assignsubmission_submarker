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
 * Contains the event tests for the plugin.
 *
 * @package   assignsubmission_submarker
 * @copyright 2017 University of Helsinki
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/tests/base_test.php');

class assignsubmission_submarker_events_testcase extends advanced_testcase {

    /** @var stdClass $user A user to submit an assignment. */
    protected $user;

    /** @var stdClass $course New course created to hold the assignment activity. */
    protected $course;

    /** @var stdClass $cm A context module object. */
    protected $cm;

    /** @var stdClass $context Context of the assignment activity. */
    protected $context;

    /** @var stdClass $assign The assignment object. */
    protected $assign;

    /** @var stdClass $submission Submission information. */
    protected $submission;

    /** @var stdClass $data General data for the assignment submission. */
    protected $data;

    /**
     * Setup all the various parts of an assignment activity including creating a submarker submission.
     */
    protected function setUp() {
        $this->user = $this->getDataGenerator()->create_user();
        $this->course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        $params['course'] = $this->course->id;
        $instance = $generator->create_instance($params);
        $this->cm = get_coursemodule_from_instance('assign', $instance->id);
        $this->context = context_module::instance($this->cm->id);
        $this->assign = new testable_assign($this->context, $this->cm, $this->course);

        $this->setUser($this->user->id);
        $this->submission = $this->assign->get_user_submission($this->user->id, true);
        $this->data = new stdClass();
        $this->data->exerchkbox1 = '1';
        $this->data->exerchkbox2 = '0';
        $this->data->exerchkbox3 = '1';
    }

    /**
     * Test that the assessable_uploaded event is fired when a submarker submission is saved.
     */
    public function test_assessable_uploaded() {
        $this->resetAfterTest();

        $plugin = $this->assign->get_submission_plugin_by_type('submarker');
        $sink = $this->redirectEvents();
        $plugin->save($this->submission, $this->data);
        $events = $sink->get_events();

        $this->assertCount(2, $events);
        $event = reset($events);
        $this->assertInstanceOf('\assignsubmission_submarker\event\assessable_uploaded', $event);
        $this->assertEquals($this->context->id, $event->contextid);
        $this->assertEquals($this->submission->id, $event->objectid);
        $this->assertEquals('101', $event->other['content']);
        $expected = new stdClass();
        $expected->modulename = 'assign';
        $expected->cmid = $this->cm->id;
        $expected->itemid = $this->submission->id;
        $expected->courseid = $this->course->id;
        $expected->userid = $this->user->id;
        $expected->content = '101';
        $this->assertEventLegacyData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test that the submission_created event is fired when an submarker submission is saved.
     */
    public function test_submission_created() {
        $this->resetAfterTest();

        $plugin = $this->assign->get_submission_plugin_by_type('submarker');
        $sink = $this->redirectEvents();
        $plugin->save($this->submission, $this->data);
        $events = $sink->get_events();

        $this->assertCount(2, $events);
        $event = $events[1];
        $this->assertInstanceOf('\assignsubmission_submarker\event\submission_created', $event);
        $this->assertEquals($this->context->id, $event->contextid);
        $this->assertEquals($this->course->id, $event->courseid);
        $this->assertEquals($this->submission->id, $event->other['submissionid']);
        $this->assertEquals($this->submission->attemptnumber, $event->other['submissionattempt']);
        $this->assertEquals($this->submission->status, $event->other['submissionstatus']);
        $this->assertEquals($this->submission->userid, $event->relateduserid);
    }

    /**
     * Test that the submission_updated event is fired when a submarker
     * submission is saved and an existing submission already exists.
     */
    public function test_submission_updated() {
        $this->resetAfterTest();

        $plugin = $this->assign->get_submission_plugin_by_type('submarker');
        $sink = $this->redirectEvents();
        // Create a submission.
        $plugin->save($this->submission, $this->data);
        // Update a submission.
        $plugin->save($this->submission, $this->data);
        $events = $sink->get_events();

        $this->assertCount(4, $events);
        $event = $events[3];
        $this->assertInstanceOf('\assignsubmission_submarker\event\submission_updated', $event);
        $this->assertEquals($this->context->id, $event->contextid);
        $this->assertEquals($this->course->id, $event->courseid);
        $this->assertEquals($this->submission->id, $event->other['submissionid']);
        $this->assertEquals($this->submission->attemptnumber, $event->other['submissionattempt']);
        $this->assertEquals($this->submission->status, $event->other['submissionstatus']);
        $this->assertEquals($this->submission->userid, $event->relateduserid);
    }
}
