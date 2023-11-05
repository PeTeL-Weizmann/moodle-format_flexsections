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
 * This file contains main class for Flexible sections course format.
 *
 * @package   format_flexsections
 * @copyright 2022 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot. '/course/format/lib.php');
require_once($CFG->dirroot . '/lib/formslib.php');

use core\output\inplace_editable;
use format_flexsections\forms\editcard_form;

define('FORMAT_FLEXSECTIONS_COLLAPSED', 1);
define('FORMAT_FLEXSECTIONS_EXPANDED', 0);
define('FORMAT_FLEXSECTIONS_USEDEFAULT', 0);
define('FORMAT_FLEXSECTIONS_FILEAREA_IMAGE', 'image');
define('FORMAT_FLEXSECTIONS_SHOWPROGRESS_SHOW', 1);
define('FORMAT_FLEXSECTIONS_SHOWPROGRESS_HIDE', 2);
define('FORMAT_FLEXSECTIONS_PROGRESSFORMAT_COUNT', 1);
define('FORMAT_FLEXSECTIONS_PROGRESSFORMAT_PERCENTAGE', 2);
define('FORMAT_FLEXSECTIONS_PROGRESSMODE_CIRCLE', 1);
define('FORMAT_FLEXSECTIONS_PROGRESSMODE_LINE', 2);
define('FORMAT_FLEXSECTIONS_HIDDENSECTION_COLLAPSED', 0);
define('FORMAT_FLEXSECTIONS_HIDDENSECTION_VISIBLE', 1);
define('FORMAT_FLEXSECTIONS_ORIENTATION_VERTICAL', 1);
define('FORMAT_FLEXSECTIONS_ORIENTATION_HORIZONTAL', 2);
define('FORMAT_FLEXSECTIONS_SHOWSUMMARY_SHOWFULL', 1);
define('FORMAT_FLEXSECTIONS_SHOWSUMMARY_SHOW', 3);
define('FORMAT_FLEXSECTIONS_SHOWSUMMARY_HIDE', 2);
define('FORMAT_FLEXSECTIONS_SECTION0_COURSEPAGE', 1);
define('FORMAT_FLEXSECTIONS_SECTION0_ALLPAGES', 2);
define('FORMAT_FLEXSECTIONS_SECTIONVIEW_CARDS', 1);
define('FORMAT_FLEXSECTIONS_SECTIONSVIEW_LIST', 2);

