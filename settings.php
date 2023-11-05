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
 * Administrative settings
 *
 * @package     format_flexsections
 * @copyright   2023 Devlion <info@devlion.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $ADMIN, $CFG;

require_once("$CFG->dirroot/course/format/flexsections/lib.php");

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'format_flexsections',
        get_string('settings:name', 'format_flexsections')
    );

    $settings->add(new admin_setting_configselect('format_flexsections/section0',
        get_string('form:course:section0', 'format_flexsections'),
        get_string('form:course:section0_help', 'format_flexsections'),
        FORMAT_FLEXSECTIONS_SECTION0_COURSEPAGE,
        [
            FORMAT_FLEXSECTIONS_SECTION0_COURSEPAGE => get_string('form:course:section0:coursepage', 'format_flexsections'),
            FORMAT_FLEXSECTIONS_SECTION0_ALLPAGES => get_string('form:course:section0:allpages', 'format_flexsections')
        ]
    ));

    $settings->add(new admin_setting_configselect('format_flexsections/showsummary',
        get_string('form:course:showsummary', 'format_flexsections'),
        '',
        FORMAT_FLEXSECTIONS_SHOWSUMMARY_SHOW,
        [
            FORMAT_FLEXSECTIONS_SHOWSUMMARY_SHOW => get_string('form:course:showsummary:show', 'format_flexsections'),
            FORMAT_FLEXSECTIONS_SHOWSUMMARY_HIDE => get_string('form:course:showsummary:hide', 'format_flexsections'),
            FORMAT_FLEXSECTIONS_SHOWSUMMARY_SHOWFULL => get_string('form:course:showsummary:showfull', 'format_flexsections')
        ]
    ));

    $settings->add(new admin_setting_configselect('format_flexsections/cardorientation',
        get_string('form:course:cardorientation', 'format_flexsections'),
        '',
        FORMAT_FLEXSECTIONS_ORIENTATION_VERTICAL,
        [
            FORMAT_FLEXSECTIONS_ORIENTATION_VERTICAL => get_string('form:course:cardorientation:vertical', 'format_flexsections'),
            FORMAT_FLEXSECTIONS_ORIENTATION_HORIZONTAL => get_string('form:course:cardorientation:horizontal', 'format_flexsections')
        ]
    ));

    $settings->add(new admin_setting_configselect('format_flexsections/showprogress',
        get_string('form:course:showprogress', 'format_flexsections'),
        '',
        FORMAT_FLEXSECTIONS_SHOWPROGRESS_SHOW,
        [
            FORMAT_FLEXSECTIONS_SHOWPROGRESS_SHOW => get_string('form:course:showprogress:show', 'format_flexsections'),
            FORMAT_FLEXSECTIONS_SHOWPROGRESS_HIDE => get_string('form:course:showprogress:hide', 'format_flexsections')
        ]
    ));

    $settings->add(new admin_setting_configselect('format_flexsections/progressformat',
        get_string('form:course:progressformat', 'format_flexsections'),
        '',
        FORMAT_FLEXSECTIONS_PROGRESSFORMAT_PERCENTAGE,
        [
            FORMAT_FLEXSECTIONS_PROGRESSFORMAT_COUNT => get_string('form:course:progressformat:count', 'format_flexsections'),
            FORMAT_FLEXSECTIONS_PROGRESSFORMAT_PERCENTAGE => get_string('form:course:progressformat:percentage', 'format_flexsections')
        ]
    ));

    $settings->add(new admin_setting_configselect('format_flexsections/progressmode',
        get_string('form:course:progressmode', 'format_flexsections'),
        '',
        FORMAT_FLEXSECTIONS_PROGRESSMODE_CIRCLE,
        [
            FORMAT_FLEXSECTIONS_PROGRESSMODE_CIRCLE => get_string('form:course:progressmode:circle', 'format_flexsections'),
            FORMAT_FLEXSECTIONS_PROGRESSMODE_LINE => get_string('form:course:progressmode:line', 'format_flexsections')
        ]
    ));

    $settings->add(new admin_setting_configselect('format_flexsections/sectionviewoption',
        get_string('form:course:sectionviewoption', 'format_flexsections'),
        '',
        FORMAT_FLEXSECTIONS_SECTIONVIEW_CARDS,
        [
            FORMAT_FLEXSECTIONS_SECTIONVIEW_CARDS => get_string('form:course:sectionview:cards', 'format_flexsections'),
            FORMAT_FLEXSECTIONS_SECTIONSVIEW_LIST => get_string('form:course:sectionview:list', 'format_flexsections')
        ]
    ));
}
