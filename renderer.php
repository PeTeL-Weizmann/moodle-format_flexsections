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
 *
 *
 * @package    format_flexsections
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/format/renderer.php');

/**
 * Basic renderer for topics format.
 *
 * @copyright 2012 Marina Glancy
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_flexsections_renderer extends plugin_renderer_base {

    /**
     * Renders course header/footer
     *
     * @param renderable $obj
     * @return string
     */
    public function render_format_flexsections_courseobj($obj) {
        return html_writer::tag('div', "<b>{$obj->text}</b>",
                array('style' => 'background: #'.$obj->background.'; border: 1px solid black; text-align: center; padding: 5px;'));
    }

    /**
     * Generate the section title (with link if section is collapsed)
     *
     * @param int|section_info $section
     * @param stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title($section, $course, $supresslink = false) {
        $title = get_section_name($course, $section);
        if (!$supresslink) {
            $url = course_get_url($course, $section, array('navigation' => true));
            if ($url) {
                $title = html_writer::link($url, $title);
            }
        }
        return $title;
    }

    /**
     * Generate html for a section summary text
     *
     * @param stdClass $section The course_section entry from DB
     * @return string HTML to output.
     */
    protected function format_summary_text($section) {
        $context = context_course::instance($section->course);
        $summarytext = file_rewrite_pluginfile_urls($section->summary, 'pluginfile.php',
            $context->id, 'course', 'section', $section->id);

        $options = new stdClass();
        $options->noclean = true;
        $options->overflowdiv = true;
        return format_text($summarytext, $section->summaryformat, $options);
    }

    protected function display_new_section($course, $parentsection, $sr) {
        $url = new moodle_url(course_get_url($course, null, array('sr' => $sr)));
        $url->param('addchildsection', $parentsection);
        $url->param('sr', $sr);
        echo html_writer::start_tag('div', array('class' => 'mdl-right addsection'));
        echo html_writer::link($url, $parentsection?'Add subsection':'Add section'); // TODO
        echo html_writer::end_tag('div');
    }

    public function display_section($course, $sectionnum, $sr, $level = 0) {
        global $PAGE;
        if (empty($sectionnum)) {
            $sectionnum = 0;
        }
        $section = get_fast_modinfo($course)->get_section_info($sectionnum);
        if ($level === 0) {
            echo html_writer::start_tag('ul', array('class' => 'flexsections'));
        }
        echo html_writer::start_tag('li',
                array('class' => "section section-$sectionnum section-level-$level",
                    'id' => 'section-'.$sectionnum));
        // display controls
        if ($PAGE->user_is_editing() && $sectionnum) {
            echo html_writer::start_tag('div', array('class' => 'controls'));
            $editurl = new moodle_url('/course/editsection.php', array('id' => $section->id, 'sr' => $sr));
            echo html_writer::link($editurl, 'Edit'); // TODO
            $deleteurl = new moodle_url('/course/view.php', array('id' => $course->id, 'deletesection' => $section->section, 'sr' => $sr));
            echo ' '.html_writer::link($deleteurl, 'Delete'); // TODO
            echo html_writer::end_tag('div'); // .controls
        }
        echo html_writer::start_tag('div', array('class' => "content"));
        // display section name
        if ($sectionnum && ($title = $this->section_title($sectionnum, $course, $level == 0))) {
            echo html_writer::tag('h3', $title, array('class' => 'sectionname'));
        }
        // display section description (if needed)
        if ($summary = $this->format_summary_text($section)) {
            echo html_writer::tag('div', $summary, array('class' => 'summary'));
        }
        if ($section->collapsed == FORMAT_FLEXSECTIONS_EXPANDED || !$level) {
            // display resources and activities
            print_section($course, $section, null, null, true, "100%", false, $sr);
            if ($PAGE->user_is_editing()) {
                print_section_add_menus($course, $sectionnum, null, false, false, $sr);
            }
            // display subsections
            $sections = get_fast_modinfo($course)->get_section_info_all();
            $children = array();
            foreach ($sections as $num => $subsection) {
                if ($subsection->parent == $sectionnum && $num != $sectionnum) {
                    $children[] = $num;
                }
            }
            if (!empty($children)) {
                echo html_writer::start_tag('ul', array('class' => 'flexsections'));
                foreach ($children as $num) {
                    $this->display_section($course, $num, $sr, $level+1);
                }
                echo html_writer::end_tag('ul'); // .flexsections
            }
            if ($PAGE->user_is_editing()) {
                $this->display_new_section($course, $sectionnum, $sr);
            }
        }
        echo html_writer::end_tag('div'); // .content
        echo html_writer::end_tag('li'); // .section
        if ($level === 0) {
            echo html_writer::end_tag('ul'); // .flexsections
        }
    }
}
