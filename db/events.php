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
 * Recently accessed items event observer.
 *
 * @package   format_flexsections
 * @category  event
 * @copyright 2023 Devlion <info@devlion.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = array (
    array(
            'eventname'   => '\core\event\course_module_viewed',
            'callback'    => 'format_flexsections\observer::course_module_viewed',
    ),
    array(
            'eventname'   => '\core\event\course_module_deleted',
            'callback'    => 'format_flexsections\observer::course_module_deleted'
    ),
    array(
            'eventname'   => '\core\event\course_viewed',
            'callback'    => 'format_flexsections\observer::course_viewed'
    ),
    array(
            'eventname'   => '\core\event\course_deleted',
            'callback'    => 'format_flexsections\observer::course_deleted'
    ),
);