/**
 * Main class for the Flexible sections course format.
 *
 * @package    format_flexsections
 * @copyright  2022 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_flexsections extends core_courseformat\base {

    /**
     * Returns true if this course format uses sections.
     *
     * @return bool
     */
    public function uses_sections() {
        return true;
    }

    /**
     * Uses course index
     *
     * @return bool
     */
    public function uses_course_index() {
        return true;
    }

    /**
     * Uses indentation
     *
     * @return bool
     */
    public function uses_indentation(): bool {
        return false;
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * Use section name is specified by user. Otherwise use default ("Topic #").
     *
     * @param int|stdClass|section_info $section Section object from database or just field section.section
     * @return string Display name that the course format prefers, e.g. "Topic 2"
     */
    public function get_section_name($section) {
        $section = $this->get_section($section);
        if ((string)$section->name !== '') {
            return format_string($section->name, true,
                ['context' => context_course::instance($this->courseid)]);
        } else {
            return $this->get_default_section_name($section);
        }
    }

    /**
     * Returns the default section name for the flexsections course format.
     *
     * If the section number is 0, it will use the string with key = section0name from the course format's lang file.
     * If the section number is not 0, the base implementation of course_format::get_default_section_name which uses
     * the string with the key = 'sectionname' from the course format's lang file + the section number will be used.
     *
     * @param stdClass|section_info $section Section object from database or just field course_sections section
     * @return string The default value for the section name.
     */
    public function get_default_section_name($section) {
        if ($section->section == 0) {
            // Return the general section.
            return get_string('section0name', 'format_flexsections');
        } else {
            // Use course_format::get_default_section_name implementation which
            // will display the section name in "Topic n" format.
            return parent::get_default_section_name($section);
        }
    }

    /**
     * Generate the title for this section page.
     *
     * @return string the page title
     */
    public function page_title(): string {
        return get_string('topicoutline');
    }

    /**
     * Returns the section relative number regardless whether argument is an object or an int
     *
     * @param int|section_info $section
     * @return int
     */
    protected function resolve_section_number($section) {
        if ($section === null || $section === '') {
            return null;
        } else if (is_object($section)) {
            return $section->section;
        } else {
            return (int)$section;
        }
    }

    /**
     * The URL to use for the specified course (with section).
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     *     if omitted the course view page is returned
     * @param array $options options for view URL. At the moment core uses:
     *     'navigation' (bool) if true and section has no separate page, the function returns null
     *     'sr' (int) used by multipage formats to specify to which section to return
     * @return null|moodle_url
     */

    public function get_view_url($section, $options = []) {
        global $PAGE;

        $url = new moodle_url('/course/view.php', ['id' => $this->courseid]);

        $sectionno = $this->resolve_section_number($section);
        $section = $this->get_section($sectionno);
        if ($sectionno && (!$section->uservisible || !$this->is_section_real_available($section))) {
            return empty($options['navigation']) ? $url : null;
        }

        if (array_key_exists('sr', $options)) {
            // Return to the page for section with number $sr.
            $url->param('section', $options['sr']);
            if ($sectionno) {
                $url->set_anchor('section-'.$sectionno);
            }
        } else if ($sectionno) {

            if ($PAGE->user_is_editing()) {
                $url->set_anchor('section-'.$sectionno);
            } else {
                $url->param('section', $section->section);
            }

            // Check if this section has separate page.
            //if ($section->collapsed == FORMAT_FLEXSECTIONS_COLLAPSED) {
            //    $url->param('section', $section->section);
            //    return $url;
            //}
            //// Find the parent (or grandparent) page that is displayed on separate page.
            //if ($parent = $this->find_collapsed_parent($section->parent)) {
            //    $url->param('section', $parent);
            //}
            //$url->set_anchor('section-'.$sectionno);

            return $url;
        } else {
            // General section.
            $url->set_anchor('section-'.$sectionno);
            return $url;
        }
        return $url;
    }

    /**
     * Returns the information about the ajax support in the given source format.
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     *
     * @return stdClass
     */
    public function supports_ajax() {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }

    /**
     * Supports components
     *
     * @return bool
     */
    public function supports_components() {
        return true;
    }

    /**
     * Loads all of the course sections into the navigation.
     *
     * @param global_navigation $navigation
     * @param navigation_node $node The course node within the navigation
     * @return void
     */
    public function extend_course_navigation($navigation, navigation_node $node) {
        // If course format displays section on separate pages and we are on course/view.php page
        // and the section parameter is specified, make sure this section is expanded in
        // navigation.
        if ($navigation->includesectionnum === false && $this->get_viewed_section() &&
            (!defined('AJAX_SCRIPT') || AJAX_SCRIPT == '0')) {
            $navigation->includesectionnum = $this->get_viewed_section();
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
    }

    /**
     * Adds a course module to the navigation node
     *
     * @param navigation_node $node
     * @param cm_info $cm
     * @return null|navigation_node
     */
    protected function navigation_add_activity(navigation_node $node, cm_info $cm): ?navigation_node {
        if (!$cm->uservisible || !$cm->has_view()) {
            return null;
        }
        $activityname = $cm->get_formatted_name();
        $action = $cm->url;
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
        if (method_exists($cm, 'is_visible_on_course_page')) {
            $activitynode->display = $cm->is_visible_on_course_page();
        }
        return $activitynode;
    }

    /**
     * Adds a section to navigation node, loads modules and subsections if necessary
     *
     * @param global_navigation $navigation
     * @param navigation_node $node
     * @param section_info $section
     * @return null|navigation_node
     */
    protected function navigation_add_section($navigation, navigation_node $node, section_info $section): ?navigation_node {
        if (!$section->uservisible || !$this->is_section_real_available($section)) {
            return null;
        }
        $sectionname = get_section_name($this->get_course(), $section);
        $url = course_get_url($this->get_course(), $section->section, array('navigation' => true));

        $sectionnode = $node->add($sectionname, $url, navigation_node::TYPE_SECTION, null, $section->id);
        $sectionnode->nodetype = navigation_node::NODETYPE_BRANCH;
        $sectionnode->hidden = !$section->visible || !$section->available;
        if ($section->section == $this->get_viewed_section()) {
            $sectionnode->force_open();
        }
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
     * Custom action after section has been moved in AJAX mode.
     *
     * Used in course/rest.php
     *
     * @return array This will be passed in ajax respose
     */
    public function ajax_section_move() {
        global $PAGE;
        $titles = [];
        $course = $this->get_course();
        $modinfo = get_fast_modinfo($course);
        $renderer = $this->get_renderer($PAGE);
        if ($renderer && ($sections = $modinfo->get_section_info_all())) {
            foreach ($sections as $number => $section) {
                $titles[$number] = $renderer->section_title($section, $course);
            }
        }
        return ['sectiontitles' => $titles, 'action' => 'move'];
    }

    /**
     * Returns the list of blocks to be automatically added for the newly created course.
     *
     * @return array of default blocks, must contain two keys BLOCK_POS_LEFT and BLOCK_POS_RIGHT
     *     each of values is an array of block names (for left and right side columns)
     */
    public function get_default_blocks() {
        return [
            BLOCK_POS_LEFT => [],
            BLOCK_POS_RIGHT => [],
        ];
    }

    /**
     * Definitions of the additional options that this course format uses for section
     *
     * See {@see format_base::course_format_options()} for return array definition.
     *
     * Additionally section format options may have property 'cache' set to true
     * if this option needs to be cached in {@see get_fast_modinfo()}. The 'cache' property
     * is recommended to be set only for fields used in {@see format_base::get_section_name()},
     * {@see format_base::extend_course_navigation()} and {@see format_base::get_view_url()}
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
            'visibleold' => array(
                'type' => PARAM_INT,
                'label' => '',
                'element_type' => 'hidden',
                'default' => 1,
                'cache' => true,
                'cachedefault' => 0,
            ),
            'collapsed' => array(
                'type' => PARAM_INT,
                'label' => get_string('displaycontent', 'format_flexsections'),
                'element_type' => 'select',
                'element_attributes' => array(
                    array(
                        FORMAT_FLEXSECTIONS_EXPANDED => new lang_string('showexpanded', 'format_flexsections'),
                        FORMAT_FLEXSECTIONS_COLLAPSED => new lang_string('showcollapsed', 'format_flexsections'),
                    )
                ),
                'cache' => true,
                'cachedefault' => FORMAT_FLEXSECTIONS_COLLAPSED,
                'default' => FORMAT_FLEXSECTIONS_COLLAPSED,
            )
        );
    }

    /**
     * Adds format options elements to the course/section edit form.
     *
     * This function is called from {@see course_edit_form::definition_after_data()}.
     *
     * @param MoodleQuickForm $mform form the elements are added to.
     * @param bool $forsection 'true' if this is a section edit form, 'false' if this is course edit form.
     * @return array array of references to the added form elements.
     */
    public function create_edit_form_elements(&$mform, $forsection = false) {
        global $COURSE;
        $elements = parent::create_edit_form_elements($mform, $forsection);

        if (!$forsection && (empty($COURSE->id) || $COURSE->id == SITEID)) {
            // Add "numsections" element to the create course form - it will force new course to be prepopulated
            // with empty sections.
            // The "Number of sections" option is no longer available when editing course, instead teachers should
            // delete and add sections when needed.
            $courseconfig = get_config('moodlecourse');
            $max = (int)$courseconfig->maxsections;
            $element = $mform->addElement('select', 'numsections', get_string('numberweeks'), range(0, $max ?: 52));
            $mform->setType('numsections', PARAM_INT);
            if (is_null($mform->getElementValue('numsections'))) {
                $mform->setDefault('numsections', $courseconfig->numsections);
            }
            array_unshift($elements, $element);
        }

        $defaultshowprogress = get_config('format_flexsextions', 'showprogress');
        $hiddenvalues = [
            FORMAT_FLEXSECTIONS_SHOWPROGRESS_HIDE
        ];

        if ($defaultshowprogress == FORMAT_FLEXSECTIONS_SHOWPROGRESS_HIDE) {
            $hiddenvalues[] = FORMAT_FLEXSECTIONS_USEDEFAULT;
        }
        $mform->hideIf('progressformat', 'showprogress', 'in', $hiddenvalues);

        return $elements;
    }

    /**
     * Whether this format allows to delete sections.
     *
     * Do not call this function directly, instead use {@see course_can_delete_section()}
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function can_delete_section($section) {
        return true;
    }

    /**
     * Prepares the templateable object to display section name.
     *
     * @param \section_info|\stdClass $section
     * @param bool $linkifneeded
     * @param bool $editable
     * @param null|lang_string|string $edithint
     * @param null|lang_string|string $editlabel
     * @return inplace_editable
     */
    public function inplace_editable_render_section_name($section, $linkifneeded = true,
            $editable = null, $edithint = null, $editlabel = null) {
        if (empty($edithint)) {
            $edithint = new lang_string('editsectionname', 'format_flexsections');
        }
        if (empty($editlabel)) {
            $title = get_section_name($section->course, $section);
            $editlabel = new lang_string('newsectionname', 'format_flexsections', $title);
        }
        $section = $this->get_section($section);
        if ($linkifneeded && $section->collapsed != FORMAT_FLEXSECTIONS_COLLAPSED) {
            $linkifneeded = false;
        }
        return parent::inplace_editable_render_section_name($section, $linkifneeded, $editable, $edithint, $editlabel);
    }

    /**
     * Indicates whether the course format supports the creation of a news forum.
     *
     * @return bool
     */
    public function supports_news() {
        return true;
    }

    /**
     * Returns whether this course format allows the activity to
     * have "triple visibility state" - visible always, hidden on course page but available, hidden.
     *
     * @param stdClass|cm_info $cm course module (may be null if we are displaying a form for adding a module)
     * @param stdClass|section_info $section section where this module is located or will be added to
     * @return bool
     */
    public function allow_stealth_module_visibility($cm, $section) {
        // Allow the third visibility state inside visible sections or in section 0.
        return !$section->section || $section->visible;
    }

    /**
     * Callback used in WS core_course_edit_section when teacher performs an AJAX action on a section (show/hide).
     *
     * Access to the course is already validated in the WS but the callback has to make sure
     * that particular action is allowed by checking capabilities
     *
     * Course formats should register.
     *
     * @param section_info|stdClass $section
     * @param string $action
     * @param int $sr
     * @return null|array any data for the Javascript post-processor (must be json-encodeable)
     */
    public function section_action($section, $action, $sr) {
        global $PAGE;

        if ($section->section && ($action === 'setmarker' || $action === 'removemarker')) {
            // Format 'flexsections' allows to set and remove markers in addition to common section actions.
            require_capability('moodle/course:setcurrentsection', context_course::instance($this->courseid));
            course_set_marker($this->courseid, ($action === 'setmarker') ? $section->section : 0);
            return null;
        }

        if ($section->section && ($action === 'showexpanded' || $action === 'showcollapsed')) {
            require_capability('moodle/course:update', context_course::instance($this->courseid));
            $newvalue = ($action === 'showexpanded') ? FORMAT_FLEXSECTIONS_EXPANDED : FORMAT_FLEXSECTIONS_COLLAPSED;
            course_update_section($this->courseid, $section, ['collapsed' => $newvalue]);
            // TODO what to return?
            return null;
        }

        $mergeup = optional_param('mergeup', null, PARAM_INT);
        if ($mergeup && has_capability('moodle/course:update', context_course::instance($this->courseid))) {
            require_sesskey();
            $section = $this->get_section($mergeup, MUST_EXIST);
            $this->mergeup_section($section);
            $url = course_get_url($this->courseid, $section->parent);
            redirect($url);
        }

        // For show/hide actions call the parent method and return the new content for .section_availability element.
        $rv = parent::section_action($section, $action, $sr);
        $renderer = $PAGE->get_renderer('format_flexsections');

        if (!($section instanceof section_info)) {
            $modinfo = course_modinfo::instance($this->courseid);
            $section = $modinfo->get_section_info($section->section);
        }
        $elementclass = $this->get_output_classname('content\\section\\availability');
        $availability = new $elementclass($this, $section);

        $rv['section_availability'] = $renderer->render($availability);
        return $rv;
    }

    /**
     * Return the plugin configs for external functions.
     *
     * @return array the list of configuration settings
     */
    public function get_config_for_external() {
        // Return everything (nothing to hide).
        return $this->get_format_options();
    }

    /**
     * Checks if section is really available for the current user (analyses parent section available)
     *
     * @param int|section_info $section
     * @return bool
     */
    public function is_section_real_available($section) {
        if (($this->resolve_section_number($section) == 0)) {
            // Section 0 is always available.
            return true;
        }
        $context = context_course::instance($this->courseid);
        if (has_capability('moodle/course:viewhiddensections', $context)) {
            // For the purpose of this function only return true for teachers.
            return true;
        }
        $section = $this->get_section($section);
        return $section->available && $this->is_section_real_available($section->parent);
    }

    /**
     * Returns either section or it's parent or grandparent, whoever first is collapsed
     *
     * @param int|section_info $section
     * @param bool $returnid
     * @return int
     */
    public function find_collapsed_parent($section, $returnid = false) {
        $section = $this->get_section($section);
        if (!$section->section || $section->collapsed == FORMAT_FLEXSECTIONS_COLLAPSED) {
            return $returnid ? $section->id : $section->section;
        } else {
            return $this->find_collapsed_parent($section->parent, $returnid);
        }
    }

    /**
     * URL of the page from where this function was called (use referer if this is an AJAX request)
     *
     * @return moodle_url
     */
    protected function get_caller_page_url(): moodle_url {
        global $PAGE, $FULLME;
        $url = $PAGE->has_set_url() ? $PAGE->url : new moodle_url($FULLME);
        if ($url->compare(new moodle_url('/lib/ajax/service.php'), URL_MATCH_BASE)) {
            return !empty($_SERVER['HTTP_REFERER']) ? new moodle_url($_SERVER['HTTP_REFERER']) : $url;
        }
        return $url;
    }

    /**
     * Returns true if we are on /course/view.php page
     *
     * @return bool
     */
    public function on_course_view_page() {
        $url = $this->get_caller_page_url();
        return ($url && $url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE));
    }

    /**
     * If we are on course/view.php page return the 'section' attribute from query
     *
     * @return int
     */
    public function get_viewed_section() {
        if ($this->on_course_view_page()) {
            if ($s = $this->get_caller_page_url()->get_param('section')) {
                return $s;
            }
            $sid = $this->get_caller_page_url()->get_param('sectionid');
            if ($sid && ($section = $this->get_modinfo()->get_section_info_by_id($sid))) {
                return $section->section;
            }
        }
        return 0;
    }

    /**
     * Is this section displayed on the current page
     *
     * Used in course index
     *
     * @param int $sectionnum
     * @return bool
     */
    public function is_section_displayed_on_current_page(int $sectionnum): bool {
        $viewedsection = $this->get_viewed_section();
        if ($viewedsection) {
            return $sectionnum == $viewedsection || $this->section_has_parent($sectionnum, $viewedsection);
        } else {
            $section = $this->get_section($sectionnum);
            if (!$section->parent) {
                return true;
            }
            return $this->find_collapsed_parent($section->parent) ? false : true;
        }
    }

    /**
     * Create a new section under given parent
     *
     * @param int|section_info $parent parent section
     * @param null|int|section_info $before
     * @return int $sectionnum
     */
    public function create_new_section($parent = 0, $before = null): int {
        $section = course_create_section($this->courseid, 0);
        $sectionnum = $this->move_section($section, $parent, $before);
        return $sectionnum;
    }

    /**
     * Allows course format to execute code on moodle_page::set_course()
     *
     * format_flexsections processes additional attributes in the view course URL
     * to manipulate sections and redirect to course view page
     *
     * @param moodle_page $page instance of page calling set_course
     */
    public function page_set_course(moodle_page $page) {
        global $PAGE;
        if ($PAGE != $page) {
            return;
        }
        if ($this->on_course_view_page()) {
            $context = context_course::instance($this->courseid);
            $currentsectionnum = $this->get_viewed_section();

            // Fix the section argument.
            if ($currentsectionnum) {
                $sectioninfo = $this->get_modinfo()->get_section_info($currentsectionnum);
                if (!$sectioninfo || !$sectioninfo->collapsed) {

                    // PTL-9606.
                    if ($this->get_format_option('sectionviewoption') != FORMAT_FLEXSECTIONS_SECTIONVIEW_CARDS) {
                        redirect(course_get_url($this->get_course(), $sectioninfo ? $this->find_collapsed_parent($sectioninfo) : null));
                    }
                }
            }

            if (!$this->is_section_real_available($this->get_viewed_section())) {
                throw new moodle_exception('nopermissiontoviewpage');
            }

            if ($currentsectionnum) {
                navigation_node::override_active_url(new moodle_url('/course/view.php',
                    array('id' => $this->courseid,
                        'section' => $currentsectionnum)));
            }

            // If requested, create new section and redirect to course view page.
            $addchildsection = optional_param('addchildsection', null, PARAM_INT);
            if ($addchildsection !== null && has_capability('moodle/course:update', $context)) {
                $sectionnum = $this->create_new_section($addchildsection);
                $url = course_get_url($this->courseid, $sectionnum);
                redirect($url);
            }

            // If requested, merge the section content with parent and remove the section.
            $mergeup = optional_param('mergeup', null, PARAM_INT);
            if ($mergeup && confirm_sesskey() && has_capability('moodle/course:update', $context)) {
                $section = $this->get_section($mergeup, MUST_EXIST);
                $this->mergeup_section($section);
                $url = course_get_url($this->courseid, $section->parent);
                redirect($url);
            }

            // If requested, delete the section.
            $deletesection = optional_param('deletesection', null, PARAM_INT);
            if ($deletesection && confirm_sesskey() && has_capability('moodle/course:update', $context)
                && optional_param('confirm', 0, PARAM_INT) == 1) {
                $section = $this->get_section($deletesection, MUST_EXIST);
                $parent = $section->parent;
                $this->delete_section_with_children($section);
                $url = course_get_url($this->courseid, $parent);
                redirect($url);
            }

            // If requested, move section.
            $movesection = optional_param('movesection', null, PARAM_INT);
            $moveparent = optional_param('moveparent', null, PARAM_INT);
            $movebefore = optional_param('movebefore', null, PARAM_RAW);
            $sr = optional_param('sr', null, PARAM_RAW);
            $options = array();
            if ($sr !== null) {
                $options = array('sr' => $sr);
            }
            if ($movesection !== null && $moveparent !== null && has_capability('moodle/course:update', $context)) {
                $newsectionnum = $this->move_section($movesection, $moveparent, $movebefore);
                redirect(course_get_url($this->courseid, $newsectionnum, $options));
            }

            // If requested, switch collapsed attribute.
            $switchcollapsed = optional_param('switchcollapsed', null, PARAM_INT);
            if ($switchcollapsed && confirm_sesskey() && has_capability('moodle/course:update', $context)
                && ($section = $this->get_section($switchcollapsed))) {
                if ($section->collapsed == FORMAT_FLEXSECTIONS_EXPANDED) {
                    $newvalue = FORMAT_FLEXSECTIONS_COLLAPSED;
                } else {
                    $newvalue = FORMAT_FLEXSECTIONS_EXPANDED;
                }
                $this->update_section_format_options(array('id' => $section->id, 'collapsed' => $newvalue));
                if ($newvalue == FORMAT_FLEXSECTIONS_COLLAPSED) {
                    if (!isset($options['sr'])) {
                        $options['sr'] = $this->find_collapsed_parent($section->parent);
                    }
                    redirect(course_get_url($this->courseid, $switchcollapsed, $options));
                } else {
                    redirect(course_get_url($this->courseid, $switchcollapsed, $options));
                }
            }

            // Set course marker if required.
            $marker = optional_param('marker', null, PARAM_INT);
            if ($marker !== null && has_capability('moodle/course:setcurrentsection', $context) && confirm_sesskey()) {
                if ($marker > 0) {
                    // Set marker.
                    $url = course_get_url($this->courseid, $marker, array('sr' => $this->get_viewed_section()));
                    course_set_marker($this->courseid, $marker);
                    redirect($url);
                } else if ($this->get_course()->marker) {
                    // Remove marker.
                    $url = course_get_url($this->courseid, $this->get_course()->marker,
                        array('sr' => $this->get_viewed_section()));
                    course_set_marker($this->courseid, 0);
                    redirect($url);
                }
            }

            // Change visibility if required.
            $hide = optional_param('hide', null, PARAM_INT);
            if ($hide !== null && has_capability('moodle/course:sectionvisibility', $context) && confirm_sesskey()) {
                $url = course_get_url($this->courseid, $hide, array('sr' => $this->get_viewed_section()));
                $this->set_section_visible($hide, 0);
                redirect($url);
            }
            $show = optional_param('show', null, PARAM_INT);
            if ($show !== null && has_capability('moodle/course:sectionvisibility', $context) && confirm_sesskey()) {
                $url = course_get_url($this->courseid, $show, array('sr' => $this->get_viewed_section()));
                $this->set_section_visible($show, 1);
                redirect($url);
            }
        }
    }

    /**
     * Moves section to the specified position
     *
     * @param int|section_info $section
     * @param int|section_info $parent
     * @param null|int|section_info $before
     * @return int new section number
     */
    public function move_section($section, $parent, $before = null) {
        global $DB;
        $section = $this->get_section($section);
        $parent = $this->get_section($parent);
        $newsectionnumber = $section->section;
        if (!$this->can_move_section_to($section, $parent, $before)) {
            return $newsectionnumber;
        }
        if ($section->visible != $parent->visible && $section->parent != $parent->section) {
            // Section is changing parent and new parent has different visibility than the section.
            if ($section->visible) {
                // Visible section is moved under hidden parent.
                $updatesectionvisible = 0;
                $updatesectionvisibleold = 1;
            } else {
                // Hidden section is moved under visible parent.
                if ($section->visibleold) {
                    $updatesectionvisible = 1;
                    $updatesectionvisibleold = 1;
                }
            }
        }

        // Find the changes in the sections numbering.
        $origorder = array();
        foreach ($this->get_sections() as $subsection) {
            $origorder[$subsection->id] = $subsection->section;
        }
        $neworder = array();
        $this->reorder_sections($neworder, 0, $section->section, $parent, $before);
        if (count($origorder) != count($neworder)) {
            die('Error in sections hierarchy'); // TODO.
        }
        $changes = array();
        foreach ($origorder as $id => $num) {
            if ($num == $section->section) {
                $newsectionnumber = $neworder[$id];
            }
            if ($num != $neworder[$id]) {
                $changes[$id] = array('old' => $num, 'new' => $neworder[$id]);
                if ($num && $this->get_course()->marker == $num) {
                    $changemarker = $neworder[$id];
                }
            }
            if ($this->resolve_section_number($parent) === $num) {
                $newparentnum = $neworder[$id];
            }
        }

        if (empty($changes) && $newparentnum == $section->parent) {
            return $newsectionnumber;
        }

        // Build array of required changes in field 'parent'.
        $changeparent = array();
        foreach ($this->get_sections() as $subsection) {
            foreach ($changes as $id => $change) {
                if ($subsection->parent == $change['old']) {
                    $changeparent[$subsection->id] = $change['new'];
                }
            }
        }
        $changeparent[$section->id] = $newparentnum;

        // Update all in database in one transaction.
        $transaction = $DB->start_delegated_transaction();
        // Update sections numbers in 2 steps to avoid breaking database uniqueness constraint.
        foreach ($changes as $id => $change) {
            $DB->set_field('course_sections', 'section', -$change['new'], array('id' => $id));
        }
        foreach ($changes as $id => $change) {
            $DB->set_field('course_sections', 'section', $change['new'], array('id' => $id));
        }
        // Change parents of their subsections.
        foreach ($changeparent as $id => $newnum) {
            $this->update_section_format_options(array('id' => $id, 'parent' => $newnum));
        }
        $transaction->allow_commit();
        rebuild_course_cache($this->courseid, true);
        if (isset($changemarker)) {
            course_set_marker($this->courseid, $changemarker);
        }
        if (isset($updatesectionvisible)) {
            $this->set_section_visible($newsectionnumber, $updatesectionvisible, $updatesectionvisibleold);
        }
        return $newsectionnumber;
    }

    /**
     * Sets the section visible/hidden including subsections and modules
     *
     * @param int|stdClass|section_info $section
     * @param int $visibility
     * @param null|int $setvisibleold if specified in case of hiding the section,
     *    this will be the value of visibleold for the section $section.
     */
    protected function set_section_visible($section, $visibility, $setvisibleold = null) {
        $subsections = array();
        $sectionnumber = $this->resolve_section_number($section);
        if (!$sectionnumber && !$visibility) {
            // Can not hide section with number 0.
            return;
        }
        $section = $this->get_section($section);
        if ($visibility && $section->parent && !$this->get_section($section->parent)->visible) {
            // Can not set section visible when parent is hidden.
            return;
        }
        $ch = array($section);
        while (!empty($ch)) {
            $chlast = $ch;
            $ch = array();
            foreach ($chlast as $s) {
                // Store copy of attributes to avoid rebuilding course cache when we need to access section properties.
                $subsections[] = (object)array('section' => $s->section,
                    'id' => $s->id, 'visible' => $s->visible, 'visibleold' => $s->visibleold);
                $ch += $this->get_subsections($s);
            }
        }
        foreach ($subsections as $s) {
            if ($s->section == $sectionnumber) {
                set_section_visible($this->courseid, $s->section, $visibility);
                if ($setvisibleold === null) {
                    $setvisibleold = $visibility;
                }
                $this->update_section_format_options(array('id' => $s->id, 'visibleold' => $setvisibleold));
            } else {
                if ($visibility) {
                    if ($s->visibleold) {
                        set_section_visible($this->courseid, $s->section, $s->visibleold);
                    }
                } else {
                    if ($s->visible) {
                        set_section_visible($this->courseid, $s->section, $visibility);
                        $this->update_section_format_options(array('id' => $s->id, 'visibleold' => $s->visible));
                    }
                }
            }
        }
    }

    /**
     * Returns the list of direct subsections of the specified section
     *
     * @param int|section_info $section
     * @return array
     */
    public function get_subsections($section) {
        $sectionnum = $this->resolve_section_number($section);
        $subsections = array();
        foreach ($this->get_sections() as $num => $subsection) {
            if ($subsection->parent == $sectionnum && $num != $sectionnum) {
                $subsections[$num] = $subsection;
            }
        }
        return $subsections;
    }

    /**
     * Function recursively reorders the sections while moving one section to the new position
     *
     * If $movedsectionnum is not specified, function just populates the array for each (sub)section
     * If $movedsectionnum is specified, we ignore it on the present location but add it
     * under $movetoparentnum before $movebeforenum
     *
     * @param array $neworder the result or re-ordering, array (sectionid => sectionnumber)
     * @param int|section_info $cursection
     * @param int|section_info $movedsectionnum
     * @param int|section_info $movetoparentnum
     * @param int|section_info $movebeforenum
     */
    protected function reorder_sections(&$neworder, $cursection, $movedsectionnum = null,
                                        $movetoparentnum = null, $movebeforenum = null) {
        // Normalise arguments.
        $cursection = $this->get_section($cursection);
        $movetoparentnum = $this->resolve_section_number($movetoparentnum);
        $movebeforenum = $this->resolve_section_number($movebeforenum);
        $movedsectionnum = $this->resolve_section_number($movedsectionnum);
        if ($movedsectionnum === null) {
            $movebeforenum = $movetoparentnum = null;
        }

        // Ignore section being moved.
        if ($movedsectionnum !== null && $movedsectionnum == $cursection->section) {
            return;
        }

        // Add current section to $neworder.
        $neworder[$cursection->id] = count($neworder);
        // Loop through subsections and reorder them (insert $movedsectionnum if necessary).
        foreach ($this->get_subsections($cursection) as $subsection) {
            if ($movebeforenum && $subsection->section == $movebeforenum) {
                $this->reorder_sections($neworder, $movedsectionnum);
            }
            $this->reorder_sections($neworder, $subsection, $movedsectionnum, $movetoparentnum, $movebeforenum);
        }
        if (!$movebeforenum && $movetoparentnum !== null && $movetoparentnum == $cursection->section) {
            $this->reorder_sections($neworder, $movedsectionnum);
        }
    }

    /**
     * Check if we can move the section to this position
     *
     * not allow to insert section as it's own subsection
     * not allow to insert section directly before or after itself (it would not change anything)
     *
     * @param int|section_info $section
     * @param int|section_info $parent
     * @param null|section_info|int $before null if in the end of subsections list
     */
    public function can_move_section_to($section, $parent, $before = null) {
        $section = $this->get_section($section);
        $parent = $this->get_section($parent);
        if ($section === null || $parent === null ||
            !has_capability('moodle/course:update', context_course::instance($this->courseid))) {
            return false;
        }
        // Check that $parent is not subsection of $section.
        if ($section->section == $parent->section || $this->section_has_parent($parent, $section->section)) {
            return false;
        }

        if ($before) {
            if (is_string($before)) {
                $before = (int)$before;
            }
            $before = $this->get_section($before);
            // Check that it's a subsection of $parent.
            if (!$before || $before->parent !== $parent->section) {
                return false;
            }
        }

        if ($section->parent == $parent->section) {
            // Section's parent is not being changed
            // do not insert section directly before or after itself.
            if ($before && $before->section == $section->section) {
                return false;
            }
            $subsections = array();
            $lastsibling = null;
            foreach ($this->get_sections() as $num => $sibling) {
                if ($sibling->parent == $parent->section) {
                    if ($before && $before->section == $num) {
                        if ($lastsibling && $lastsibling->section == $section->section) {
                            return false;
                        } else {
                            return true;
                        }
                    }
                    $lastsibling = $sibling;
                }
            }
            if ($lastsibling && !$before && $lastsibling->section == $section->section) {
                return false;
            }
        }
        return true;
    }

    /**
     * Checks if given section has another section among it's parents
     *
     * @param int|section_info $section child section
     * @param int $parentnum parent section number
     * @return boolean
     */
    public function section_has_parent($section, $parentnum) {
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
        } else if ($section->parent >= $section->section) {
            // Some error.
            return false;
        } else {
            return $this->section_has_parent($section->parent, $parentnum);
        }
    }

    /**
     * Completely removes a section, all subsections and activities they contain
     *
     * @param section_info $section
     * @return array Array containing arrays of section ids and course mod ids that were deleted
     */
    public function delete_section_with_children(section_info $section): array {
        global $DB;
        if (!$section->section) {
            // Section 0 does not have parent.
            return [[], []];
        }

        $sectionid = $section->id;
        $course = $this->get_course();

        // Move the section to be removed to the end (this will re-number other sections).
        $this->move_section($section->section, 0);

        $modinfo = get_fast_modinfo($this->courseid);
        $allsections = $modinfo->get_section_info_all();
        $process = false;
        $sectionstodelete = [];
        $modulestodelete = [];
        foreach ($allsections as $sectioninfo) {
            if ($sectioninfo->id == $sectionid) {
                // This is the section to be deleted. Since we have already
                // moved it to the end we know that we need to delete this section
                // and all the following (which can only be its subsections).
                $process = true;
            }
            if ($process) {
                $sectionstodelete[] = $sectioninfo->id;
                if (!empty($modinfo->sections[$sectioninfo->section])) {
                    $modulestodelete = array_merge($modulestodelete,
                        $modinfo->sections[$sectioninfo->section]);
                }
                // Remove the marker if it points to this section.
                if ($sectioninfo->section == $course->marker) {
                    course_set_marker($course->id, 0);
                }
            }
        }

        foreach ($modulestodelete as $cmid) {
            course_delete_module($cmid);
        }

        foreach ($sectionstodelete as $sid) {
            // Invalidate the section cache by given section id.
            course_modinfo::purge_course_section_cache_by_id($course->id, $sid);

            // Delete section summary files.
            $context = \context_course::instance($course->id);
            $fs = get_file_storage();
            $fs->delete_area_files($context->id, 'course', 'section', $sid);
        }

        [$sectionsql, $params] = $DB->get_in_or_equal($sectionstodelete);
        $transaction = $DB->start_delegated_transaction();
        $DB->execute('DELETE FROM {course_format_options} WHERE sectionid ' . $sectionsql, $params);
        $DB->execute('DELETE FROM {course_sections} WHERE id ' . $sectionsql, $params);
        $transaction->allow_commit();

        // Partial rebuild section cache that has been purged.
        rebuild_course_cache($this->courseid, true, true);

        return [$sectionstodelete, $modulestodelete];
    }

    /**
     * Moves the section content to the parent section and deletes it
     *
     * Moves all activities and subsections to the parent section (section 0
     * can never be deleted)
     *
     * @param section_info $section
     */
    public function mergeup_section(section_info $section): void {
        global $DB;
        if (!$section->section || !$section->parent) {
            // Section 0 does not have parent.
            return;
        }

        // Move all modules and activities from this section to parent.
        $modinfo = get_fast_modinfo($this->courseid);
        $allsections = $modinfo->get_section_info_all();
        $subsections = $this->get_subsections($section);
        $parent = $modinfo->get_section_info($section->parent);
        if (!empty($modinfo->sections[$section->section])) {
            foreach ($modinfo->sections[$section->section] as $cmid) {
                moveto_module($modinfo->get_cm($cmid), $parent);
            }
        }
        foreach ($subsections as $subsection) {
            $this->update_section_format_options(
                ['id' => $subsection->id, 'parent' => $parent->section]);
        }

        if ($this->get_course()->marker == $section->section) {
            course_set_marker($this->courseid, 0);
        }

        // Move the section to be removed to the end (this will re-number other sections).
        $this->move_section($section->section, 0);

        // Invalidate the section cache by given section id.
        course_modinfo::purge_course_section_cache_by_id($this->courseid, $section->id);

        // Delete section summary files.
        $context = \context_course::instance($this->courseid);
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'course', 'section', $section->id);

        // Delete section completely.
        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('course_format_options', ['courseid' => $this->courseid, 'sectionid' => $section->id]);
        $DB->delete_records('course_sections', ['id' => $section->id]);
        $transaction->allow_commit();

        // Partial rebuild section cache that has been purged.
        rebuild_course_cache($this->courseid, true, true);
    }

    // TODO updated functions

    /**
     * Modify the edit section form to include controls for editing
     * the image for a section
     *
     * @param string $action
     * @param array $customdata
     * @return editcard_form
     */
    public function editsection_form($action, $customdata = []) {
        if (!array_key_exists('course', $customdata)) {
            $customdata['course'] = $this->get_course();
        }

        $form = new editcard_form($action, $customdata);

        $draftimageid = file_get_submitted_draft_itemid('image');
        file_prepare_draft_area(
            $draftimageid,
            context_course::instance($this->get_courseid())->id,
            'format_flexsections',
            FORMAT_FLEXSECTIONS_FILEAREA_IMAGE,
            $customdata['cs']->id
        );

        $form->set_data([ 'id' => null, 'name' => null, 'image' => $draftimageid ]);

        return $form;
    }

    /**
     * When the section form is changed, make sure any uploaded
     * images are saved properly
     *
     * @param stdClass|array $data Return value from moodleform::get_data() or array with data
     * @return bool True if changes were made
     * @throws coding_exception
     */
    public function update_section_format_options($data) {
        $changes = parent::update_section_format_options($data);

        // Make sure we don't accidentally clobber any existing saved images if we get here
        // from inplace_editable.
        if (!array_key_exists('image', $data)) {
            return $changes;
        }

        file_save_draft_area_files(
            $data['image'],
            context_course::instance($this->get_courseid())->id,
            'format_flexsections',
            FORMAT_FLEXSECTIONS_FILEAREA_IMAGE,
            $data['id']
        );

        // Try and resize the image. It's no big deal if this fails -- we still
        // have the image, it'll just affect page load times.
        try {
            $this->resize_card_image($data['id']);
        } catch (moodle_exception $e) {
            notification::add(
                get_string('editimage:resizefailed', 'format_flexsections'),
                notification::WARNING
            );
        }

        return $changes;
    }
    /**
     * When a section is deleted successfully, make sure we also delete
     * the card image
     *
     * @param int|stdClass|section_info $section
     * @param bool $forcedeleteifnotempty
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     */
    public function delete_section($section, $forcedeleteifnotempty = false) {
        global $DB;

        if (!is_object($section)) {
            $section = $DB->get_record('course_sections',
                [
                    'course' => $this->get_courseid(),
                    'section' => $section
                ]);
        }

        $filestorage = get_file_storage();
        $context = context_course::instance($this->get_courseid());
        $images = $filestorage->get_area_files(
            $context->id,
            'format_shiftsections',
            FORMAT_FLEXSECTIONS_FILEAREA_IMAGE,
            $section->id
        );

        foreach ($images as $image) {
            $image->delete();
        }

        return parent::delete_section($section, $forcedeleteifnotempty);
    }
    /**
     * Attempt to resize the image uploaded for a card
     *
     * @param int|stdClass $section Section ID or class
     * @return void
     * @throws coding_exception
     * @throws file_exception
     * @throws moodle_exception
     * @throws stored_file_creation_exception
     */
    public function resize_card_image($section) {
        global $CFG;

        require_once("$CFG->libdir/gdlib.php");

        if (is_object($section)) {
            $section = $section->id;
        }

        $course = $this->get_course();
        $context = context_course::instance($course->id);
        $storage = get_file_storage();

        // First, grab the file.
        $images = $storage->get_area_files(
            $context->id,
            'format_flexsections',
            FORMAT_FLEXSECTIONS_FILEAREA_IMAGE,
            $section,
            'itemid, filepath, filename',
            false
        );

        if (empty($originalimage)) {
            return;
        }

        /** @var stored_file $originalimage */
        $originalimage = reset($images);

        $tempfilepath = $originalimage->copy_content_to_temp('format_flexsections', 'sectionimage_');

        $resized = resize_image($tempfilepath, null, 500, false);

        if (!$resized) {
            throw new moodle_exception('failedtoresize', 'format_flexsections');
        }

        $originalimage->delete();

        try {
            $storage->create_file_from_string(
                [
                    'contextid' => $originalimage->get_contextid(),
                    'component' => $originalimage->get_component(),
                    'filearea' => $originalimage->get_filearea(),
                    'itemid' => $originalimage->get_itemid(),
                    'filepath' => $originalimage->get_filepath(),
                    'filename' => $originalimage->get_filename()
                ], $resized
            );
            $originalimage->delete();
        } finally {
            unlink($tempfilepath);
        }
    }
    /**
     * Gets a list of user options for this course format
     *
     * @param bool $foreditform
     * @return array|array[]|false
     * @throws coding_exception
     * @throws dml_exception
     */
    public function course_format_options($foreditform = false) {

        $defaults = get_config('format_flexsections');
        // We always show one section per page.
        //$options['coursedisplay']['default'] = COURSE_DISPLAY_MULTIPAGE;

        $createselect = function (string $name, array $options, int $default, bool $hashelp = false): array {
            $option = [
                'default' => FORMAT_FLEXSECTIONS_USEDEFAULT,
                'type' => PARAM_INT,
                'label' => new lang_string("form:course:$name", 'format_flexsections'),
                'element_type' => 'select',
                'element_attributes' => [
                    array_merge(
                        [
                            FORMAT_FLEXSECTIONS_USEDEFAULT => new lang_string(
                                'form:course:usedefault',
                                'format_flexsections',
                                $options[$default])
                        ],
                        $options
                    )
                ],
            ];

            if ($hashelp) {
                $option['help'] = "form:course:$name";
                $option['help_component'] = 'format_flexsections';
            }

            return $option;
        };

        static $options = false;
        if ($options === false) {
            $courseconfig = get_config('moodlecourse');
            $options = [
                'hiddensections' => [
                    'default' => $courseconfig->hiddensections,
                    'type' => PARAM_INT,
                ]
            ];
        }
        if ($foreditform) {
            $courseformatoptionsedit = [
                'hiddensections' => [
                    'label' => new lang_string('hiddensections'),
                    'help' => 'hiddensections',
                    'help_component' => 'moodle',
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            0 => new lang_string('hiddensectionscollapsed'),
                            1 => new lang_string('hiddensectionsinvisible')
                        ],
                    ],
                ]
            ];
            $options = array_merge_recursive($options, $courseformatoptionsedit);
        }

        $section0options = [
            FORMAT_FLEXSECTIONS_SECTION0_COURSEPAGE => new lang_string('form:course:section0:coursepage', 'format_flexsections'),
            FORMAT_FLEXSECTIONS_SECTION0_ALLPAGES => new lang_string('form:course:section0:allpages', 'format_flexsections')
        ];
        $options['section0'] = $createselect('section0', $section0options, $defaults->section0, true);

        $orientationoptions = [
            FORMAT_FLEXSECTIONS_ORIENTATION_VERTICAL => new lang_string('form:course:cardorientation:vertical', 'format_flexsections'),
            FORMAT_FLEXSECTIONS_ORIENTATION_HORIZONTAL => new lang_string('form:course:cardorientation:horizontal', 'format_flexsections')
        ];
        $options['cardorientation'] = $createselect('cardorientation', $orientationoptions, $defaults->cardorientation);

        $summaryoptions = [
            FORMAT_FLEXSECTIONS_SHOWSUMMARY_SHOW => new lang_string('form:course:showsummary:show', 'format_flexsections'),
            FORMAT_FLEXSECTIONS_SHOWSUMMARY_HIDE => new lang_string('form:course:showsummary:hide', 'format_flexsections'),
            FORMAT_FLEXSECTIONS_SHOWSUMMARY_SHOWFULL => new lang_string('form:course:showsummary:showfull', 'format_flexsections')
        ];

        $options['showsummary'] = $createselect('showsummary', $summaryoptions, $defaults->showsummary);

        $showprogressoptions = [
            FORMAT_FLEXSECTIONS_SHOWPROGRESS_SHOW => new lang_string('form:course:showprogress:show', 'format_flexsections'),
            FORMAT_FLEXSECTIONS_SHOWPROGRESS_HIDE => new lang_string('form:course:showprogress:hide', 'format_flexsections')
        ];

        $options['showprogress'] = $createselect('showprogress', $showprogressoptions, $defaults->showprogress);

        $progressformatoptions = [
            FORMAT_FLEXSECTIONS_PROGRESSFORMAT_COUNT => new lang_string('form:course:progressformat:count', 'format_flexsections'),
            FORMAT_FLEXSECTIONS_PROGRESSFORMAT_PERCENTAGE => new lang_string('form:course:progressformat:percentage', 'format_flexsections')
        ];

        $options['progressformat'] = $createselect('progressformat', $progressformatoptions, $defaults->progressformat);

        $progressmodeoptions = [
            FORMAT_FLEXSECTIONS_PROGRESSMODE_CIRCLE => new lang_string('form:course:progressmode:circle', 'format_flexsections'),
            FORMAT_FLEXSECTIONS_PROGRESSMODE_LINE => new lang_string('form:course:progressmode:line', 'format_flexsections')
        ];

        $options['progressmode'] = $createselect('progressmode', $progressmodeoptions, $defaults->progressmode);

        $sectionviewoption = [
            FORMAT_FLEXSECTIONS_SECTIONVIEW_CARDS => new lang_string('form:course:sectionview:cards', 'format_flexsections'),
            FORMAT_FLEXSECTIONS_SECTIONSVIEW_LIST => new lang_string('form:course:sectionview:list', 'format_flexsections')
        ];

        $options['sectionviewoption'] = $createselect('sectionviewoption', $sectionviewoption, $defaults->sectionviewoption);

        return $options;
    }

    /**
     * Fetch a format option from the settings. If it's one of the options that can have an admin provided default,
     * use that unless it's been overridden for this course
     *
     * @param string $name Option key
     * @param null|int|section_info|stdClass $section The section this option applies to, or 0 for the whole course
     * @return mixed The option's valie
     * @throws dml_exception
     */
    public function get_format_option(string $name, $section = null) {
        $options = $this->get_format_options($section);
        $defaults = get_config('format_flexsections');

        if (array_key_exists($name, $options)) {
            $value = $options[$name];
        } else {
            $value = $defaults->$name;
        }

        if (!object_property_exists($defaults, $name)) {
            return $value;
        }

        if ($value != FORMAT_FLEXSECTIONS_USEDEFAULT) {
            return $value;
        }

        if (!is_null($section)) {
            $coursedefaults = (object) $this->get_format_options();

            if (!object_property_exists($coursedefaults, $name)) {
                return $defaults->$name;
            }

            if ($coursedefaults->$name != FORMAT_FLEXSECTIONS_USEDEFAULT) {
                return $coursedefaults->$name;
            }
        }

        return $defaults->$name;
    }

    public function get_custom_sections_preferences(): array {
        global $USER;

        $course = $this->get_course();
        $result = get_user_preferences('flexsectionscoursesectionspreferences_' . $course->id, json_encode([]), $USER->id);

        return json_decode($result);
    }

    /**
     * Return the format section preferences.
     *
     * @param string $preferencename preference name
     * @param int[] $sectionids affected section ids
     *
     */
    public function set_sections_preference(string $preferencename, array $sectionids) {
        global $USER;
        $course = $this->get_course();
        $sectionpreferences = $this->get_sections_preferences_by_preference();
        $sectionpreferences[$preferencename] = $sectionids;
        set_user_preference('coursesectionspreferences_' . $course->id, json_encode($sectionpreferences), $USER->id);

        // PTL-9728.
        $json_indexcollapsed = json_encode($sectionpreferences['indexcollapsed']);
        if ($json_indexcollapsed === 'null') {
            $json_indexcollapsed = '[]';
        }
        set_user_preference('flexsectionscoursesectionspreferences_' . $course->id,
            $json_indexcollapsed, $USER->id);

        // Invalidate section preferences cache.
        $coursesectionscache = cache::make('core', 'coursesectionspreferences');
        $coursesectionscache->delete($course->id);
    }
}

