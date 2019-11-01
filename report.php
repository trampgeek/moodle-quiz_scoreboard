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
 * Quiz scoreboard report class. Based on liveviewgrid report class.
 *
 * @package   quiz_scoreboard
 * @copyright 2014 Open University, 2019 University of Canterbury
 * @author    James Pratt <me@jamiep.org>, Richard Lobb <richard.lobb@canterbury.ac.nz>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Information about the question appearing in a particular quiz slot.
 */
class quiz_scoreboard_report_question {
    public $questionid = 0;
    public $name = null;
    public $qtype = null;
    public $maxmark = 0;

    public function __construct($questionid, $name, $qtype, $maxmark) {
        $this->questionid = $questionid;
        $this->name = $name;
        $this->qtype = $qtype;
        $this->maxmark = $maxmark;
    }
}

/**
 * The class quiz_scoreboard_report provides a dynamic view of the status of a
 * a live quiz for use as a Scoreboard in contests.
 *
 * It gives the most recent answers from all students.
 */
class quiz_scoreboard_report extends quiz_default_report {

    /** @var context_module context of this quiz.*/
    protected $context;

    /** @var quiz_scoreboard_table instance of table class used for main questions stats table. */
    protected $table;

    /** @var int either 1 or 0 in the URL get determined by the teacher to order by names (0) or total score (1). */
    protected $order = 1;
    /** @var int the id of the group that is being displayed. If the value is 0, results are from all students. */
    protected $group = 0;
    /** @var int The time of the last student response to a question. */
    protected $qmaxtime = 0;
    /** @var int The course module id for the quiz. */
    protected $id = 0;
    /** @var String The string that tells the code in quiz/report which sub-module to use. */
    protected $mode = '';
    /** @var int The context id for the quiz. */
    protected $quizcontextid = 0;
    /** @var Array The sorted array of the students who are attempting the quiz. */
    protected $users = array();
    /** @var Array The array of the students who have attempted the quiz. */
    protected $sofar = array();
    /** @var String The answer submitted to a question. */
    protected $answer = '';
    /** @var String The URL where the program can find out if a new response has been submitted and thus update the spreadsheet. */
    protected $graphicshashurl = '';

