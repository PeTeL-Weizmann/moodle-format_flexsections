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
 * Code to be executed after the plugin's database scheme has been installed is defined here.
 *
 * @package     format_flexsections
 * @category    upgrade
 * @copyright   2023 Devlion <info@devlion.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Custom code to be run on installing the plugin.
 */
function xmldb_format_flexsections_install() {

    global $DB;

    $dbman = $DB->get_manager();

    $table = new xmldb_table('flexsections_lastaccess');

    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    $table->add_field('sectionid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    $table->add_field('timeaccess', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

    $dbman->create_table($table);

    // Set indexes.
    $indexuserid = new xmldb_index('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));
    $dbman->add_index($table, $indexuserid);

    $indexcourseid = new xmldb_index('courseid', XMLDB_INDEX_NOTUNIQUE, array('courseid'));
    $dbman->add_index($table, $indexcourseid);

    $indexsectionid = new xmldb_index('sectionid', XMLDB_INDEX_NOTUNIQUE, array('sectionid'));
    $dbman->add_index($table, $indexsectionid);

    $indexcmid = new xmldb_index('cmid', XMLDB_INDEX_NOTUNIQUE, array('cmid'));
    $dbman->add_index($table, $indexcmid);

    return true;
}