/**
 * Implements callback inplace_editable() allowing to edit values in-place.
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return inplace_editable
 */
function format_flexsections_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            [$itemid, 'flexsections'], MUST_EXIST);
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}

/**
 * Get icon mapping for font-awesome.
 */
function format_flexsections_get_fontawesome_icon_map() {
    return [
        'format_flexsections:mergeup' => 'fa-level-up',
    ];
}

/**
 * Serves files for format_flexsections
 *
 * @param stdClass $course
 * @param stdClass|null $coursemodule
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return void
 * @throws coding_exception
 */
function format_flexsections_pluginfile(stdClass $course,
                                 ?stdClass $coursemodule,
                                 context $context,
                                 string $filearea,
                                 array $args,
    $forcedownload,
                                 array $options = []) {
    if ($context->contextlevel != CONTEXT_COURSE && $context->contextlevel != CONTEXT_SYSTEM) {
        send_file_not_found();
    }

    if ($filearea != FORMAT_FLEXSECTIONS_FILEAREA_IMAGE) {
        send_file_not_found();
    }

    $itemid = array_shift($args);

    $filename = array_pop($args);
    if (!$args) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    $filestorage = get_file_storage();
    $file = $filestorage->get_file($context->id, 'format_flexsections', $filearea, $itemid, $filepath, $filename);
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}