    /**
     * Display the report.
     * @param Obj $quiz The object from the quiz table.
     * @param Obj $cm The object from the course_module table.
     * @param Obj $course The object from the course table.
     * @return bool True if successful.
     */
    public function display($quiz, $cm, $course) {
        global $OUTPUT, $DB, $CFG;
        $order = optional_param('order', 1, PARAM_INT);
        $group = optional_param('group', 0, PARAM_INT);
        $id = optional_param('id', 0, PARAM_INT);
        $mode = optional_param('mode', '', PARAM_ALPHA);
        $question = array();
        $users = array();
        $sofar = array();
        $quizid = $quiz->id;
        $answer = '';
        $graphicshashurl = '';
        // Check permissions.
        $this->context = context_module::instance($cm->id);
        require_capability('mod/quiz:viewreports', $this->context);
        $this->print_header_and_tabs($cm, $course, $quiz, 'scoreboard');
        $context = $DB->get_record('context', array('instanceid' => $cm->id, 'contextlevel' => 70));
        $quizcontextid = $context->id;
        list($questions, $totalquizmark) = $this->get_all_questions($quizid);
        $stmark = $this->get_all_student_marks($quizid, $questions);
        $qmaxtime = $this->scoreboardquizmaxtime($quizcontextid);

        if ($order) {
            $urlget = "id=$id&mode=$mode&order=0&group=$group";
            echo "<a href='".$CFG->wwwroot."/mod/quiz/report.php?$urlget'>";
            echo get_string('ordername', 'quiz_scoreboard')."</a>\n";
        } else {
            $urlget = "id=$id&mode=$mode&order=1&group=$group";
            echo "<a href='".$CFG->wwwroot."/mod/quiz/report.php?$urlget'>";
            echo get_string('orderscore', 'quiz_scoreboard')."</a>\n";
        }
        echo "&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp";
        echo "<span id='mod-quiz-report-refresh' style='display:none'>".get_string('refreshpage', 'quiz_scoreboard').'</span>';

        // Find out if there may be groups. If so, allow the teacher to choose a group.
        if ($cm->groupmode) {
            echo get_string('whichgroups', 'quiz_scoreboard');
            echo "<select onchange='location=this.value;'>";
            $all = get_string('allresponses', 'quiz_scoreboard');
            $urlqparams = "id=$id&mode=$mode&order=$order&group=0";
            $url = $CFG->wwwroot."/mod/quiz/report.php?$urlqparams";
            $selected = $group == 0 ? ' selected' : '';
            echo "<option value='$url'$selected>$all</option>\n";
            $groups = $DB->get_records('groups', array('courseid' => $course->id));
            foreach ($groups as $grp) {
                $urlqparams = "id=$id&mode=$mode&order=$order&group=".$grp->id;
                $url = $CFG->wwwroot."/mod/quiz/report.php?$urlqparams";
                $selected = $group == $grp->id ? ' selected' : '';
                echo "<option value='$url'$selected>{$grp->name}</option>\n";
            }
            echo "</select>\n";
	}

        // Javascript to show 'Refresh Page' message when the page stops refreshing responses.
        echo "\n<script>";
        echo "\n  function modquizreportrefresh() {";
        echo "\n    document.getElementById('mod-quiz-scoreboard-refresh').setAttribute(\"style\", \"display:inline\");";
        echo "\n }";
        echo "\n</script>";

        $sofar = $this->scoreboard_who_sofar_gridview($quizid);

        // If a group is given, filter out all non-group members.
        $users = [];
        if ($group) {
            foreach ($sofar as $unuser) {
            // If only a group is desired, make sure this student is in the group.
                if ($DB->get_record('groups_members', array('groupid' => $group, 'userid' => $unuser))) {
                    $users[] = $unuser;
                }
            }
        } else {
            $users = $sofar;
        }

        echo "<table class='table table-bordered mod-quiz-report-scoreboard-table' style='width:auto' id='timemodified' name=$qmaxtime>\n";
        echo "<thead><tr>";

        echo "<th>Name</th>\n<th style='text-align:center'>Total</th><th>Percent</th>\n";

        $qnum = 1;
        foreach ($questions as $slot => $question) {
            echo "<th title='{$question->name}' style='word-wrap: break-word;text-align:center'>";
            echo "Q" . strval($qnum);
            $qnum += 1;
            echo "</th>\n";
        }
        echo "</tr>\n</thead>\n<tbody>";

        // Create the table.
        $rows = [];
        if (isset($users)) {
            foreach ($users as $user) {
                $row = "<tr>";
                $name = $this->scoreboard_find_student_gridview($user);
                $row .= "<td>" . $name . "</td>\n";
                $total_mark = 0;
                $markshtml = '';
                foreach ($questions as $slotnum => $question) {
                    $mark = 0;
                    if (isset($stmark[$user][$slotnum]) and $stmark[$user][$slotnum]) {
                        list($fraction, $outof) = $stmark[$user][$slotnum];
                        $mark = $fraction * $outof;
                        $class = 'mark';
                        if ($fraction > 0.9) {
                            $class .= ' correct';
                        } else if ($fraction >= 0.1) {
                            $class .= ' partial';
                        } else if ($fraction > 0.0) {
                            $class .= ' wrong';
                        }
                        $total_mark += $mark;
                    }
                    if ($mark) {
                        $markshtml .= "<td class='$class'>" . number_format($mark, 2) . "</td>";
                    } else {
                        $markshtml .= "<td></td>";
                    }
                }
                $row .= "<td class='mark total'>" . number_format($total_mark, 2) . "</td>";
                $percent = 100 * $total_mark / $totalquizmark;
                $row .= "<td class='mark percent'>" . number_format($percent, 1) . "</td>";
                $row .= "$markshtml</tr>\n";
                $rows[] = array($name, $total_mark, $row);
            }
        }

        // Sort and output the rows
        if ($order == 0) {
            uasort($rows, function ($rowa, $rowb) {
                return strcmp($rowa[0], $rowb[0]);  // Sort by name, ascending.
            });
        } else {
            uasort($rows, function ($rowa, $rowb) { // Sort by mark, descending
                if ($rowa[1] < $rowb[1]) {
                    return 1;
                } else if ($rowa[1] == $rowb[1]) {
                    return 0;
                } else {
                    return -1;
                }
            });
        }
        foreach ($rows as $row) {
            echo $row[2];
        }

        // Close off the table
        echo "\n</tbody>\n</table>";

        // Javascript to refresh the page if the contents of the table change.
        $graphicshashurl = $CFG->wwwroot."/mod/quiz/report/scoreboard/graphicshash.php?id=$id";
        // The number of seconds before checking to see if the answers have changed is the $refreshtime.
        $refreshtime = 10;
        $sessionconfig = $DB->get_record('config', array('name' => 'sessiontimeout'));
        $sessiontimeout = $sessionconfig->value;
        $maxrepeat = intval($sessiontimeout / $refreshtime);
        // The number of refreshes without a new answer is $numrefresh.
        $numrefresh = 0;
        $replacetime = $refreshtime * 1000;
        echo "\n\n<script type=\"text/javascript\">\nvar http = false;\nvar x=\"\";
                \n\nif(navigator.appName == \"Microsoft Internet Explorer\")
                {\nhttp = new ActiveXObject(\"Microsoft.XMLHTTP\");\n} else {\nhttp = new XMLHttpRequest();}";
            echo "\n var numrefresh = $numrefresh;";
            echo "\n var maxrepeat = $maxrepeat;";
            echo "\n\nfunction replace() { ";
            $t = '&t='.time();
            echo "\n numrefresh ++;";
            echo "\n x=document.getElementById('timemodified');";
            echo "\n myname = x.getAttribute('name');";
            echo "\n if(numrefresh < $maxrepeat) {";
            echo "\n    var t=setTimeout(\"replace()\",$replacetime);";
            echo "\n } else {";
            echo "\n modquizreportrefresh();";
            echo "\n }";
            echo "\nhttp.open(\"GET\", \"".$graphicshashurl.$t."\", true);";
            echo "\nhttp.onreadystatechange=function() {\nif(http.readyState == 4) {";
            echo "\n if(parseInt(http.responseText) != parseInt(myname)){";
            echo "\n    location.reload(true);";
            echo "\n}\n}\n}";
            echo "\n http.send(null);";
            echo "\n}\nreplace();";
        echo "\n</script>";

        return true;
    }

    /**
     * Return the array of marks $stmark[$userid][$slotnum] for a given quiz. Each
     * mark is either false (no mark can be determined) or a 2-element array of
     * fraction, max_possible_mark
     * @param $quizid The id of the quiz of interest
     * @param $questions A map from slot_number to a quiz_scoreboard_report_question object.
     * @return array A map from ($userid, $slotnum) to a 2-element array
     * (mark_fraction, mark_out_of) or false if no mark can be determined.
     */
    function get_all_student_marks($quizid, $questions) {
        global $DB;
        $quizattempts = $DB->get_records('quiz_attempts', array('quiz' => $quizid));
        $stmark = array();
        foreach ($quizattempts as $key => $quizattempt) {
            $usrid = $quizattempt->userid;
            $qubaid = $quizattempt->uniqueid;
            $dm = null;  // Question engine data mapper
            $qattempts = $DB->get_records('question_attempts', array('questionusageid' => $qubaid));
            foreach ($qattempts as $qattempt) {
                $slotnum = $qattempt->slot;
                if (!array_key_exists($slotnum, $questions)) {
                    continue;
                }
                $maxmark = $questions[$slotnum]->maxmark;

                // Firstly see if we already have graded questions, which will
                // be the case if we're running in Adaptive mode or (regardless)
                // for CodeRunner questions.
                $max_fraction = $DB->get_record('question_attempt_steps',
                        array('questionattemptid' => $qattempt->id), "max(fraction) as fract");
                if ($max_fraction && is_numeric($max_fraction->fract)) {
                    $stmark[$usrid][$slotnum] = array($max_fraction->fract, $maxmark);
                } else {
                    // No fraction available. Try the slow way.
                    if ($dm === null) {
                        // Get a question engine data mapper
                        $dm = question_engine::load_questions_usage_by_activity($qubaid);
                    }
                    $fraction = $this->grade_responses($dm, $qattempt);
                    $stmark[$usrid][$slotnum] = array($fraction, $maxmark);
                }
            }
        }
        return $stmark;
    }


    /**
     * Return the best mark (fraction 0 - 1) obtained by a particular user's
     * attempt on a particular question.
     * @param type $dm Question engine data mapper object
     * @param $qattempt The question attempt object (from the DB).
     * @return The best mark faction earned over all attempts, or false if no mark
     * can be determined.
     */
    private function grade_responses($dm, $qattempt) {
        global $DB;
        $bestmark = false;
        $qattemptsteps = $DB->get_records('question_attempt_steps',
                array('questionattemptid' => $qattempt->id));
        foreach ($qattemptsteps as $qattemptstep) {
            $answer = $DB->get_record('question_attempt_step_data',
                    array('attemptstepid' => $qattemptstep->id, 'name' => 'answer'));
            if ($answer) {
                $mark = $this->get_mark_fraction($dm, $qattempt->slot, array('answer' => $answer->value));
                if ($mark && ($bestmark === false || $mark > $bestmark)) {
                    $bestmark = $mark;
                }
            }
        }
        return $bestmark;
    }


   /**
     * Function to return the graded responses to the question.
     *
     * @param int $slot The slot number of the question in the quiz.
     * @param string $response The response that the student gave.
     * @return The fraction of the marks awarded to the student's response or
     * false if the response cannot be graded for any reason.
     */
    public function get_mark_fraction ($dm, $slot, $response) {
        $question = $dm->get_question($slot);
        if (method_exists($question, 'grade_response')
            && is_callable(array($question, 'grade_response'))) {
            $grade = $question->grade_response($response);
            if ($grade[0] == 0) {
                $grade[0] = 0.001; // Non-zero means student attempted the question
            }
            return $grade[0];
        }
        return false;
    }

    /**
     * Return the greatest time that a student responded to a given quiz.
     *
     * This is used to determine if the teacher view of the graph should be refreshed.
     * @param int $quizcontextid The ID for the context for this quiz.
     * @return int The integer for the greatest time.
     */
    private function scoreboardquizmaxtime($quizcontextid) {
        global $DB;
        $quiztime = $DB->get_record_sql("
            SELECT max(qa.timemodified)
            FROM {question_attempts} qa
            JOIN {question_usages} qu ON qu.id = qa.questionusageid
            WHERE qu.contextid = ?", array($quizcontextid));
        $arg = 'max(qa.timemodified)';
        $qmaxtime = intval($quiztime->$arg) + 1;
        return $qmaxtime;
    }

    /**
     * Function to return an array mapping from slotnum to question info.
     * @param int $quizid The id for this quiz.
     * @return array A two element array. First element is a map from slotnum
     * to a quiz_report_scoreboardquestion object. Second element is the
     * total mark possible over all questions.
     */
    private function get_all_questions($quizid) {
        global $DB;
        $questions = array();
        $totalquizmark = 0;
        $slots = $DB->get_records('quiz_slots', array('quizid' => $quizid));
        foreach ($slots as $slotid => $slot) {
            $qid = $slot->questionid;
            if (($question = $DB->get_record('question', array('id' => $qid))) &&
                    $question->qtype != 'description') {
                $questions[$slot->slot] = new quiz_scoreboard_report_question(
                   $qid, $question->name, $question->qtype, $slot->maxmark);
                $totalquizmark += $slot->maxmark;
            }
        }
        return array($questions, $totalquizmark);
    }


    /**
     * Return the number of users who have submitted answers to this quiz instance.
     *
     * @param int $quizid The ID for the quiz instance
     * @return array The userids for all the students submitting answers.
     */
    private function scoreboard_who_sofar_gridview($quizid) {
        global $DB;

        $records = $DB->get_records('quiz_attempts', array('quiz' => $quizid));

        foreach ($records as $records) {
            $userid[] = $records->userid;
        }
        if (isset($userid)) {
            return(array_unique($userid));
        } else {
            return(null);
        }
    }

    /**
     * Return the name of a student.
     *
     * @param int $userid The ID for the student.
     * @return string The last name, first name of the student.
     */
    protected function scoreboard_find_student_gridview($userid) {
         global $DB;
         $user = $DB->get_record('user', array('id' => $userid));
         $name = $user->firstname." ".$user->lastname;
         return($name);
    }

}
