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

define('FORMAT_FLEXSECTIONS_COLLAPSED', 1);
define('FORMAT_FLEXSECTIONS_EXPANDED', 0);
/**
 * Format Flexsections base class
 *
 * @package    format_flexsections
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_flexsections extends format_base {
    protected $viewcoursesection = null;

    /**
     * Returns true if this course format uses sections
     *
     * @return bool
     */
    public function uses_sections() {
        return true;
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * @param int|stdClass $section Section object from database or just field section.section
     * @return string Display name that the course format prefers, e.g. "Topic 2"
     */
    public function get_section_name($section) {
        $section = $this->get_section($section);
        if ((string)$section->name !== '') {
            return format_string($section->name, true, array('context' => context_course::instance($course->id)));
        } else if ($section->section == 0) {
            return get_string('section0name', 'format_flexsections');
        } else {
            return get_string('topic').' '.$section->section;
        }
    }

    /**
     * The URL to use for the specified course (with section)
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     *     if omitted the course view page is returned
     * @param array $options options for view URL. At the moment core uses:
     *     'navigation' (bool) if true and section has no separate page, the function returns null
     *     'sr' (int) used by multipage formats to specify to which section to return
     * @return null|moodle_url
     */
    public function get_view_url($section, $options = array()) {
        $url = new moodle_url('/course/view.php', array('id' => $this->courseid));

        if (is_object($section)) {
            $sectionno = $section->section;
        } else {
            $sectionno = $section;
        }
        if (array_key_exists('sr', $options)) {
            // return to the page for section with number $sr
            $url->param('section', $options['sr']);
            if ($sectionno) {
                $url->set_anchor('section-'.$sectionno);
            }
        } else if (!empty($options['navigation'])) {
            // this is called from navigation, create link only if this
            // section has separate page
            $section = $this->get_section($sectionno);
            if ($section->collapsed == FORMAT_FLEXSECTIONS_COLLAPSED) {
                $url->param('section', $sectionno);
            } else {
                return null;
            }
        } else if ($sectionno) {
            // check if this section has separate page
            $section = $this->get_section($sectionno);
            if ($section->collapsed == FORMAT_FLEXSECTIONS_COLLAPSED) {
                $url->param('section', $sectionno);
                return $url;
            }
            // find the parent (or grandparent) page that is displayed on separate page
            $url->set_anchor('section-'.$sectionno);
            while ($section->parent) {
                $section = $this->get_section($section->parent);
                if ($section->collapsed == FORMAT_FLEXSECTIONS_COLLAPSED) {
                    $url->param('section', $section->section);
                    return $url;
                }
            }
        }
        return $url;
    }

    /**
     * Returns the information about the ajax support in the given source format
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     * The property (array)testedbrowsers can be used as a parameter for {@link ajaxenabled()}.
     *
     * @return stdClass
     */
    public function supports_ajax() {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;
        $ajaxsupport->testedbrowsers = array('MSIE' => 6.0, 'Gecko' => 20061111, 'Safari' => 531, 'Chrome' => 6.0);
        return $ajaxsupport;
    }

    /**
     * Loads all of the course sections into the navigation
     *
     * This method is called from {@link global_navigation::load_course_sections()}
     *
     * When overwriting please note that navigationlib relies on using the correct values for
     * arguments $type and $key in {@link navigation_node::add()}
     *
     * Example of code creating a section node:
     * $sectionnode = $node->add($sectionname, $url, navigation_node::TYPE_SECTION, null, $section->id);
     * $sectionnode->nodetype = navigation_node::NODETYPE_BRANCH;
     *
     * Example of code creating an activity node:
     * $activitynode = $sectionnode->add($activityname, $action, navigation_node::TYPE_ACTIVITY, null, $activity->id, $icon);
     * if (global_navigation::module_extends_navigation($activity->modname)) {
     *     $activitynode->nodetype = navigation_node::NODETYPE_BRANCH;
     * } else {
     *     $activitynode->nodetype = navigation_node::NODETYPE_LEAF;
     * }
     *
     * Also note that if $navigation->includesectionnum is not null, the section with this relative
     * number needs is expected to be loaded
     *
     * @param global_navigation $navigation
     * @param navigation_node $node The course node within the navigation
     */
    public function extend_course_navigation($navigation, navigation_node $node) {
        global $PAGE;
        // if course format displays section on separate pages and we are on course/view.php page
        // and the section parameter is specified, make sure this section is expanded in
        // navigation
        if ($navigation->includesectionnum === false && $this->viewcoursesection) {
            $navigation->includesectionnum = $this->viewcoursesection;
        }

        $modinfo = get_fast_modinfo($this->courseid);
        if (!empty($modinfo->sections[0])) {
            foreach ($modinfo->sections[0] as $cmid) {
                $this->navigation_add_activity($node, $modinfo->get_cm($cmid));
            }
        }
        foreach ($modinfo->get_section_info_all() as $section) {
            if ($section->parent == 0 && $section->section != 0) {
                $this->navigation_add_section($navigation, $node, $section);
            }
        }
        return array();
    }

    /**
     * Checks if given section has another section among it's parents
     *
     * @param int|section_info $section child section
     * @param int $parentnum parent section number
     * @return boolean
     */
    protected function section_has_parent($section, $parentnum) {
        if (!$section) {
            return false;
        }
        $section = $this->get_section($section);
        if (!$section->section) {
            return false;
        } else if ($section->parent == $parentnum) {
            return true;
        } else if ($section->parent == 0) {
            return false;
        } else {
            return $this->section_has_parent($section->parent, $parentnum);
        }
    }

    /**
     * Adds a section to navigation node, loads modules and subsections if necessary
     *
     * @param global_navigation $navigation
     * @param navigation_node $node
     * @param section_info $section
     * @return null|navigation_node
     */
    protected function navigation_add_section($navigation, navigation_node $node, $section) {
        if (!$section->uservisible) {
            return null;
        }
        $sectionname = get_section_name($this->get_course(), $section);
        $url = course_get_url($this->get_course(), $section->section, array('navigation' => true));

        $sectionnode = $node->add($sectionname, $url, navigation_node::TYPE_SECTION, null, $section->id);
        $sectionnode->nodetype = navigation_node::NODETYPE_BRANCH;
        $sectionnode->hidden = (!$section->visible || !$section->available);
        if ($this->section_has_parent($navigation->includesectionnum, $section->section)
                || $navigation->includesectionnum == $section->section) {
            $modinfo = get_fast_modinfo($this->courseid);
            if (!empty($modinfo->sections[$section->section])) {
                foreach ($modinfo->sections[$section->section] as $cmid) {
                    $this->navigation_add_activity($sectionnode, $modinfo->get_cm($cmid));
                }
            }
            foreach ($modinfo->get_section_info_all() as $subsection) {
                if ($subsection->parent == $section->section && $subsection->section != 0) {
                    $this->navigation_add_section($navigation, $sectionnode, $subsection);
                }
            }
        }
        return $sectionnode;
    }

    /**
     * Adds a course module to the navigation node
     *
     * @param navigation_node $node
     * @param cm_info $cm
     * @return null|navigation_node
     */
    protected function navigation_add_activity(navigation_node $node, $cm) {
        if (!$cm->uservisible) {
            return null;
        }
        $activityname = format_string($cm->name, true, array('context' => context_module::instance($cm->id)));
        $action = $cm->get_url();
        if ($cm->icon) {
            $icon = new pix_icon($cm->icon, $cm->modfullname, $cm->iconcomponent);
        } else {
            $icon = new pix_icon('icon', $cm->modfullname, $cm->modname);
        }
        $activitynode = $node->add($activityname, $action, navigation_node::TYPE_ACTIVITY, null, $cm->id, $icon);
        if (global_navigation::module_extends_navigation($cm->modname)) {
            $activitynode->nodetype = navigation_node::NODETYPE_BRANCH;
        } else {
            $activitynode->nodetype = navigation_node::NODETYPE_LEAF;
        }
        return $activitynode;
    }

    /**
     * Custom action after section has been moved in AJAX mode
     *
     * Used in course/rest.php
     *
     * @return array This will be passed in ajax respose
     */
    public function ajax_section_move() {
        global $PAGE;
        $titles = array();
        $modinfo = get_fast_modinfo($this->courseid);
        $renderer = $this->get_renderer($PAGE);
        if ($renderer && ($sections = $modinfo->get_section_info_all())) {
            foreach ($sections as $number => $section) {
                $titles[$number] = $renderer->section_title($section, $this->get_course());
            }
        }
        return array('sectiontitles' => $titles, 'action' => 'move');
    }

    /**
     * Returns the list of blocks to be automatically added for the newly created course
     *
     * @return array of default blocks, must contain two keys BLOCK_POS_LEFT and BLOCK_POS_RIGHT
     *     each of values is an array of block names (for left and right side columns)
     */
    public function get_default_blocks() {
        global $CFG;
        $format = array();
        $format['defaultblocks'] = ':search_forums,news_items,calendar_upcoming,recent_activity';
        return blocks_parse_default_blocks_list($format['defaultblocks']);
    }

    /**
     * Definitions of the additional options that this course format uses for course
     *
     * @param bool $foreditform
     * @return array of options
     */
    public function course_format_options($foreditform = false) {
        return array();
        static $courseformatoptions = false;
        if ($courseformatoptions === false) {
            $courseconfig = get_config('moodlecourse');
            $courseformatoptions = array(
                'numsections' => array(
                    'default' => $courseconfig->numsections,
                    'type' => PARAM_INT,
                ),
                'hiddensections' => array(
                    'default' => $courseconfig->hiddensections,
                    'type' => PARAM_INT,
                ),
                'coursedisplay' => array(
                    'default' => $courseconfig->coursedisplay,
                    'type' => PARAM_INT,
                ),
            );
        }
        if ($foreditform && !isset($courseformatoptions['coursedisplay']['label'])) {
            $courseconfig = get_config('moodlecourse');
            $sectionmenu = array();
            for ($i = 0; $i <= $courseconfig->maxsections; $i++) {
                $sectionmenu[$i] = "$i";
            }
            $courseformatoptionsedit = array(
                'numsections' => array(
                    'label' => new lang_string('numberweeks'),
                    'element_type' => 'select',
                    'element_attributes' => array($sectionmenu),
                ),
                'hiddensections' => array(
                    'label' => new lang_string('hiddensections'),
                    'help' => 'hiddensections',
                    'help_component' => 'moodle',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            0 => new lang_string('hiddensectionscollapsed'),
                            1 => new lang_string('hiddensectionsinvisible')
                        )
                    ),
                ),
                'coursedisplay' => array(
                    'label' => new lang_string('coursedisplay'),
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            COURSE_DISPLAY_SINGLEPAGE => new lang_string('coursedisplay_single'),
                            COURSE_DISPLAY_MULTIPAGE => new lang_string('coursedisplay_multi')
                        )
                    ),
                    'help' => 'coursedisplay',
                    'help_component' => 'moodle',
                )
            );
            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);
        }
        return $courseformatoptions;
    }

    /**
     * Updates format options for a course
     *
     * Legacy course formats may assume that course format options
     * ('coursedisplay', 'numsections' and 'hiddensections') are shared between formats.
     * Therefore we make sure to copy them from the previous format
     *
     * @param stdClass|array $data return value from {@link moodleform::get_data()} or array with data
     * @param stdClass $oldcourse if this function is called from {@link update_course()}
     *     this object contains information about the course before update
     * @return bool whether there were any changes to the options values
     */
    public function update_course_format_options($data, $oldcourse = null) {
        if ($oldcourse !== null) {
            $data = (array)$data;
            $oldcourse = (array)$oldcourse;
            // TODO
            foreach ($this->course_format_options() as $key => $unused) {
                if (array_key_exists($key, $oldcourse) && !array_key_exists($key, $data)) {
                    $data[$key] = $oldcourse[$key];
                }
            }
        }
        return $this->update_format_options($data);
    }

    /**
     * Definitions of the additional options that this course format uses for section
     *
     * See {@link format_base::course_format_options()} for return array definition.
     *
     * Additionally section format options may have property 'cache' set to true
     * if this option needs to be cached in {@link get_fast_modinfo()}. The 'cache' property
     * is recommended to be set only for fields used in {@link format_base::get_section_name()},
     * {@link format_base::extend_course_navigation()} and {@link format_base::get_view_url()}
     *
     * For better performance cached options are recommended to have 'cachedefault' property
     * Unlike 'default', 'cachedefault' should be static and not access get_config().
     *
     * Regardless of value of 'cache' all options are accessed in the code as
     * $sectioninfo->OPTIONNAME
     * where $sectioninfo is instance of section_info, returned by
     * get_fast_modinfo($course)->get_section_info($sectionnum)
     * or get_fast_modinfo($course)->get_section_info_all()
     *
     * All format options for particular section are returned by calling:
     * $this->get_format_options($section);
     *
     * @param bool $foreditform
     * @return array
     */
    public function section_format_options($foreditform = false) {
        return array(
            'parent' => array(
                'type' => PARAM_INT,
                'label' => '',
                'element_type' => 'hidden',
                'default' => 0,
                'cache' => true,
                'cachedefault' => 0,
            ),
            'collapsed' => array(
                'type' => PARAM_INT,
                'label' => 'Display content',
                'element_type' => 'select',
                'element_attributes' => array(
                    array(
                        FORMAT_FLEXSECTIONS_EXPANDED => 'Expanded',
                        FORMAT_FLEXSECTIONS_COLLAPSED => 'Collapsed'
                    )
                ),
                'default' => COURSE_DISPLAY_SINGLEPAGE,
            )
        );
    }

    /**
     * Create a new section under given parent
     *
     * @param int $parentnum section number of parent
     * @return int
     */
    public function create_new_section($parentnum = 0) {
        global $DB;
        $sections = get_fast_modinfo($this->courseid)->get_section_info_all();
        $sectionnums = array_keys($sections);
        $sectionnum = array_pop($sectionnums) + 1;
        course_create_sections_if_missing($this->courseid, $sectionnum);
        if ($parentnum) {
            $id = $DB->get_field('course_sections', 'id', array(
                'course' => $this->courseid, 'section' => $sectionnum));
            $this->update_section_format_options(
                    array('parent' => $parentnum, 'id' => $id));
        }
        return $sectionnum;
    }

    /**
     * Deletes a section
     *
     * Moves all activities and subsections to the parent section (section 0
     * can never be deleted)
     *
     * @param section_info $section
     */
    protected function delete_section($section) {
        global $DB;
        if (!$section->section) {
            // can not delete section 0
            return;
        }
        // move all modules and activities from this section to parent
        $modinfo = get_fast_modinfo($this->courseid);
        $parent = $modinfo->get_section_info($section->parent);
        if (!empty($modinfo->sections[$section->section])) {
            foreach ($modinfo->sections[$section->section] as $cmid) {
                moveto_module($modinfo->get_cm($cmid), $parent);
            }
        }
        foreach ($modinfo->get_section_info_all() as $subsection) {
            if ($subsection->parent == $section->section) {
                $this->update_section_format_options(
                        array('id' => $subsection->id, 'parent' => $parent->section));
            }
        }
        $params = array('courseid' => $this->courseid,
                    'sectionid' => $section->id,
                    'format' => $this->format);
        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('course_format_options', $params);
        $DB->delete_records('course_sections', array('id' => $section->id));
        $values = $DB->get_fieldset_sql('SELECT section FROM {course_sections}
            WHERE course = ? and section > ? ORDER BY section',
                array($this->courseid, $section->section));
        foreach ($values as $value) {
            $DB->execute("UPDATE {course_sections} SET section = section - 1
                WHERE course = ? and section = ?",
                    array($this->courseid, $value));
        }
        foreach ($modinfo->get_section_info_all() as $subsection) {
            if ($subsection->parent>$section->section) {
                $this->update_section_format_options(
                        array('id' => $subsection->id, 
                            'parent' => $subsection->parent-1));
            }
        }
        $transaction->allow_commit();
        if (!empty($values)) {
            rebuild_course_cache($this->courseid, true);
        }
    }

    /**
     * Allows course format to execute code on moodle_page::set_course()
     *
     * format_flexsections processes the attributes 'addchildsection' and
     * 'section' in the view course URL
     *
     * @param moodle_page $page instance of page calling set_course
     */
    public function page_set_course(moodle_page $page) {
        global $PAGE;
        if ($PAGE != $page) {
            return;
        }
        if ($page->has_set_url() &&
                $page->url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE)) {
            // if requested, create new section and redirect to course view page
            $addchildsection = optional_param('addchildsection', null, PARAM_INT);
            if ($addchildsection !== null) {
                $sectionnum = $this->create_new_section($addchildsection);
                $sr = optional_param('sr', null, PARAM_INT);
                $url = course_get_url($this->courseid, $sectionnum, array('sr' => $sr));
                redirect($url);
            }
            // if requested, delete the section
            $deletesection = optional_param('deletesection', null, PARAM_INT);
            $sr = optional_param('sr', null, PARAM_INT);
            if ($deletesection) {
                $section = $this->get_section($deletesection, MUST_EXIST);
                $url = course_get_url($this->courseid, $section->parent, array('sr' => $sr));
                $this->delete_section($section);
                redirect($url);
            }
            // save 'section' attribute is specified in query string
            $selectedsection = optional_param('section', null, PARAM_INT);
            if ($selectedsection !== null && (!defined('AJAX_SCRIPT') || AJAX_SCRIPT == '0')) {
                $this->viewcoursesection = $selectedsection;
            }
        }
    }

    public function course_header() {
        return new format_flexsections_courseobj('This is the course header', 'DDFFFF');
    }

    public function course_footer() {
        return new format_flexsections_courseobj('This is the course footer', 'DDFFFF');
    }

    public function course_content_header() {
        return new format_flexsections_courseobj('This is the course content header', 'DDDDDD');
    }

    public function course_content_footer() {
        return new format_flexsections_courseobj('This is the course content footer', 'DDDDDD');
    }
}

/**
 * Class storing information to be displayed in course header/footer
 * 
 * @package    format_flexsections
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_flexsections_courseobj implements renderable {
    public $background;
    public $text;
    public function __construct($text, $background) {
        $this->text = $text;
        $this->background = $background;
    }
}