function format_flexsections_lastseen($courseid, $sectionid, $userid) {
    global $DB;

    $lastsection = false;

    $sql = "
        SELECT *
        FROM {flexsections_lastaccess}
        WHERE userid=? AND courseid=? AND sectionid > 0
        ORDER BY `timeaccess` DESC
        LIMIT 1
    ";

    if ($obj = $DB->get_record_sql($sql, [$userid, $courseid])) {
        $lastsection = $obj->sectionid == $sectionid ?? false;
    }

    return $lastsection;
}

/**
 * Get course summary image
 * @param core_course_list_element $courseid
 * @param bool $islist
 * @return string
 */
function format_flexsections_get_course_image($course, $islist = false) {
    global $CFG, $OUTPUT, $PAGE;

    $coursecontext = context_course::instance($course->id);
    // require_login($course);
    $PAGE->set_context($coursecontext);

    if (!$islist) {
        $course = new core_course_list_element($course);
    }

    // Course image.
    foreach ($course->get_course_overviewfiles() as $file) {
        $isimage     = $file->is_valid_image();
        $courseimage = file_encode_url("$CFG->wwwroot/pluginfile.php",
            '/' . $file->get_contextid() . '/' . $file->get_component() . '/' .
            $file->get_filearea() . $file->get_filepath() . $file->get_filename(), !$isimage);
        if ($isimage) {
            break;
        }
    }
    if (!empty($courseimage)) {
        return $courseimage;
    } else {
        return $OUTPUT->image_url($CFG->instancename . '_placeholder', 'theme');
    }
}


