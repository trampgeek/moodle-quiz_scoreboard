# moodle-quiz_scoreboard

A dynamic scoreboard-like view of a quiz.

This module is a modified version of the Live Report plugin, compatible for Moodle 3.2+.

This quiz report module allows teachers to see, in real time, the progress of a
live quiz, such as might be seen in a programming contest. It shows which students
have attempted which questions. Successful attempts are coloured gree, unsuccessful
attempts are coloured red.

As students change their answers or submit more answers, the spreadsheet is
refreshed.

The top row in this spreadsheet/table has the names of the questions in the quiz.
The teacher can click on any of these question names to obtain, in a new tab, an
overview of that question.

The spreadsheet is dynamic but each overview window is not dynamic.

For multichoice, truefalse, and calculatedmulti question types, a histogram is
displayed in the overview window.

For all other question types, the response from each student is given in one
line on the page. The teacher can choose to show or hide the student's name
associated with each response.

To install this module, place the scoreboard directory as a sub-directory in the
<your moodle site>/mod/quiz/report/ directory, creating the
<your moodle site>/mod/quiz/report/scoreboard/ directory.

After installing this quiz report module, teachers can click on the "Scoreboard"
option in the "Report" drop-down menu to access this scoreboard.
