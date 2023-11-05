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

namespace format_flexsections\output\courseformat\content;

use completion_info;
use stdClass;

/**
 * Contains the section controls output class.
 *
 * @package   format_flexsections
 * @copyright 2022 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section extends \core_courseformat\output\local\content\section {

    /** @var \format_flexsections the course format */
    protected $format;

    /** @var int subsection level */
    protected $level = 1;

    /**
     * Template name
     *
     * @param \renderer_base $renderer
     * @return string
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'format_flexsections/local/content/section';
    }

    /**
     * Data exporter
     *
     * @param \renderer_base $output
     * @param bool $lazyload
     * @return stdClass
     */
    public function export_for_template(\renderer_base $output, bool $ajax = false): stdClass {
        global $USER, $PAGE;

        $format = $this->format;
        $course = $format->get_course();
        $section = $this->section;

        $summary = new $this->summaryclass($format, $section);

        // Default action.
        $lazyload = false;
        $cmload = true;

        if ($ajax === false) {
            if ($section->section == '0') {
                $lazyload = false;
                $cmload = true;
            } else {
                $lazyload = true;
                $cmload = false;
            }
        }

        if ($ajax === true) {
            if ($section->section == '0') {
                $lazyload = false;
                $cmload = false;
            } else {
                $lazyload = true;
                $cmload = true;
            }
        }

        $data = (object)[
            'num' => $section->section ?? '0',
            'id' => $section->id,
            'sectionreturnid' => $format->get_section_number(),
            'insertafter' => false,
            'summary' => $summary->export_for_template($output),
            'highlightedlabel' => $format->get_section_highlighted_name(),
            'sitehome' => $course->id == SITEID,
            'editing' => $PAGE->user_is_editing(),
            'lazyload' => $lazyload
        ];
        $haspartials = [];
        $haspartials['header'] = $this->add_header_data($data, $output);

        $haspartials['availability'] = $this->add_availability_data($data, $output);
        $haspartials['visibility'] = $this->add_visibility_data($data, $output);
        $haspartials['editor'] = $this->add_editor_data($data, $output);
        $haspartials['header'] = $this->add_header_data($data, $output);

        if ($cmload) {
            $haspartials['cm'] = $this->add_cm_data($data, $output);
        }
        $this->add_format_data($data, $haspartials, $output);

        // For sections that are displayed as a link do not print list of cms or controls.
        $showaslink = $this->section->collapsed == FORMAT_FLEXSECTIONS_COLLAPSED
            && $this->format->get_viewed_section() != $this->section->section;

        // TODO update $showaslink logic !!!!!!!!! its better to remove it.
        $data->showaslink = false;//$showaslink;
        if ($showaslink) {
          //  $data->cmlist = [];
          //  $data->cmcontrols = '';
        }

        // Add subsections.
       //if (!$showaslink) {
            $data->subsections = $this->section->section ? $this->get_subsections($output) : [];
            $data->level = $this->level;
        //}

        if (!$this->section->section || $this->section->section == $this->format->get_viewed_section()) {
            $data->contentcollapsed = false;
            $data->collapsemenu = true;
        } else {
            $data->collapsemenu = false;
        }
        // TODO do we really need collapse button ?
        $data->collapsemenu = false;

        // Add completion data.
        $completion = $this->get_section_completion();
        $data->completion = $completion;
        $data->hascompletion = !empty($completion);
        $data->sectioncompletion = $completion;

        // Cards orientation
        if ($this->format->get_format_option('cardorientation') == FORMAT_FLEXSECTIONS_ORIENTATION_HORIZONTAL) {
            $data->classes[] = "card-horizontal";
        }

        // Shorten the card's summary text, if applicable.
        // TODO: read settings from course and not from system defaults
        if (!empty($data->summary->summarytext)) {
            if ($this->format->get_format_option('showsummary', $this->section) == FORMAT_FLEXSECTIONS_SHOWSUMMARY_SHOW) {
                if ($this->section->summaryformat == FORMAT_MARKDOWN) {
                    $data->summary->summarytext = markdown_to_html($data->summary->summarytext);
                }
                $data->summary->summarytext = shorten_text(
                    strip_tags(
                        $data->summary->summarytext,
                        '<b><i><u><strong><em><a>'
                    ),
                    300,
                    true,
                    '&hellip;');
                // PTL-9551 Show full summary, as it is, including HTML tags.
            } elseif ($this->format->get_format_option('showsummary', $this->section) == FORMAT_FLEXSECTIONS_SHOWSUMMARY_SHOWFULL) {
                if ($this->section->summaryformat == FORMAT_MARKDOWN) {
                    $data->summary->summarytext = markdown_to_html($data->summary->summarytext);
                    // And remove TAGs
                    $data->summary->summarytext = strip_tags(
                        $data->summary->summarytext,
                        '<b><i><u><strong><em><a>'
                    );
                }
            } else {
                $data->summary->summarytext = '';
            }
        }

        // Lastseen
        $lastseen = format_flexsections_lastseen($this->format->get_course()->id, $this->section->section, $USER->id);
        $data->lastseen = $lastseen;

        // Has share button.
        $data->hassharebutton = $PAGE->user_is_editing() ? true : false;

        return $data;
    }

    /**
     * Subsections (recursive)
     *
     * @param \renderer_base $output
     * @return array
     */
    protected function get_subsections(\renderer_base $output): array {
        $modinfo = $this->format->get_modinfo();
        $data = [];
        foreach ($modinfo->get_section_info_all() as $section) {
            if ($section->parent == $this->section->section) {
                if ($this->format->is_section_visible($section)) {
                    $instance = new static($this->format, $section);
                    $instance->level++;
                    $d = (array)($instance->export_for_template($output)) +
                        $this->default_section_properties();
                    $data[] = (object)$d;
                }
            }
        }
        return $data;
    }

    /**
     * Since we display sections nested the values from the parent can propagate in templates
     *
     * @return array
     */
    protected function default_section_properties(): array {
        return [
            'collapsemenu' => false, 'summary' => [],
            'insertafter' => false, 'numsections' => false,
            'availability' => [], 'restrictionlock' => false, 'hasavailability' => false,
            'isstealth' => false, 'ishidden' => false, 'notavailable' => false, 'hiddenfromstudents' => false,
            'controlmenu' => [], 'cmcontrols' => '',
            'singleheader' => [], 'header' => [],
            'cmsummary' => [], 'onlysummary' => false, 'cmlist' => [],
        ];
    }

    /**
     * Grabs the completion info for this section
     *
     * @return array
     */
    public function get_section_completion(): array {

        $coursecontext = \context_course::instance($this->format->get_course()->id);
        if (has_capability('moodle/course:viewhiddensections', $coursecontext)) {
            return [];
        }

        // Can't do anything if completion is disabled, or we're a guest user.
        if (isguestuser() || !$this->format->get_course()->enablecompletion) {
            return [];
        }

        if($this->format->get_format_option('showprogress') == FORMAT_FLEXSECTIONS_SHOWPROGRESS_HIDE) {
            return [];
        }

        $completioninfo = new completion_info($this->format->get_course());
        $modinfo = $this->section->modinfo;

        if (!array_key_exists($this->section->section, $modinfo->sections)) {
            return [];
        }

        // List of course module IDs for this section.
        $currentsection = $this->section->section;
        $sectioncmids = [];
        if (!$currentsection) {
            foreach ($modinfo->sections as $arr) {
                $sectioncmids = array_merge($sectioncmids, $arr);
            }
        } else {
            format_flexsections_get_sub_sections_cmids($sectioncmids, $this->section->id);
        }

        $total = $completed = 0;

        // Iterate through all the course module ID's that appear in this section.
        foreach (array_unique($sectioncmids) as $cmid) {
            $cminfo = $modinfo->cms[$cmid];

            // Don't include the course module if it's not visible, or about to be deleted.
            if (!$cminfo->uservisible || $cminfo->deletioninprogress) {
                continue;
            }

            // Don't include the course module if completion tracking is disabled.
            if ($completioninfo->is_enabled($cminfo) == COMPLETION_TRACKING_NONE) {
                continue;
            }

            $total++;

            // Finally, figure out if the user has completed this course module.
            $completiondata = $completioninfo->get_data($cminfo, true);

            if (in_array(
                $completiondata->completionstate,
                [ COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS ]
            )) {
                $completed++;
            }
        }

        // Don't show completion data if there's nothing completable in this section.
        if ($total == 0) {
            return [];
        }

        $iscomplete = $total == $completed;
        $progressformat = $this->format->get_format_option('progressformat');
        $progressmode = $this->format->get_format_option('progressmode');
        $percentage = round(($completed / $total) * 100);

        $modecircle = $progressmode == FORMAT_FLEXSECTIONS_PROGRESSMODE_CIRCLE;
        $modeline = $progressmode == FORMAT_FLEXSECTIONS_PROGRESSMODE_LINE;

        if($this->format->get_format_option('sectionviewoption') == FORMAT_FLEXSECTIONS_PROGRESSMODE_LINE){
            $modecircle = false;
            $modeline = true;
        }

        return [
            'total' => $total,
            'completed' => $completed,
            'percentage' => $percentage,
            'dashoffset' => 100 - $percentage,
            'iscomplete' => $iscomplete,
            'hasprogress' => $completed > 0,
            'showpercentage' => $progressformat == FORMAT_FLEXSECTIONS_PROGRESSFORMAT_PERCENTAGE,
            'modecircle' => $modecircle,
            'modeline' =>  $modeline,
            'showcount' => $progressformat == FORMAT_FLEXSECTIONS_PROGRESSFORMAT_COUNT,
        ];
    }
}
