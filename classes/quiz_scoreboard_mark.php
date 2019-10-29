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
 * Quiz scoreboardg quiz_scoreboardgrid_mark class.
 *
 * @package   quiz_scoreboard
 * @copyright 2018 W. F. Junkin, Eckerd College, http://www.eckerd.edu,
 *            2019 Richard Lobb, University of Canterbury.
 * @author    William Junkin <junkinwf@eckerd.edu>, Richard Lobb <richard.lobb@canterbury.ac.nz>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This class returns the number of points for students answers.
 *
 * For questions that can be graded by the computer program, it returns the
 * product of thefraction associated with
 * the grade assigned to the answer the student has given.
 * A lot of this comes from the question/engine/ scripts.
 **/
class quiz_scoreboard_mark {
    /**
     * @var question_engine_data_mapper $dm
     */
    public $dm;

    /**
     * Create a new instance of this class. This can be called directly.
     *
     * @param int $qubaid The id of from the question_useages table for this student.
     */
    public function __construct($qubaid) {
        $this->dm = question_engine::load_questions_usage_by_activity($qubaid);
    }

    /**
     * Function to obtain the question from the slot value of the question.
     *
     * @param int $slot The id from the slot table of this question.
     * @return The question object from the row in the question table.
     */
    public function get_question($slot) {
        return $this->dm->get_question($slot);
    }
    /**
     * Function to return the graded responses to the question.
     *
     * @param int $slot The value of the id for this question in the slot table.
     * @param string $myresponse The response that the student gave.
     * @return A two element array containing the fraction of the marks
     * awarded to the student's answer and what the question was marked out of.
     * The product of the two is thus the actual mark. If no mark is possible
     * because the question does not have a callable 'grade_response' method,
     * the return value is false
     */
    public function get_mark ($slot, $myresponse) {
        $myquestion = $this->dm->get_question($slot);
        $marks = false;
        if (method_exists($myquestion, 'grade_response')
            && is_callable(array($myquestion, 'grade_response'))) {
            $grade = $myquestion->grade_response($myresponse);
            if ($grade[0] == 0) {
                $grade[0] = 0.001;// This is set so that the isset function returns a value of true.
            }
            $marks = array($grade[0], $myquestion->defaultmark);
        }
        return $marks;
    }
}

