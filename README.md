# moodle-quiz_scoreboard

A dynamic scoreboard-like view of a quiz.

This module is a modified version of the Live Report plugin, compatible for Moodle 3.2+.

This quiz report module allows teachers to see, in real time, the progress of a
live quiz, such as might be seen in a programming contest. It shows which students
have attempted which questions. Successful attempts are coloured green, partially
successful attempts are very pale green, unsuccessful attempts are red. Current
totals are computed and by default the scoreboard is displayed sorted by total
mark, in descending order.

As students change their answers or submit more answers, the spreadsheet is
refreshed.

The plugin is recommended primarily for use with quizzes running in adaptive mode, since
these quizzes have questions marked on the fly, with marks already recorded
in the database. This makes for a much faster update of the display, which
can be painfully slow with larger classes otherwise.

If a quiz is not running in an adaptive mode, the plug-in does attempt to grade
submitted answers but this can be very slow. Also, the marks are not reliable
as re-submission penalties are not applied.

Regardless of the behaviour mode of the quiz, and other quiz settings,
the displayed mark for a given user and a given question is
the maximum achieved over all submissions to the given question
on the most-recent quiz attempt by that
student.

To install this module, place the scoreboard directory as a sub-directory in the
<your moodle site>/mod/quiz/report/ directory, creating the
<your moodle site>/mod/quiz/report/scoreboard/ directory.

After installing this quiz report module, teachers can click on the "Scoreboard"
option in the "Report" drop-down menu to access this scoreboard.
