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
 * Quiz scoreboard report version information.
 *
 * @package   quiz_scoreboard
 * @copyright 2019 Richard Lobb
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2019102800;
$plugin->requires  = 2016120500;
$plugin->cron      = 18000;
$plugin->component = 'quiz_scoreboard';
$plugin->maturity = MATURITY_ALPHA;
$plugin->release   = 'v0.1.0 (2019102800) for Moodle 3.2+';
