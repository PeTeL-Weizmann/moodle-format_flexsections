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
 * Event observer.
 *
 * @package    format_flexsections
 * @copyright  2023 Devlion <info@devlion.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_flexsections;

defined('MOODLE_INTERNAL') || die();

/**
 * Events observer.
 *
 * Stores all actions about modules viewed in block_recentlyaccesseditems table.
 *
 * @package    block_recentlyaccesseditems
 * @copyright  2018 Victor Deniz <victor@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Register items views in block_recentlyaccesseditems table.
     *
     * When the item is view for the first time, a new record is created. If the item was viewed before, the time is
     * updated.
     *
     * @param \core\event\base $event
     */
    public static function course_module_viewed(\core\event\course_module_viewed $event) {
        global $DB;

        if (!isloggedin() or \core\session\manager::is_loggedinas() or isguestuser()) {
            // No access tracking.
            return;
        }

        $sql = "
            SELECT *
            FROM {flexsections_lastaccess}
            WHERE cmid > 0 AND userid = ?   
            ORDER BY `timeaccess` DESC
        ";

        $records = $DB->get_records_sql($sql, ['userid' => $event->userid]);

        foreach ($records as $record) {
            if (($record->userid == $event->userid) && ($record->cmid == $event->contextinstanceid)) {
                $conditions = [
                        'userid' => $event->userid,
                        'cmid' => $event->contextinstanceid
                ];
                $DB->set_field('flexsections_lastaccess', 'timeaccess', $event->timecreated, $conditions);
                return;
            }
        }

        if (count($records) >= 9) {
            $conditions = [
                    'id' => end($records)->id,
            ];
            $DB->delete_records('flexsections_lastaccess', $conditions);
        }

        $eventdata = new \stdClass();

        $eventdata->cmid = $event->contextinstanceid;
        $eventdata->timeaccess = $event->timecreated;
        $eventdata->courseid = $event->courseid;
        $eventdata->userid = $event->userid;

        $DB->insert_record('flexsections_lastaccess', $eventdata);
    }

    /**
     * Remove record when course module is deleted.
     *
     * @param \core\event\base $event
     */
    public static function course_module_deleted(\core\event\course_module_deleted $event) {
        global $DB;

        $DB->delete_records('flexsections_lastaccess', array('cmid' => $event->contextinstanceid));
    }

    /**
     * Course viewed.
     *
     * @param \core\event\base $event
     */
    public static function course_viewed(\core\event\course_viewed $event) {
        global $DB;

        // $event->other['coursesectionnumber']

        //echo '<pre>';print_r($event->contextinstanceid);exit;


        if (!isloggedin() or \core\session\manager::is_loggedinas() or isguestuser()) {
            // No access tracking.
            return;
        }

        if (!isset($event->other['coursesectionnumber'])) {
            return;
        }

        $section = $DB->get_record('course_sections', ['course' => $event->contextinstanceid, 'section' => $event->other['coursesectionnumber']]);

        $sql = "
            SELECT *
            FROM {flexsections_lastaccess}
            WHERE sectionid > 0 AND userid = ?   
            ORDER BY `timeaccess` DESC
        ";

        $records = $DB->get_records_sql($sql, ['userid' => $event->userid]);

        foreach ($records as $record) {
            if (($record->userid == $event->userid) && ($record->sectionid == $section->id)) {
                $conditions = [
                        'userid' => $event->userid,
                        'sectionid' => $section->id
                ];
                $DB->set_field('flexsections_lastaccess', 'timeaccess', $event->timecreated, $conditions);
                return;
            }
        }

        if (count($records) >= 9) {
            $conditions = [
                    'id' => end($records)->id,
            ];
            $DB->delete_records('flexsections_lastaccess', $conditions);
        }

        $eventdata = new \stdClass();

        $eventdata->sectionid = $section->id;
        $eventdata->timeaccess = $event->timecreated;
        $eventdata->courseid = $event->courseid;
        $eventdata->userid = $event->userid;

        $DB->insert_record('flexsections_lastaccess', $eventdata);
    }

    /**
     * Remove record when course module is deleted.
     *
     * @param \core\event\base $event
     */
    public static function course_deleted(\core\event\course_deleted $event) {
        global $DB;

        $DB->delete_records('flexsections_lastaccess', array('courseid' => $event->objectid));
    }
}