function format_flexsections_output_fragment_upload_image($args) {
    global $OUTPUT;

    $args = (object) $args;

    $type = isset($args->type) ? $args->type : '';
    $courseid = isset($args->courseid) ? $args->courseid : 0;
    $sectionid = isset($args->sectionid) ? $args->sectionid : 0;

    // Upload form.
    $uploadmform = new upload_image(null, ['type' => $type, 'courseid' => $courseid, 'sectionid' => $sectionid],
            'post', '', null, true, []);

    $uploadhtml = '';
    ob_start();
    $uploadmform->display();
    $uploadhtml .= ob_get_contents();
    ob_end_clean();

    $uploadhtml = str_replace('col-md-3', '', $uploadhtml);
    $uploadhtml = str_replace('col-md-9', 'col-md-12', $uploadhtml);
    $uploadhtml = str_replace('<form ', '<div ', $uploadhtml);
    $uploadhtml = str_replace('</form>', '</div>', $uploadhtml);

    $data = array(
            'uploadhtml' => $uploadhtml,
            'uniqueid' => time(),
            'type' => $type,
            'courseid' => $courseid,
            'sectionid' => $sectionid,
    );

    return $OUTPUT->render_from_template('format_flexsections/upload_image_form', $data);
}

class upload_image extends \moodleform {

