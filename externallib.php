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
 * External Web Service class
 *
 * @package    format_flexsections
 * @copyright  2019 Devlion.co
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir . "/externallib.php";
require_once $CFG->dirroot . "/course/format/flexsections/lib.php";
require_once $CFG->dirroot . "/course/format/flexsections/locallib.php";

/**
 * External functions for theme petel.
 *
 * @package     format_flexsections
 * @copyright   2019 Devlion <info@devlion.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_flexsections_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function change_courseimage_parameters() {
        return new external_function_parameters(
            array(
                'fileitemid' => new external_value(PARAM_INT, 'file itemid'),
                'courseid' => new external_value(PARAM_INT, 'course id'),
            )
        );
    }

    /**
     * Returns welcome message
     * @param int $fileitemid
     * @param int $courseid
     * @return string
     */
    public static function change_courseimage($fileitemid, $courseid) {
        global $DB, $OUTPUT;

        $fileitemid = trim($fileitemid);

        $data['type'] = 'course';
        $data['courseid'] = $courseid;

        // Default image.
        $data['imageurl'] = $OUTPUT->get_generated_image_for_id($courseid);

        $fs = get_file_storage();
        $course = get_course($courseid);
        $context = context_course::instance($courseid);

        // Delete old images.
        $fs->delete_area_files($context->id, 'course', 'overviewfiles');

        // Clear cache.
        $image = \cache::make('core', 'course_image');
        $image->delete($course->id);

        $draftfiles = file_get_drafarea_files($fileitemid, '/');
        if (!empty($draftfiles->list)) {

            $sql = "SELECT * FROM {files} WHERE filename != '.' AND component = 'user' AND filearea = 'draft' AND itemid = ?";

            if ($draft = $DB->get_record_sql($sql, array($fileitemid))) {
                $files = $fs->get_area_files($draft->contextid, $draft->component, $draft->filearea, $draft->itemid);
                foreach ($files as $f) {
                    if ($f->is_valid_image()) {
                        $filename = str_replace(' ', '_', $f->get_filename());
                        $fileinfo = array(
                                'contextid' => $context->id,
                                'component' => 'course',
                                'filearea' => 'overviewfiles',
                                'itemid' => 0,
                                'filepath' => '/',
                                'filename' => $filename,
                        );

                        // Save file.
                        $fs->create_file_from_string($fileinfo, $f->get_content());

                        break;
                    }
                }

                $data['imageurl'] = format_flexsections_get_course_image($course);
            }
        }

        return json_encode($data);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function change_courseimage_returns() {
        return new external_value(PARAM_RAW, 'Answer to the front');
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function change_sectionimage_parameters() {
        return new external_function_parameters(
            array(
                'type' => new external_value(PARAM_TEXT, 'type'),
                'sectionid' => new external_value(PARAM_INT, 'section id'),
                'fileitemid' => new external_value(PARAM_INT, 'file itemid'),
            )
        );
    }

    /**
     * Returns welcome message
     * @param int $sectionid
     * @param int fileitemid
     * @return string
     */
    public static function change_sectionimage($type, $sectionid, $fileitemid) {
        global $DB, $OUTPUT, $CFG;

        $fileitemid = trim($fileitemid);

        $data['type'] = $type;
        $data['sectionid'] = $sectionid;

        // Default image.
        $data['imageurl'] = $OUTPUT->get_generated_image_for_id($sectionid);

        //$pattern = new core_geopattern();
        //$pattern->setColor(self::get_course_colour($sectionid));
        //$pattern->patternbyid($sectionid);
        //$data['imageurl'] = $pattern->datauri();

        $obj = $DB->get_record('course_sections', ['id' => $sectionid]);

        $fs = get_file_storage();
        $context = context_course::instance($obj->course);

        // Delete old images.
        $fs->delete_area_files($context->id, 'format_flexsections', 'image', $sectionid);

        $draftfiles = file_get_drafarea_files($fileitemid, '/');
        if (!empty($draftfiles->list)) {

            $sql = "SELECT * FROM {files} WHERE filename != '.' AND component = 'user' AND filearea = 'draft' AND itemid = ?";

            if ($draft = $DB->get_record_sql($sql, array($fileitemid))) {
                $files = $fs->get_area_files($draft->contextid, $draft->component, $draft->filearea, $draft->itemid);
                foreach ($files as $f) {
                    if ($f->is_valid_image()) {

                        $filename = str_replace(' ', '_', $f->get_filename());
                        $fileinfo = array(
                                'contextid' => $context->id,
                                'component' => 'format_flexsections',
                                'filearea'  => 'image',
                                'itemid'    => $sectionid,
                                'filepath'  => '/',
                                'filename'  => $filename,
                        );

                        // Save file.
                        $file = $fs->create_file_from_string($fileinfo, $f->get_content());

                        $data['imageurl'] = $CFG->wwwroot . "/pluginfile.php/". $file->get_contextid() . '/' . $file->get_component() . '/' .
                                $file->get_filearea() . '/' . $file->get_itemid() . $file->get_filepath() . $file->get_filename();

                        break;
                    }
                }
            }
        }

        return json_encode($data);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function change_sectionimage_returns() {
        return new external_value(PARAM_RAW, 'Answer to the front');
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_activity_grade_status_parameters() {
        return new external_function_parameters(
            array(
                'cmids' => new external_value(PARAM_RAW, 'Activity ID'),
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
            )
        );
    }

    /**
     * Returns get_activity_grade_status
     * @return string
     */
    public static function get_activity_grade_status($cmids, $courseid) {
        global $CFG, $OUTPUT, $PAGE;

        $context = context_system::instance();
        $PAGE->set_context($context);

        $params = self::validate_parameters(self::get_activity_grade_status_parameters(),
                array(
                        'cmids' => $cmids,
                        'courseid' => (int) $courseid,
                )
        );

        $result = [];
        foreach(json_decode($cmids) as $cmid) {
            $modinfo = get_fast_modinfo($params['courseid']);
            $cm = $modinfo->get_cm($cmid);

            // Grade status.
            $data = [];
            $data['gradestatus'] = format_flexsections_cm_grade_status($cm);
            $data['submissionstatus'] = format_flexsections_cm_submission_status($cm);
            $gradehtml = $OUTPUT->render_from_template('format_flexsections/activity_grade', $data);

            // Check version updated of oercatalog activity.
            $data = [];
            list($status, $description) = \community_oer\reviews_oer::check_version_of_oercatalog_activity($cmid);
            $data['show_version_updated_btn'] = $status;
            $data['version_updated_btn_description'] = $description;
            $oerversionhtml = $OUTPUT->render_from_template('format_flexsections/activity_version_update', $data);

            $result[] = ['cmid' => $cmid, 'gradestatus' => $gradehtml, 'oerversion' => $oerversionhtml];
        }

        return json_encode(['result' => $result]);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_activity_grade_status_returns() {
        return new external_value(PARAM_RAW, 'Activity grade status');
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_section_status_parameters() {
        return new external_function_parameters(
            array(
                'sectionid' => new external_value(PARAM_INT, 'section id'),
            )
        );
    }

    /**
     * Returns welcome message
     * @param int $sectionid
     * @return string
     */
    public static function get_section_status($sectionid) {
        global $DB, $USER, $COURSE;
        //TODO Improve statistics
        return json_encode([]);
        $data = $cmids = [];

        format_flexsections_get_sub_sections_cmids($cmids, $sectionid);

        $section = $DB->get_record('course_sections', ['id' => $sectionid]);
        $modinfo = get_fast_modinfo($section->course);

        // Get last access activity. PTL-9737.
        if (!empty($cmids)) {

            $sql = "
                SELECT *
                FROM {flexsections_lastaccess}
                WHERE userid=? AND courseid=? AND cmid IN (".implode(',', $cmids).")
                ORDER BY `timeaccess` DESC
                LIMIT 1
            ";

            if ($recent = $DB->get_record_sql($sql, [$USER->id, $COURSE->id])) {
                $cm = $modinfo->get_cm($recent->cmid);
                $data['cmlastaccess'] = [
                        'cmid' => $recent->cmid,
                        'cmname' => $cm->name,
                        'cmurl' => $cm->url->out(),
                ];
            }
        }

        // Teacher.
        if(format_flexsections_has_teacher_course_capability($section->course)){

            $students = [];
            $context = \context_course::instance($section->course);
            foreach (get_enrolled_users($context) as $enroluser) {
                foreach (get_user_roles($context, $enroluser->id, true) as $role) {
                    if ($role->shortname == 'student') {
                        $students[] = $enroluser->id;
                    }
                }
            }

            $cmwaitingforsubmission = $cmfailed = $cmnotsubmitted = 0;
            foreach ($cmids as $cmid){
                try {
                    $cm = $modinfo->get_cm($cmid);
                    $flagwaitingforsubmission = $flagfailed = $flagnotsubmitted = false;
                    foreach ($students as $userid) {
                        if ($tmod = format_flexsections_cm_submission_data($cm, $userid)) {
                            // Status הוגש וטרם נבדק.
                            if ($tmod->submitted && $tmod->requiregrade && !$tmod->grade) {
                                $flagwaitingforsubmission = true;
                            }

                            // Status failed.
                            if ($tmod->failed) {
                                $flagfailed = true;
                            }

                            // Status לאחר תאריך הגשה סופי.
                            if (!$tmod->submitted && $tmod->cutoffdate && $tmod->cutoffdate <= time()) {
                                $flagnotsubmitted = true;
                            }
                        }
                    }

                    if ($flagwaitingforsubmission) {
                        $cmwaitingforsubmission++;
                    }
                    if ($flagfailed) {
                        $cmfailed++;
                    }
                    if ($flagnotsubmitted) {
                        $cmnotsubmitted++;
                    }
                } catch (Exception $e) {

                }
            }

            if($cmwaitingforsubmission > 0){
                $data['firstrow'] = [
                        'value' => $cmwaitingforsubmission,
                        'label' => get_string('statuscmwaitingforsubmission', 'format_flexsections')
                ];
            }
            if($cmfailed > 0){
                $data['secondrow'] = [
                        'value' => $cmfailed,
                        'label' => get_string('statuscmfailed', 'format_flexsections')
                ];
            }
        }else{

            // Student.
            $waitingforsubmission = $failed = $notsubmitted = 0;

            foreach ($cmids as $cmid){
                try {
                    $cm = $modinfo->get_cm($cmid);

                    if ($tmod = format_flexsections_cm_submission_data($cm)) {

                        // Status הוגש וטרם נבדק.
                        if ($tmod->submitted && $tmod->requiregrade && !$tmod->grade) {
                            $waitingforsubmission++;
                        }

                        // Status failed.
                        if ($tmod->failed) {
                            $failed++;
                        }

                        // Status לאחר תאריך הגשה סופי.
                        if (!$tmod->submitted && $tmod->cutoffdate && $tmod->cutoffdate <= time()) {
                            $notsubmitted++;
                        }
                    }
                } catch (Exception $e) {

                }
            }

            if($waitingforsubmission > 0){
                $data['firstrow'] = [
                        'value' => $waitingforsubmission,
                        'label' => get_string('statuswaitingforsubmission', 'format_flexsections')
                ];
            }

            // PTL-10090.
            //if($failed > 0){
            //    $data['secondrow'] = [
            //            'value' => $failed,
            //            'label' => get_string('statusfailed', 'format_flexsections')
            //    ];
            //}

            if($notsubmitted > 0){
                $data['thirdrow'] = [
                        'value' => $notsubmitted,
                        'label' => get_string('statusnotsubmittedintime', 'format_flexsections')
                ];
            }
        }

        return json_encode($data);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_section_status_returns() {
        return new external_value(PARAM_RAW, 'Answer to section status');
    }

    // Update
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_section_content_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Course id'),
                'sectionid' => new external_value(PARAM_INT, 'Section id'),
            )
        );
    }

    /**
     * Returns section data
     * @param int $courseid
     * @param int $sectionid
     * @return array
     */
    public static function get_section_content($courseid, $sectionid) {
        global $PAGE, $USER;

        $context = context_course::instance($courseid);
        self::validate_context($context);

        $params = self::validate_parameters(self::get_section_content_parameters(),
            array(
                'courseid' => $courseid,
                'sectionid' => $sectionid,
            )
        );

        $course = get_course($params['courseid']);
        $format = course_get_format($params['courseid']);

        $modinfo = get_fast_modinfo($course);
        $sections = $modinfo->get_section_info_all();
        foreach($sections as $section){
            if($section->id == $params['sectionid']){
                $thissection = $section;
                break;
            }
        }

        $sectionclass = $format->get_output_classname('content\\section');
        $renderer = $PAGE->get_renderer('format_flexsections');
        $widget = new $sectionclass($format, $thissection);
        $section = $widget->export_for_template($renderer, true);

        // Build js for mod_learningmap. PTL-9855.
        if (isset($section->cmlist->cms)) {
            foreach ($section->cmlist->cms as $key => $cm) {
                if ($cm->cmitem->module == 'learningmap' && strlen($cm->cmitem->cmformat->altcontent) > 0) {
                    $cm->cmitem->cmformat->altcontent .= "
                        <script>
                          require(['mod_learningmap/renderer'], function(renderer) {
                            renderer.init(".$cm->cmitem->id.");
                          });
                        </script>                     
                     ";

                    $section->cmlist->cms[$key] = $cm;
                }
            }
        }

        $section->sharebuttonenable = has_capability('moodle/course:update', $context, $USER->id) ? true : false;

        return ['result' => true, 'data' => json_encode($section)];
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_section_content_returns() {
        return new external_single_structure([
            'result' => new external_value(PARAM_BOOL, 'The processing result'),
            'data' => new external_value(PARAM_RAW, 'Section content'),
        ]);
    }

    private static function get_course_colour($id): string {
        // The colour palette is hardcoded for now. It would make sense to combine it with theme settings.
        $basecolours = [
                '#81ecec', '#74b9ff', '#a29bfe', '#dfe6e9', '#00b894',
                '#0984e3', '#b2bec3', '#fdcb6e', '#fd79a8', '#6c5ce7'
        ];

        return $basecolours[$id % 10];
    }

}
