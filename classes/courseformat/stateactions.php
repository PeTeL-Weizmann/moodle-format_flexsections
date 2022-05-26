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

namespace format_flexsections\courseformat;

use context_course;
use core_courseformat\stateupdates;
use stdClass;

/**
 * class stateactions
 *
 * @package   format_flexsections
 * @copyright 2022 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stateactions extends  \core_courseformat\stateactions {
    /**
     * Moving a section
     *
     * @param \core_courseformat\stateupdates $updates
     * @param stdClass $course
     * @param array $ids
     * @param int|null $targetsectionid if positive number, move AFTER this section under the same parent
     *     if negative number, move TO the parent with id abs($targetsectionid) as the first child
     *     if 0, move to parent=0 as the first child
     *     (it's quite hacky but unfortunately we can only use one argument here so have to be creative)
     * @param int|null $targetcmid
     * @return void
     */
    public function section_move(\core_courseformat\stateupdates $updates, stdClass $course, array $ids,
                                 ?int $targetsectionid = null, ?int $targetcmid = null): void {
        $this->validate_sections($course, $ids, __FUNCTION__);

        $coursecontext = context_course::instance($course->id);
        require_capability('moodle/course:movesections', $coursecontext);

        /** @var \format_flexsections $format */
        $format = course_get_format($course);
        $modinfo = $format->get_modinfo();

        // Parent section and position.
        if ($targetsectionid > 0) {
            $this->validate_sections($course, [$targetsectionid], __FUNCTION__);
            $targetsection = $modinfo->get_section_info_by_id($targetsectionid, MUST_EXIST);
            $before = $this->find_next_section($modinfo, $targetsection);
            $parent = $targetsection->parent;
        } else if ($targetsectionid < 0) {
            $this->validate_sections($course, [-$targetsectionid], __FUNCTION__);
            $targetsection = $modinfo->get_section_info_by_id(-$targetsectionid, MUST_EXIST);
            $before = $this->get_first_child($modinfo, $targetsection->section);
            $parent = $modinfo->get_section_info_by_id(-$targetsectionid, MUST_EXIST);
        } else {
            $before = $this->get_first_child($modinfo, 0);
            $parent = 0;
        }

        // Move sections.
        $sections = $this->get_section_info($modinfo, $ids);
        foreach ($sections as $section) {
            if ($format->can_move_section_to($section, $parent, $before)) {
                $format->move_section($section, $parent, $before);
            }
        }

        // All course sections can be renamed because of the resort.
        $allsections = $modinfo->get_section_info_all();
        foreach ($allsections as $section) {
            $updates->add_section_put($section->id);
        }
        // The section order is at a course level.
        $updates->add_course_put();
    }

    protected function find_next_section(\course_modinfo $modinfo, \section_info $thissection): ?\section_info {
        $found = false;
        foreach ($modinfo->get_section_info_all() as $section) {
            if ($found) {
                return $section->parent == $thissection->parent ? $section : null;
            } else if ($section->id == $thissection->id) {
                $found = true;
            }
        }
        return null;
    }

    protected function get_first_child(\course_modinfo $modinfo, int $parent): ?\section_info {
        foreach ($modinfo->get_section_info_all() as $section) {
            if ($section->parent == $parent && $section->section) {
                return $section;
            }
        }
        return null;
    }

    /**
     * Merging a section with its parent
     *
     * @param \core_courseformat\stateupdates $updates
     * @param stdClass $course
     * @param array $ids
     * @param int|null $targetsectionid not used
     * @param int|null $targetcmid not used
     * @return void
     */
    public function mergeup(\core_courseformat\stateupdates $updates, stdClass $course, array $ids,
                                 ?int $targetsectionid = null, ?int $targetcmid = null): void {
        $this->validate_sections($course, $ids, __FUNCTION__);
        require_capability('moodle/course:update', context_course::instance($course->id));
        /** @var \format_flexsections $format */
        $format = course_get_format($course);
        $modinfo = $format->get_modinfo();
        foreach ($this->get_section_info($modinfo, $ids) as $section) {
            $format->mergeup_section($section);
        }

        // All course sections can be renamed because of the resort.
        $allsections = $format->get_modinfo()->get_section_info_all();
        foreach ($allsections as $section) {
            $updates->add_section_put($section->id);
        }
        // The section order is at a course level.
        $updates->add_course_put();
    }

    /**
     * Adding a subsection
     *
     * @param \core_courseformat\stateupdates $updates
     * @param stdClass $course
     * @param array $ids parent section id
     * @param int|null $targetsectionid not used
     * @param int|null $targetcmid not used
     * @return void
     */
    public function section_add_subsection(\core_courseformat\stateupdates $updates, stdClass $course, array $ids,
                            ?int $targetsectionid = null, ?int $targetcmid = null): void {
        $this->validate_sections($course, $ids, __FUNCTION__);
        require_capability('moodle/course:update', context_course::instance($course->id));
        /** @var \format_flexsections $format */
        $format = course_get_format($course);
        $modinfo = $format->get_modinfo();
        foreach ($this->get_section_info($modinfo, $ids) as $section) {
            $format->create_new_section($section);
        }

        // All course sections can be renamed because of the resort.
        $allsections = $format->get_modinfo()->get_section_info_all();
        foreach ($allsections as $section) {
            $updates->add_section_put($section->id);
        }
        // The section order is at a course level.
        $updates->add_course_put();
    }

    /**
     * Delete course sections.
     *
     * This method follows the same logic as editsection.php.
     *
     * @param stateupdates $updates the affected course elements track
     * @param stdClass $course the course object
     * @param int[] $ids section ids
     * @param int $targetsectionid not used
     * @param int $targetcmid not used
     */
    public function section_delete(
        stateupdates $updates,
        stdClass $course,
        array $ids = [],
        ?int $targetsectionid = null,
        ?int $targetcmid = null
    ): void {

        $coursecontext = context_course::instance($course->id);
        require_capability('moodle/course:update', $coursecontext);
        require_capability('moodle/course:movesections', $coursecontext);

        /** @var \format_flexsections $format */
        $format = course_get_format($course);
        $modinfo = $format->get_modinfo();

        foreach ($ids as $sectionid) {
            $section = $modinfo->get_section_info_by_id($sectionid, MUST_EXIST);
            $deleted = $format->delete_section_with_children($section);
            foreach ($deleted as $deletedid) {
                $updates->add_section_delete($sectionid);
            }
        }

        // Removing a section affects the full course structure.
        $this->course_state($updates, $course);
    }

    //section_switch_collapsed


    /**
     * Switch collapsed state (display as link/ display on the same page)
     *
     * @param \core_courseformat\stateupdates $updates
     * @param stdClass $course
     * @param array $ids section id
     * @param int|null $targetsectionid not used
     * @param int|null $targetcmid not used
     * @return void
     */
    public function section_switch_collapsed(\core_courseformat\stateupdates $updates, stdClass $course, array $ids,
                                           ?int $targetsectionid = null, ?int $targetcmid = null): void {
        $this->validate_sections($course, $ids, __FUNCTION__);
        require_capability('moodle/course:update', context_course::instance($course->id));
        /** @var \format_flexsections $format */
        $format = course_get_format($course);
        $modinfo = $format->get_modinfo();
        foreach ($this->get_section_info($modinfo, $ids) as $section) {
            if ($section->collapsed == FORMAT_FLEXSECTIONS_EXPANDED) {
                $newvalue = FORMAT_FLEXSECTIONS_COLLAPSED;
            } else {
                $newvalue = FORMAT_FLEXSECTIONS_EXPANDED;
            }
            $format->update_section_format_options(['id' => $section->id, 'collapsed' => $newvalue]);
            rebuild_course_cache($course->id, true);
        }

        // All course sections can be renamed because of the resort.
        $modinfo = get_fast_modinfo($course->id);
        $allsections = $modinfo->get_section_info_all();
        foreach ($allsections as $section) {
            $updates->add_section_put($section->id);
        }
        // The section order is at a course level.
        $updates->add_course_put();
    }

}