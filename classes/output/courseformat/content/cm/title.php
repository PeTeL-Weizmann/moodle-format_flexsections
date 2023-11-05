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
 * Contains the default activity title.
 *
 * This class is usually rendered inside the cmname inplace editable.
 *
 * @package   core_courseformat
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_flexsections\output\courseformat\content\cm;

use cm_info;
use core\output\inplace_editable;
use core\output\named_templatable;
use core_courseformat\base as course_format;
use core_text;
use lang_string;
use renderable;
use section_info;
use stdClass;
use external_api;
use context_module;

/**
 * Base class to render a course module title inside a course format.
 *
 * @package   core_courseformat
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class title extends \core_courseformat\output\local\content\cm\title {

    /** @var course_format the course format */
    protected $format;

    /** @var section_info the section object */
    private $section;

    /** @var cm_info the course module instance */
    protected $mod;

    /** @var array optional display options */
    protected $displayoptions;

    /** @var editable if the title is editable */
    protected $editable;

    /** @var displaytemplate the default display template */
    protected $displaytemplate = 'format_flexsections/local/content/cm/title';


    /**
     * Get the name of the template to use for this templatable.
     *
     * @param \renderer_base $renderer The renderer requesting the template name
     * @return string
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'core/inplace_editable';
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): array {

        // Inplace editable uses pre-rendered elements and does not allow line beaks in the UI value.
        $this->displayvalue = str_replace("\n", "", $this->get_title_displayvalue());

        if (trim($this->displayvalue) == '') {
            $this->editable = false;
        }
        return parent::export_for_template($output);
    }

    /**
     * Return the title template data to be used inside the inplace editable.
     *
     */
    protected function get_title_displayvalue (): string {
        global $PAGE, $COURSE, $DB;

        // Inplace editable uses core renderer by default. However, course elements require
        // the format specific renderer.
        $courseoutput = $this->format->get_renderer($PAGE);

        $mod = $this->mod;

        $data = (object)[
            'url' => $mod->url,
            'instancename' => $mod->get_formatted_name(),
            'uservisible' => $mod->uservisible,
            'linkclasses' => $this->displayoptions['linkclasses'],
        ];

        // Display a tooltip with OER catalog item metadata
        $context = \context_course::instance($COURSE->id);
        if (has_capability('moodle/course:manageactivities', $context)) {

            $mid = \local_metadata\mcontext::module()->get($mod->id, 'ID');
            $mteacherremarks = \local_metadata\mcontext::module()->get($mod->id, 'teacherremarks');

            if (!empty($mid) && !empty($mteacherremarks)) {

                // Default date.
                $moddateadded = gmdate("d-m-Y", $mod->added);

                // Get last modified.
                $dbman = $DB->get_manager();
                if ($dbman->table_exists($mod->modname)) {
                    if ($obj = $DB->get_record($mod->modname, ['id' => $mod->instance])){
                        $moddateadded = gmdate("d-m-Y", $obj->timemodified);
                    }
                }

                $title = $mteacherremarks . '<br/> ID=' . $mid . '    ' . get_string("lastmodified") . ': ' . $moddateadded;
                $title = str_replace("'", '', $title);
                $title = str_replace('"', "'", $title);
                $position = 'top';
                if (strlen($title) > 600){
                    if (right_to_left() == 'rtl') {
                        $position = 'left';
                    }else{
                        $position = 'right';
                    }
                }

                $data->tooltip = $title;
                $data->position = $position;
            }
        }

        // File type after name, for alphabetic lists (screen reader).
        if (strpos(core_text::strtolower($data->instancename), core_text::strtolower($mod->modfullname)) === false) {
            $data->altname = get_accesshide(' ' . $mod->modfullname);
        }

        // Get on-click attribute value if specified and decode the onclick - it
        // has already been encoded for display (puke).
        $data->onclick = htmlspecialchars_decode($mod->onclick, ENT_QUOTES);

        return $courseoutput->render_from_template(
            $this->displaytemplate,
            $data
        );
    }
}
