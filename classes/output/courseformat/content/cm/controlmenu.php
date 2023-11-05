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

namespace format_flexsections\output\courseformat\content\cm;

use action_menu;
use action_menu_link;
use action_menu_link_secondary;
use cm_info;
use core\output\named_templatable;
use core_courseformat\base as course_format;
use core_courseformat\output\local\courseformat_named_templatable;
use moodle_url;
use pix_icon;
use renderable;
use section_info;
use stdClass;

/**
 * Class to render a course module menu inside a course format.
 *
 * @package   format_flexsections
 * @copyright 2022 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class controlmenu extends \core_courseformat\output\local\content\cm\controlmenu {

    /** @var \format_flexsections the course format */
    protected $format;

    /**
     * Generate the edit control items of a course module.
     *
     * This method uses course_get_cm_edit_actions function to get the cm actions.
     * However, format plugins can override the method to add or remove elements
     * from the menu.
     *
     * @return array of edit control items
     */
    protected function cm_control_items() {
        global $COURSE, $DB;
        $actions = parent::cm_control_items();

        $baseurl = new moodle_url('/course/mod.php', array('sesskey' => sesskey()));
        $sr = $this->format->get_section_number();
        $mod = $this->mod;

        if ($sr !== null) {
            $baseurl->param('sr', $sr);
        }

        if (isset($actions['move'])) {
            $actions['move'] = new action_menu_link_secondary(
                new moodle_url($baseurl, ['sesskey' => sesskey(), 'copy' => $mod->id]),
                new pix_icon('i/dragdrop', '', 'moodle', ['class' => 'iconsmall']),
                get_string('move', 'moodle'),
                [
                    'class' => 'editing_movecm',
                    'data-action-flexsections' => 'moveCm',
                    'data-id' => $mod->id,
                ]
            );
        }


        // PTL-4771.
        list($categories, $courses, $activities) = \community_oer\main_oer::get_main_structure_elements();

        $str = get_strings(array('delete', 'move', 'moveright', 'moveleft',
                'editsettings', 'duplicate', 'modhide', 'makeavailable', 'makeunavailable', 'modshow'), 'moodle');

        if(in_array($mod->id, $activities)){
            $actions['duplicate'] = new action_menu_link_secondary(
                    new moodle_url('javascript:void(0)'),
                    new pix_icon('t/copy', '', 'moodle', array('class' => 'iconsmall')),
                    $str->duplicate,
                    array('class' => 'editing_duplicate', 'data-sharebtn' => 'true', 'data-handler' => 'openDialog', 'data-cmid' => $mod->id)
            );
        }else{
            $actions['duplicate'] = new action_menu_link_secondary(
                    new moodle_url($baseurl, array('duplicate' => $mod->id)),
                    new pix_icon('t/copy', '', 'moodle', array('class' => 'iconsmall')),
                    $str->duplicate,
                    array('class' => 'editing_duplicate', 'data-action' => 'duplicate', 'data-sectionreturn' => $sr)
            );
        }

        // Demo activity. PTL-10208.
        if (!empty(get_config('local_petel', 'enabledemo'))) {
            $enrol = $DB->get_record_select('enrol', 'courseid = ? AND enrol = ? AND password <> ""', [$COURSE->id, 'self']);
            $actions['demo'] = new action_menu_link_secondary(
                    new moodle_url('#'),
                    new pix_icon('t/edit', get_string('linktodemoactivity', 'local_petel'), 'moodle',
                            array('class' => 'iconsmall', 'title' => '')),
                    get_string('linktodemoactivity', 'local_petel'),
                    array('class' => 'demo_popup', 'data-key' => $enrol->password, 'data-lang' => current_language(),
                            'data-cmid' => $mod->id)
            );
        }

        // Editing_metadata.
        if (is_siteadmin() || can_edit_in_category($this->mod->get_course()->category)) {
            $actions['metadata'] = new action_menu_link_secondary(
                    new moodle_url('/local/metadata/index.php', array('id' => $mod->id, 'action' => 'moduledata'
                    ,'contextlevel' => $mod->context->contextlevel)),
                    new pix_icon('t/edit', get_string('metadatatitle', 'metadatacontext_module'), 'moodle',
                            array('class' => 'iconsmall', 'title' => '')),
                    get_string('metadatatitle', 'metadatacontext_module'),
                    array('class' => 'editing_metadata', 'data-action' => 'editing_metadata'
                    ,'data-sectionreturn' => $sr, 'target'=>'_blank')
            );
        }

        // Local_resourcenotif.
        $actions['local_resourcenotif'] = new action_menu_link_secondary(
                new moodle_url('/local/resourcenotif/resourcenotif.php', array('id' => $mod->id)),
                new pix_icon('t/email', get_string('notifications'), 'moodle',
                        array('class' => 'iconsmall', 'title' => '')),
                get_string('notifications'),
                array('class' => '', 'data-action' => 'local_resourcenotif' ,'data-sectionreturn' => $sr, 'target'=>'_blank')
        );

        // Activity remind.
        list($categories, $courses, $activities) = \community_oer\main_oer::get_main_structure_elements();

        $catid = \community_oer\main_oer::get_oer_category();
        if ($catid != null) {
            $context = \context_coursecat::instance($catid);

            if (in_array($COURSE->id, $courses) && has_capability('moodle/category:manage', $context)) {
                $actions['activityRemind'] = new action_menu_link_secondary(
                        new moodle_url('javascript:void(0)'),
                        new pix_icon('comment', get_string('activity_update_notification', 'community_oer'),
                                'community_oer', array('class' => 'iconsmall', 'title' => '')),
                        get_string('activity_update_notification', 'community_oer'),
                        array('class' => 'activityRemind', 'data-action' => 'activityRemind', 'data-handler' => 'activityRemind',
                                'data-cmid' => $mod->id)
                );
            }
        }

        return $actions;
    }
}
