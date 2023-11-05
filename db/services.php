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
 * Web service external functions and service definitions.
 *
 * @package    format_flexsections
 * @copyright  2019 Devlion.co
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

// We defined the web service functions to install.
$functions = array(
    'format_flexsections_change_courseimage' => array(
        'classname'   => 'format_flexsections_external',
        'methodname'  => 'change_courseimage',
        'classpath'   => 'course/format/flexsections/externallib.php',
        'description' => 'Change course image',
        'type'        => 'write',
        'ajax'        => true,
    ),
    'format_flexsections_change_sectionimage' => array(
        'classname'   => 'format_flexsections_external',
        'methodname'  => 'change_sectionimage',
        'classpath'   => 'course/format/flexsections/externallib.php',
        'description' => 'Change section image',
        'type'        => 'write',
        'ajax'        => true,
    ),
    'format_flexsections_get_activity_grade_status' => array(
        'classname' => 'format_flexsections_external',
        'methodname' => 'get_activity_grade_status',
        'classpath' => 'course/format/flexsections/externallib.php',
        'description' => 'Get activity grade status',
        'type' => 'read',
        'ajax' => true,
    ),
    'format_flexsections_get_section_status' => array(
        'classname' => 'format_flexsections_external',
        'methodname' => 'get_section_status',
        'classpath' => 'course/format/flexsections/externallib.php',
        'description' => 'Get section status',
        'type' => 'read',
        'ajax' => true,
    ),
    'format_flexsections_get_section_content' => array(
        'classname' => 'format_flexsections_external',
        'methodname' => 'get_section_content',
        'classpath' => 'course/format/flexsections/externallib.php',
        'description' => 'Get section content',
        'type' => 'read',
        'ajax' => true,
    ),
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
    'Flex AJAX' => array(
        'functions'       => array(
            'format_flexsections_change_courseimage',
            'format_flexsections_change_sectionimage',
            'format_flexsections_get_activity_grade_status',
            'format_flexsections_get_section_status',
        ),
        'restrictedusers' => 0,
        'enabled'         => 1,
        'shortname'       => 'flexajax',
    ),
);
