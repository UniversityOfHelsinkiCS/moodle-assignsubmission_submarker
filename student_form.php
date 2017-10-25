<?php  

require_once($CFG->libdir.'/formslib.php');

class student_form extends moodleform {

    function definition() {
    }

    protected $_form;

    public function draw_checkbox_controller($mform, $groupid, $text) {
        $this->_form = $mform;
        $this->add_checkbox_controller($groupid, $text);
    }
}