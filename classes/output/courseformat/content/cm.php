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
 * Contains the default activity list from a section.
 *
 * @package   core_courseformat
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_flexsections\output\courseformat\content;

use cm_info;
use core\activity_dates;
use core\output\named_templatable;
use core_availability\info_module;
use core_completion\cm_completion_details;
use core_course\output\activity_information;
use core_courseformat\base as course_format;
use core_courseformat\output\local\courseformat_named_templatable;
use renderable;
use renderer_base;
use section_info;
use stdClass;

/**
 * Base class to render a course module inside a course format.
 *
 * @package   core_courseformat
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cm extends \core_courseformat\output\local\content\cm {

    /** @var course_format the course format */
    protected $format;

    /** @var section_info the section object */
    private $section;

    /** @var cm_info the course module instance */
    protected $mod;

    /** @var array optional display options */
    protected $displayoptions;

    /** @var string the activity name output class name */
    protected $cmnameclass;

    /** @var string the activity control menu class name */
    protected $controlmenuclass;

    /** @var string the activity availability class name */
    protected $availabilityclass;

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(renderer_base $output): stdClass {
        global $CFG;

        require_once $CFG->dirroot . "/course/format/flexsections/locallib.php";

        $data = parent::export_for_template($output);
        // JS parser compatability
        if (isset($data->url)){
            $data->url = $data->url->out();
        }

        // Has share button.
        $data->hassharebutton = true;
        $data->pluginname = get_string('pluginname', 'mod_' . $this->mod->modname);

        // Completion.
        if (format_flexsections_has_teacher_capability($this->mod->id) &&
            in_array($this->mod->modname, ['questionnaire', 'assign', 'quiz', 'hvp'])) {
            $data->activityinfo->hascompletion = false;
        }

        if (!format_flexsections_has_teacher_capability($this->mod->id) &&
            in_array($this->mod->modname, ['questionnaire', 'assign', 'quiz', 'hvp'])){

            $data->activityinfo->istrackeduser = false;
            $data->activityinfo->hascompletion = false;
        }

        return $data;
    }
}
