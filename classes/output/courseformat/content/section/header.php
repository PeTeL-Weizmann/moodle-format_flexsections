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

namespace format_flexsections\output\courseformat\content\section;

use coding_exception;
use context_course;
use core_geopattern;
use moodle_url;
use section_info;
use stored_file;

require_once $CFG->dirroot . "/course/format/flexsections/locallib.php";

/**
 * Contains the section header output class.
 *
 * @package   format_flexsections
 * @copyright 2022 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class header extends \core_courseformat\output\local\content\section\header {

    /**
     * In-process cache of section images
     *
     * @var stored_file[]
     */
    private static $images = [];

    /**
     * Template name
     *
     * @param \renderer_base $renderer
     * @return string
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'format_flexsections/local/content/section/header';
    }

    /**
     * Data exporter
     *
     * @param \renderer_base $output
     * @return \stdClass
     */
    public function export_for_template(\renderer_base $output): \stdClass {
        global $PAGE;
        $data = parent::export_for_template($output);
       // $this->section->coll
        if ($this->section->collapsed == FORMAT_FLEXSECTIONS_COLLAPSED && !$PAGE->user_is_editing()) {
            // Do not display the collapse/expand caret for sections that are meant to be shown on a separate page.
            $data->headerdisplaymultipage = true;
            if ($this->format->get_viewed_section() != $this->section->section) {
                // If the section is displayed as a link and we are not on this section's page, display it as a link.
                $data->title = $output->section_title($this->section, $this->format->get_course());
            }
        }

        $data->url = course_get_url(
           $this->section->course,
           $this->section,
        );

        // Try and fetch the image.
        $image = $this->get_section_image($this->section);
        if (!is_null($image)) {
            $data->image = moodle_url::make_pluginfile_url(
                $image->get_contextid(),
                $image->get_component(),
                $image->get_filearea(),
                $image->get_itemid(),
                $image->get_filepath(),
                $image->get_filename(),
                false
            )->out(false);

            $data->imggenerated = false;
        } else {
            $pattern = new core_geopattern();
            $pattern->setColor($this->get_course_colour());
            $pattern->patternbyid($this->section->id);
            $data->image = $pattern->datauri();

            $data->imggenerated = true;
        }

        $data->headerdisplaymultipage = !empty($data->headerdisplaymultipage);

        $data->recentlyviewed = format_flexsections_recently_viewed_section($this->section->id);

        return $data;
    }
    /**
     * Generates a semi-random colour based on the course's ID
     *
     * @see \block_myoverview\output\courses_view::coursecolor()
     * @return string
     */
    public function get_course_colour(): string {
        // The colour palette is hardcoded for now. It would make sense to combine it with theme settings.
        $basecolours = [
            '#81ecec', '#74b9ff', '#a29bfe', '#dfe6e9', '#00b894',
            '#0984e3', '#b2bec3', '#fdcb6e', '#fd79a8', '#6c5ce7'
        ];

        return $basecolours[$this->format->get_course()->id % 10];
    }

    /**
     * Fetch all the section images for the current course
     *
     * @return stored_file[] Array of image files
     */
    public function get_section_images(): array {

        $course = $this->format->get_course();

        if (!array_key_exists($course->id, self::$images)) {
            $context = context_course::instance($course->id);
            $filestorage = get_file_storage();

            try {
                $files = $filestorage->get_area_files($context->id,
                    'format_flexsections',
                    FORMAT_FLEXSECTIONS_FILEAREA_IMAGE,
                    false,
                    'itemid, filepath, filename',
                    false
                );
            } catch (coding_exception $e) {
                return [];
            }

            self::$images[$course->id] = [];

            foreach ($files as $file) {
                self::$images[$course->id][$file->get_itemid()] = $file;
            }
        }

        return self::$images[$course->id];
    }

    /**
     * Fetch the image file for a given section
     *
     * @param section_info $section
     * @return stored_file|null
     */
    public function get_section_image(section_info $section): ?stored_file {
        $images = $this->get_section_images();

        if (array_key_exists($section->id, $images)) {
            return $images[$section->id];
        }

        return null;
    }
}