    /**
     * Definition of the form
     */
    public function definition() {
        global $DB, $USER;

        $mform =& $this->_form;
        $customdata = $this->_customdata;

        $filemanageroptions = array(
                'accepted_types' => array('.jpg', '.png', '.svg'),
                'maxbytes' => 0,
                'maxfiles' => 1,
                'subdirs' => 0,
                'areamaxbytes' => 10485760,
                'return_types' => FILE_INTERNAL | FILE_EXTERNAL
        );

        $mform->addElement('filemanager', 'uploadedimage', null, null, $filemanageroptions);

        // Default for section.
        if (in_array($customdata['type'], ['singlesection', 'multisection'])) {
            $fs = get_file_storage();
            $sectionid = $customdata['sectionid'];

            $obj = $DB->get_record('course_sections', ['id' => $sectionid]);
            $context = context_course::instance($obj->course);

            $files = $fs->get_area_files($context->id, 'format_flexsections', 'image', $sectionid);
            foreach ($files as $f) {
                if ($f->is_valid_image()) {
                    $draftitemid = file_get_unused_draft_itemid();

                    $usercontext = \context_user::instance($USER->id);
                    $draft = new \StdClass();
                    $draft->contextid = $usercontext->id;
                    $draft->component = 'user';
                    $draft->filearea = 'draft';
                    $draft->itemid = $draftitemid;
                    $draft->userid = $USER->id;
                    $draft->filepath = '/';
                    $draft->filename = $f->get_filename();
                    $draft->source = $f->get_filename();
                    $fs->create_file_from_string($draft, $f->get_content());

                    $mform->setDefault('uploadedimage', $draftitemid);

                    break;
                }
            }
        }

        // Default for course.
        if (in_array($customdata['type'], ['course'])) {
            $fs = get_file_storage();
            $courseid = $customdata['courseid'];
            $context = context_course::instance($courseid);

            $files = $fs->get_area_files($context->id, 'course', 'overviewfiles');
            foreach ($files as $f) {
                if ($f->is_valid_image()) {
                    $draftitemid = file_get_unused_draft_itemid();

                    $usercontext = \context_user::instance($USER->id);
                    $draft = new \StdClass();
                    $draft->contextid = $usercontext->id;
                    $draft->component = 'user';
                    $draft->filearea = 'draft';
                    $draft->itemid = $draftitemid;
                    $draft->userid = $USER->id;
                    $draft->filepath = '/';
                    $draft->filename = $f->get_filename();
                    $draft->source = $f->get_filename();
                    $fs->create_file_from_string($draft, $f->get_content());

                    $mform->setDefault('uploadedimage', $draftitemid);

                    break;
                }
            }
        }
    }

    public function definition_after_data() {
        $mform = $this->_form;
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }

    public function get_editor_options() {
        return $this->_customdata['editoroptions'];
    }
}