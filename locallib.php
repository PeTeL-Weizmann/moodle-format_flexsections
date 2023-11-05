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
 * Plugin general functions are defined here.
 *
 * @package     format_flexsections
 * @category    admin
 * @copyright  2018 Devlion <info@devlion.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Render block submission activity only for admin
 * @param cm_info $mod
 * @return array|boolean
 */
function format_flexsections_cm_grade_status(cm_info $mod) {
    global $CFG, $USER, $DB;

    $result = [];

    // For a teacher colleagues don`t show activity status.
    $modcontext = context_module::instance($mod->id);
    $roles      = get_user_roles($modcontext, $USER->id, false);
    foreach ($roles as $role) {
        if ($role->shortname === 'teachercolleague') {
            return false;
        }
    }

    if (format_flexsections_has_teacher_capability($mod->id)) {
        if (in_array($mod->modname, ['assign', 'quiz', 'questionnaire', 'hvp'])) {
            $tooltip       = '';
            $segmentgray   = $segmentblue   = $segmentorange   = $segmentgreen   = $segmentred   = 0;
            $countmaxusers = count(format_flexsections_get_students_course($mod->course));

            switch ($mod->modname) {
                case 'questionnaire':
                    require_once $CFG->dirroot . '/mod/questionnaire/questionnaire.class.php';

                    list($cm, $course, $questionnaire) = questionnaire_get_standard_page_items($mod->id);
                    $questionnaire = new \questionnaire($course, $cm, 0, $questionnaire);

                    $incompleteusers      = questionnaire_get_incomplete_users($questionnaire->cm, $questionnaire->sid);
                    $countincompleteusers = is_array($incompleteusers) ? count($incompleteusers) : 0;

                    // Started users.
                    $data = $DB->get_records_sql("
                        SELECT *
                        FROM {questionnaire_response}
                        WHERE questionnaireid = ? AND complete = ?
                        GROUP BY userid
                    ", [$questionnaire->id, 'n']);

                    $countstartedusers = count($data);

                    $data = $DB->get_records_sql("
                        SELECT *
                        FROM {questionnaire_response}
                        WHERE questionnaireid = ? AND complete = ?
                        GROUP BY userid
                    ", [$questionnaire->id, 'y']);

                    $countcompleteusers = count($data);

                    // Gray - טרם נענה.
                    // Blue - בתהליך.
                    // Green - נענה.

                    $segmentgray = $countincompleteusers;
                    //$segmentblue = $countstartedusers;
                    $segmentgreen = $countcompleteusers;

                    $url = new moodle_url('/mod/questionnaire/report.php', ['instance' => $mod->instance]);

                    // Tooltip.
                    // Status Y תלמידים טרם התחילו.
                    if ($countincompleteusers) {
                        $tooltip .= '<div>' . $countincompleteusers . ' ' . get_string('questionnairenotsubmitted', 'format_flexsections') . ' </div>';
                    }

                    // Status X תלמידים הגישו.
                    if ($countcompleteusers) {
                        $tooltip .= '<div>' . $countcompleteusers . ' ' . get_string('questionnairesubmitted', 'format_flexsections') . ' </div>';
                    }

                    // Students failed.
                    $studentfailed = 0;

                    break;

                case 'assign':
                    require_once $CFG->dirroot . '/mod/assign/locallib.php';

                    list($course, $cm) = get_course_and_cm_from_cmid($mod->id, 'assign');
                    $context           = \context_module::instance($cm->id);
                    $assign            = new \assign($context, $cm, $course);
                    $summary           = $assign->get_assign_grading_summary_renderable();

                    // Gray - טרם התחיל.
                    // Orange - טרם נבדק.
                    // Green - נבדק, ניתן ציון.

                    // If Groups.
                    if ($summary->teamsubmission) {
                        $countmaxusers = $summary->participantcount;

                        if (method_exists($assign, 'count_teams_submissions_need_grading')) {
                            $nothavegrade = $assign->count_teams_submissions_need_grading();
                        } else {
                            $nothavegrade = 0;
                        }

                        $notsubmitted = $countmaxusers - $summary->submissionssubmittedcount;
                        $havegrade    = $summary->submissionssubmittedcount - $nothavegrade;

                        $segmentgray   = $notsubmitted;
                        $segmentorange = $nothavegrade;
                        $segmentgreen  = $havegrade;
                    } else {
                        $countmaxusers = $summary->participantcount;
                        $submitted     = $summary->submissionssubmittedcount;
                        $nothavegrade  = $summary->submissionsneedgradingcount;
                        $notsubmitted  = $countmaxusers - $submitted;
                        $havegrade     = $submitted - $nothavegrade;

                        $segmentgray   = $notsubmitted;
                        $segmentorange = $nothavegrade;
                        $segmentgreen  = $havegrade;
                    }

                    $url = new moodle_url('/mod/assign/view.php', ['id' => $mod->context->instanceid, 'action' => 'grading']);

                    // Tooltip.
                    // Status Y תלמידים טרם הגישו.
                    if ($notsubmitted) {
                        $tooltip .= '<div>' . $notsubmitted . ' ' . get_string('assignnotsubmitted', 'format_flexsections') . ' </div>';
                    }

                    // Status X תלמידים הגישו (ומחכה לבדיקה).
                    if ($nothavegrade) {
                        $tooltip .= '<div>' . $nothavegrade . ' ' . get_string('assignsubmitted', 'format_flexsections') . ' </div>';
                    }

                    // Status V ניתן ציון (אוטומטי או ידני).
                    if ($havegrade) {
                        $tooltip .= '<div>' . $havegrade . ' ' . get_string('assignhavegrade', 'format_flexsections') . ' </div>';
                    }

                    // Students failed.
                    $studentfailed = 0;

                    break;

                case 'quiz':
                    $query = "
						SELECT qa.*
						FROM {quiz_attempts} AS qa
						LEFT JOIN {user} AS u ON(u.id = qa.userid)
						JOIN {user_enrolments} ue_d ON ue_d.userid = u.id
						JOIN {enrol} e_d ON (e_d.id = ue_d.enrolid AND e_d.courseid = ?)
						INNER JOIN (
							SELECT userid, MAX(attempt) AS max_attempt
							  FROM {quiz_attempts}
							  WHERE quiz = ?
							  GROUP BY userid
						) a ON(a.userid = qa.userid AND a.max_attempt = qa.attempt)

						WHERE u.suspended = 0 AND qa.preview = 0 AND ue_d.status = 0 AND qa.quiz = ?
                        AND (
                            (ue_d.timestart = '0' AND ue_d.timeend = '0') OR
                            (ue_d.timestart = '0' AND ue_d.timeend > UNIX_TIMESTAMP()) OR
                            (ue_d.timeend = '0' AND ue_d.timestart < UNIX_TIMESTAMP()) OR
                            (ue_d.timeend > UNIX_TIMESTAMP() AND ue_d.timestart < UNIX_TIMESTAMP())
                            )
					";

                    $params = [$mod->course, $mod->instance, $mod->instance];

                    $querytmp        = $query . " AND qa.state = 'inprogress' ";
                    $countinprogress = count($DB->get_records_sql($querytmp, $params));

                    $querytmp        = $query . " AND qa.state = 'finished' AND qa.sumgrades IS NOT NULL ";
                    $rows = $DB->get_records_sql($querytmp, $params);
                    $countwithgrades = count($rows);

                    // Students failed.
                    $studentfailed = 0;

                    // TODO PTL-10126.
                    //$gi = $DB->get_record('grade_items', ['courseid' => $mod->course, 'itemmodule' => 'quiz', 'iteminstance' => $mod->instance]);
                    //$gradepass = isset($gi->gradepass) ? $gi->gradepass : 0;

                    //if (class_exists('\quiz_advancedoverview\quizdata') && ($gradepass > 0)) {
                    //    $quizdata = new \quiz_advancedoverview\quizdata($mod->get_course_module_record()->id);
                    //    $quizdata->prepare_questions();
                    //    $quizdata->prepare_charts();
                    //    $quizdata->prepare_students();
                    //
                    //    foreach ($quizdata->get_students_table() as $item) {
                    //        $grade = trim(strip_tags($item['grade']));
                    //        if (is_numeric($grade)) {
                    //            if ($grade < $gradepass) {
                    //                $studentfailed++;
                    //            }
                    //        }
                    //    }
                    //}

                    $querytmp           = $query . " AND qa.state = 'finished' AND qa.sumgrades IS NULL ";
                    $countwithoutgrades = count($DB->get_records_sql($querytmp, $params));

                    $url = new moodle_url('/mod/quiz/report.php', ['id' => $mod->context->instanceid, 'mode' => 'advancedoverview']);

                    // Gray - טרם התחיל מענה.
                    // [Blue] Gray - בתהליך.
                    // Orange - הוגש וטרם נבדק.
                    // Green - הוגש ונבדק + יש ציון.
                    // Red - איחור בהגשה.

                    $segmentorange = $countwithoutgrades;
                    $segmentgreen  = $countwithgrades;

                    $segmentgray = $countmaxusers - ($countwithoutgrades + $countwithgrades);
                    $segmentblue = 0;
                    $segmentred  = 0;

                    //$row = $DB->get_record('quiz', ['id' => $mod->instance]);
                    //if ($row->timeclose == 0 || $row->timeclose > time()) {
                    //    $segmentgray = $countmaxusers - ($countinprogress + $countwithoutgrades + $countwithgrades);
                    //    $segmentblue = $countinprogress;
                    //}

                    // Status טרם התחיל.
                    //$countwithoutstarted = $countmaxusers - ($countwithoutgrades + $countwithgrades + $countinprogress);
                    //if($countwithoutstarted){
                    //    $tooltip .='<div>' .  $countwithoutstarted.' '.get_string('quizwithoutstarted', 'format_flexsections').' </div>';
                    //}
                    //
                    //// Status בתהליך.
                    //if($countinprogress){
                    //    $tooltip .= '<div>' . $countinprogress.' '.get_string('quizinprogress', 'format_flexsections').' </div>';
                    //}

                    // Status טרם הוגש.
                    $countnosubmit = $countmaxusers - ($countwithoutgrades + $countwithgrades);
                    if ($countnosubmit) {
                        $tooltip .= '<div>' . $countnosubmit . ' ' . get_string('quiznosubmit', 'format_flexsections') . ' </div>';
                    }

                    // Tooltip.
                    // Status הוגש X תלמידים הגישו (ומחכה לבדיקה).
                    $submitted = $countwithgrades + $countwithoutgrades;

                    if ($submitted != $countwithgrades) {
                        //if ($submitted) {
                        //    $tooltip .= '<div>' . $submitted . ' ' . get_string('quizsubmitted', 'format_flexsections') . ' </div>';
                        //}

                        if ($countwithoutgrades) {
                            $tooltip .= '<div>' . $countwithoutgrades . ' ' . get_string('quizwithoutgrades', 'format_flexsections') .
                                ' </div>';
                        }

                        // Status ניתן ציון.
                        if ($countwithgrades) {
                            $tooltip .= '<div>' . $countwithgrades . ' ' . get_string('quizwithgrades', 'format_flexsections') . ' </div>';
                        }
                    } else {
                        if ($submitted) {
                            $tooltip .= '<div>' . $submitted . ' ' . get_string('quizsubmittedwitgrades', 'format_flexsections') .
                                ' </div>';
                        }
                    }

                    break;

                case 'hvp':

                    $hvp = $DB->get_record_sql(
                        "SELECT h.id,
                                h.name AS title,
                                hl.machine_name,
                                hl.major_version,
                                hl.minor_version
                            FROM {hvp} h
                            JOIN {hvp_libraries} hl ON hl.id = h.main_library_id
                            WHERE h.id = ?", [$mod->instance]);

                    $query = "
                        SELECT u.id,
                           i.id AS gradeitemid,
                           g.id AS gradeid,
                           u.firstnamephonetic, u.lastnamephonetic,
                           u.middlename, u.alternatename, u.firstname, u.lastname,
                           g.rawgrade,
                           g.rawgrademax,
                           g.timemodified,
                           x.id as xapiid
                       FROM {grade_items} i
                       LEFT JOIN {grade_grades} g ON i.id = g.itemid
                       LEFT JOIN {user} u ON u.id = g.userid
                       LEFT JOIN {hvp_xapi_results} x ON g.userid = x.user_id
                       WHERE i.iteminstance = ? AND x.content_id = ? AND i.itemtype = 'mod' AND i.itemmodule = 'hvp' AND x.parent_id IS NULL
                       GROUP BY i.id, g.id, u.id, i.iteminstance, x.id
                       ORDER BY g.timemodified DESC
                    ";

                    $data = $DB->get_records_sql($query, [$mod->instance, $hvp->id]);

                    $havegrade    = count($data);
                    $notsubmitted = $countmaxusers - $havegrade;

                    // Gray - טרם הוגש.
                    // Green - הוגש וניתן ציון.

                    $segmentgray  = $notsubmitted;
                    $segmentgreen = $havegrade;

                    $url = new moodle_url('/mod/hvp/grade.php', ['id' => $mod->context->instanceid]);

                    // Tooltip.
                    // Status Y תלמידים טרם הגישו.
                    if ($notsubmitted) {
                        $tooltip .= '<div>' . $notsubmitted . ' ' . get_string('hvpnotsubmitted', 'format_flexsections') . ' </div>';
                    }

                    // Status V ניתן ציון (אוטומטי או ידני).
                    if ($havegrade) {
                        $tooltip .= '<div>' . $havegrade . ' ' . get_string('hvphavegrade', 'format_flexsections') . ' </div>';
                    }

                    // Students failed.
                    $studentfailed = 0;

                    break;
            }

            $result['url']           = $url;
            $result['countmaxusers'] = $countmaxusers;

            $result['segment_green']         = $segmentgreen;
            $result['segment_green_percent'] = ($countmaxusers > 0) ? $segmentgreen / $countmaxusers * 100 : 0;

            $result['segment_orange']         = $segmentorange;
            $result['segment_orange_percent'] = ($countmaxusers > 0) ? $segmentorange / $countmaxusers * 100 : 0;

            $result['segment_blue']         = $segmentblue;
            $result['segment_blue_percent'] = ($countmaxusers > 0) ? $segmentblue / $countmaxusers * 100 : 0;

            $result['segment_gray']         = $segmentgray;
            $result['segment_gray_percent'] = ($countmaxusers > 0) ? $segmentgray / $countmaxusers * 100 : 0;

            $result['segment_red']         = $segmentred;
            $result['segment_red_percent'] = ($countmaxusers > 0) ? $segmentred / $countmaxusers * 100 : 0;

            $result['tooltip'] = $tooltip;

            $result['student_failed'] = $studentfailed;
        }
    }

    return $result;
}

/**
 * Get activity submission data
 * @param cm_info $mod
 * @return object|boolean
 */
function format_flexsections_cm_submission_data(cm_info $mod, $userid = 0) {
    global $DB, $USER, $CFG;

    require_once ($CFG->dirroot . '/mod/assign/locallib.php');
    require_once ($CFG->dirroot . '/mod/quiz/locallib.php');

    if (!$userid) {
        $userid = $USER->id;
    }

    if (!in_array($mod->modname, ['quiz', 'assign', 'questionnaire', 'hvp'])) {
        return false;
    }

    $extra = $DB->get_record($mod->modname, ['id' => $mod->instance]);
    if (!$extra) {
        return false;
    }

    // Defailt result object.
    $tmod               = new \stdClass();
    $tmod->duedate      = 0;
    $tmod->cutoffdate   = 0;
    $tmod->submitted    = false;
    $tmod->requiregrade = false;
    $tmod->grade        = false;
    $tmod->viewgrade    = false;
    $tmod->reopened     = false;
    $tmod->failed       = false;

    // Prepare data.
    switch ($mod->modname) {
        case 'quiz':
            $tmod->duedate      = $extra->timeopen;
            $tmod->cutoffdate   = $extra->timeclose;
            $tmod->requiregrade = true;

            $sql = "
                SELECT *, qa.id as attemptid
                FROM {quiz_attempts} as qa
                LEFT JOIN {user} as u ON(u.id = qa.userid)
                JOIN {user_enrolments} ue_d ON ue_d.userid = u.id
                JOIN {enrol} e_d ON (e_d.id = ue_d.enrolid AND e_d.courseid = ?)
                WHERE qa.quiz = ? AND qa.userid = ? AND qa.state = 'finished' AND u.suspended = 0 AND ue_d.status = 0
                AND (
                    (ue_d.timestart = '0' AND ue_d.timeend = '0') OR
                    (ue_d.timestart = '0' AND ue_d.timeend > UNIX_TIMESTAMP()) OR
                    (ue_d.timeend = '0' AND ue_d.timestart < UNIX_TIMESTAMP()) OR
                    (ue_d.timeend > UNIX_TIMESTAMP() AND ue_d.timestart < UNIX_TIMESTAMP())
                    )
            ";

            if ($rowas = $DB->get_records_sql($sql, [$mod->course, $mod->instance, $userid])) {

                $row = reset($rowas);

                $attemptid  = $row->attemptid;
                $cmid       = $mod->get_course_module_record()->id;
                $attemptobj = quiz_create_attempt_handling_errors($attemptid, $cmid);
                $attemptobj->preload_all_attempt_step_users();
                $options = $attemptobj->get_display_options(true);

                $tmod->viewgrade = ($options->attempt == 1) ? true : false;
                $tmod->submitted = true;
            }

            $sql = "
                SELECT *
                FROM {quiz_grades} as qq
                LEFT JOIN {user} as u ON(u.id = qq.userid)
                JOIN {user_enrolments} ue_d ON ue_d.userid = u.id
                JOIN {enrol} e_d ON (e_d.id = ue_d.enrolid AND e_d.courseid = ?)
                WHERE qq.quiz = ? AND qq.userid = ? AND u.suspended = 0 AND ue_d.status = 0
                AND (
                    (ue_d.timestart = '0' AND ue_d.timeend = '0') OR
                    (ue_d.timestart = '0' AND ue_d.timeend > UNIX_TIMESTAMP()) OR
                    (ue_d.timeend = '0' AND ue_d.timestart < UNIX_TIMESTAMP()) OR
                    (ue_d.timeend > UNIX_TIMESTAMP() AND ue_d.timestart < UNIX_TIMESTAMP())
                    )
            ";

            if ($rowag = $DB->get_record_sql($sql, [$mod->course, $mod->instance, $userid])) {
                $tmod->grade     = $rowag->grade;
                $tmod->submitted = true;
            }

            if ($tmod->submitted && $tmod->requiregrade && $tmod->grade) {

                // TODO PTL-10126.
                //$gi = $DB->get_record('grade_items', ['courseid' => $mod->course, 'itemmodule' => 'quiz', 'iteminstance' => $mod->instance]);
                //$gradepass = isset($gi->gradepass) ? $gi->gradepass : 0;

                //if (class_exists('\quiz_advancedoverview\quizdata') && ($gradepass > 0)) {
                //    $quizdata = new \quiz_advancedoverview\quizdata($mod->get_course_module_record()->id);
                //    $quizdata->prepare_questions();
                //    $quizdata->prepare_charts();
                //    $quizdata->prepare_students();
                //
                //    foreach ($quizdata->get_students_table() as $item) {
                //        if ($item['userid'] == $userid) {
                //
                //            $grade = trim(strip_tags($item['grade']));
                //            if (is_numeric($grade)) {
                //                if ($grade < $gradepass) {
                //                    $tmod->failed = true;
                //                }
                //            }
                //        }
                //    }
                //}
            }
            break;

        case 'assign':
            $tmod->duedate      = $extra->allowsubmissionsfromdate;
            $tmod->cutoffdate   = $extra->duedate; // $extra->cutoffdate
            $tmod->requiregrade = true;

            list($course, $cm) = get_course_and_cm_from_cmid($mod->id, 'assign');
            $context           = \context_module::instance($cm->id);
            $assign            = new \assign($context, $cm, $course);

            $submission = $assign->get_user_submission($userid, 0);
            $status     = $assign->get_grading_status($userid);

            if ($submission->status === 'submitted') {
                $tmod->submitted = true;
            }

            if ($status === 'notgraded' && !in_array($submission->status, ['new', 'submitted'])) {
                $tmod->submitted = false;
                $tmod->grade     = false;
                $tmod->reopened  = true;
            }

            if ($submission->status !== 'new' && $status !== 'notgraded') {
                $tmod->submitted = true;
                $tmod->grade     = $assign->get_grade_item()->get_final($userid)->finalgrade;
            }
            break;

        case 'questionnaire':
            $tmod->duedate      = $extra->opendate;
            $tmod->cutoffdate   = $extra->closedate;
            $tmod->requiregrade = true;

            $rowas = $DB->get_record('questionnaire_response', [
                'questionnaireid' => $extra->id,
                'userid'          => $userid,
                'complete'        => 'y']);
            if ($rowas) {
                $tmod->submitted = true;
                if (!empty($rowas->grade)) {
                    $tmod->grade = $rowas->grade;
                }
            }
            break;

        case 'hvp':
            $tmod->duedate      = $extra->opendate;
            $tmod->cutoffdate   = $extra->closedate;
            $tmod->requiregrade = true;

            $hvp = $DB->get_record_sql(
                "SELECT h.id,
                                h.name AS title,
                                hl.machine_name,
                                hl.major_version,
                                hl.minor_version
                            FROM {hvp} h
                            JOIN {hvp_libraries} hl ON hl.id = h.main_library_id
                            WHERE h.id = ?", [$mod->instance]);

            $query = "
                        SELECT u.id,
                           i.id AS gradeitemid,
                           g.id AS gradeid,
                           u.firstnamephonetic, u.lastnamephonetic,
                           u.middlename, u.alternatename, u.firstname, u.lastname,
                           g.rawgrade,
                           g.rawgrademax,
                           g.timemodified,
                           x.id as xapiid
                       FROM {grade_items} i
                       LEFT JOIN {grade_grades} g ON i.id = g.itemid
                       LEFT JOIN {user} u ON u.id = g.userid
                       LEFT JOIN {hvp_xapi_results} x ON g.userid = x.user_id
                       WHERE i.iteminstance = ? AND x.content_id = ? AND i.itemtype = 'mod' AND i.itemmodule = 'hvp' AND x.parent_id IS NULL AND u.id=?
                       GROUP BY i.id, g.id, u.id, i.iteminstance, x.id
                       ORDER BY g.timemodified DESC
                    ";

            $rowas = $DB->get_record_sql($query, [$mod->instance, $hvp->id, $userid]);
            if ($rowas && !empty($rowas->rawgrade)) {
                $tmod->submitted = true;
                $tmod->grade     = $rowas->rawgrade;
            }
            break;
    }

    return $tmod;
}

/**
 * Get activity submission status
 * @param cm_info $mod
 * @return array|boolean
 */
function format_flexsections_cm_submission_status(cm_info $mod) {

    if (!$tmod = format_flexsections_cm_submission_data($mod)) {
        return false;
    }

    $icons = new \stdClass();
    $icons->waiting = '<i class="fa-regular fa-circle modicon"></i>';
    $icons->done = '<i class="fa-light fa-circle-check modicon text-success"></i>';
    $icons->over_due = '<i class="fa-light fa-circle-xmark modicon text-danger"></i>';
    $icons->waiting_for_grade = '<i class="fa-light fa-circle-check modicon"></i>';

    $res = new \stdClass();
    $res->modstatus    = '';
    $res->modstyle     = '';
    $res->modicon      = '';

    if (format_flexsections_has_teacher_capability($mod->id)) {
        // Teacher.
        $res->modstatus = ($tmod->cutoffdate) ? date("d/m/Y H:i", $tmod->cutoffdate) : get_string('no_submission_date', 'format_flexsections');
        //$res->modstyle = ($tmod->cutoffdate && $tmod->cutoffdate < time()) ? 'text-danger' : 'text-secondary';
    } else {
        // Student.
        // Status הוגש וניתן ציון.
        if ($tmod->submitted && $tmod->requiregrade && $tmod->grade) {

            if ($tmod->viewgrade) {
                $res->modstatus = get_string('complete', 'format_flexsections') . ' (' . ceil($tmod->grade) . ')';
            } else {
                $res->modstatus = get_string('complete', 'format_flexsections');
            }

            $res->modicon = $icons->done;
            $res->modstyle = 'text-success';
        }

        // Status הוגש ואין הגדרת ציון.
        if ($tmod->submitted && !$tmod->requiregrade) {
            $res->modstatus = get_string('complete', 'format_flexsections');
            $res->modstyle  = 'text-success';
            $res->modicon = $icons->done;
        }

        // Status הוגש וטרם נבדק.
        if ($tmod->submitted && $tmod->requiregrade && !$tmod->grade) {
            $res->modstatus = get_string('waitgrade', 'format_flexsections');
            $res->modstyle  = 'text-secondary';
            $res->modicon = $icons->waiting_for_grade;
        }

        // Status טרם התחיל.
        if (!$tmod->submitted && $tmod->cutoffdate && $tmod->cutoffdate > time()) {
            $delta = format_flexsections_convert_seconds_to_time($tmod->cutoffdate - time());

            // Date 4 ימים ויותר להגשה.
            if ($delta->days >= 4) {
                $a               = new stdClass();
                $a->date         = date("d/m/Y H:i", $tmod->cutoffdate);
                $res->modstatus = get_string('cut_of_date_label', 'format_flexsections', $a);
            }

            // Date במהלך 3 הימים האחרונים.
            if ($delta->days > 0 && $delta->days < 4) {
                $a = $delta->days . ' ' . get_string('days') . get_string('and', 'format_flexsections') .
                $delta->hours . ' ' . get_string('hours');
                $res->modstatus = get_string('cut_of_date_less_days_label', 'format_flexsections', $a);
            }

            // Date במהלך היום האחרון.
            if ($delta->days == 0) {
                $a               = $delta->hours . ' ' . get_string('hours');
                $res->modstatus = get_string('cut_of_date_less_days_label', 'format_flexsections', $a);
            }

            $res->modstyle = 'text-secondary';
            $res->modicon = $icons->waiting;
        }

        // Status ללא תאריך הגשה.
        if (!$tmod->submitted && $tmod->cutoffdate == 0) {
            $res->modstatus = get_string('no_submission_date', 'format_flexsections');
            $res->modstyle  = 'text-secondary';
            $res->modicon = $icons->waiting;
        }

        // Status לאחר תאריך הגשה סופי.
        if (!$tmod->submitted && $tmod->cutoffdate && $tmod->cutoffdate <= time()) {
            $res->modstatus = get_string('cut_of_date', 'format_flexsections');
            $res->modstyle  = 'text-danger';
            $res->modicon = $icons->over_due;
        }
    }

    return ['modstatus' => $res->modstatus, 'modstyle' => $res->modstyle, 'modicon' => $res->modicon];
}

/**
 * @param $seconds
 * @return stdClass
 */
function format_flexsections_convert_seconds_to_time($seconds) {

    $secondsInAMinute = 60;
    $secondsInAnHour  = 60 * $secondsInAMinute;
    $secondsInADay    = 24 * $secondsInAnHour;

    // Extract days.
    $days = floor($seconds / $secondsInADay);

    // Extract hours.
    $hourSeconds = $seconds % $secondsInADay;
    $hours       = floor($hourSeconds / $secondsInAnHour);

    // Extract minutes.
    $minuteSeconds = $hourSeconds % $secondsInAnHour;
    $minutes       = floor($minuteSeconds / $secondsInAMinute);

    // Extract the remaining seconds.
    $remainingSeconds = $minuteSeconds % $secondsInAMinute;
    $seconds          = ceil($remainingSeconds);

    // Return the final object.
    $obj          = new \stdClass();
    $obj->days    = (int) $days;
    $obj->hours   = (int) $hours;
    $obj->minutes = (int) $minutes;
    $obj->seconds = (int) $seconds;

    return $obj;
}

/**
 * Get student users of course.
 * @param int $courseid
 * @return array
 */
function format_flexsections_get_students_course($courseid) {
    global $DB;

    $sql = "SELECT u.id AS userid, CONCAT(u.firstname,' ',u.lastname) AS name
            FROM {user} u
            JOIN {user_enrolments} ue_d ON ue_d.userid = u.id
            JOIN {enrol} e_d ON (e_d.id = ue_d.enrolid AND e_d.courseid = ?)
            LEFT JOIN {role_assignments} ra ON ra.userid = u.id
            LEFT JOIN {context} ct ON ct.id = ra.contextid
            LEFT JOIN {course} c ON c.id = ct.instanceid
            LEFT JOIN {role} r ON r.id = ra.roleid
            WHERE r.shortname=? AND c.id=? AND u.suspended = 0 AND ue_d.status = 0
                AND (
                    (ue_d.timestart = '0' AND ue_d.timeend = '0') OR
                    (ue_d.timestart = '0' AND ue_d.timeend > UNIX_TIMESTAMP()) OR
                    (ue_d.timeend = '0' AND ue_d.timestart < UNIX_TIMESTAMP()) OR
                    (ue_d.timeend > UNIX_TIMESTAMP() AND ue_d.timestart < UNIX_TIMESTAMP())
                    )
            ";
    $students = $DB->get_records_sql($sql, array($courseid, 'student', $courseid));

    return array_values($students);
}

function format_flexsections_has_teacher_capability($cmid) {
    $context = \context_module::instance($cmid);

    if (is_siteadmin() || has_capability('moodle/course:update', $context)) {
        return true;
    }

    return false;
}

function format_flexsections_has_teacher_course_capability($courseid) {
    $context = \context_course::instance($courseid);

    if (is_siteadmin() || has_capability('moodle/course:update', $context)) {
        return true;
    }

    return false;
}

function format_flexsections_get_sub_sections_cmids(&$cmids, $sectionid): void {
    global $DB;

    if ($obj = $DB->get_record('course_sections', ['id' => $sectionid])) {
        $cmids = array_merge($cmids, explode(',', $obj->sequence));
        $modinfo = get_fast_modinfo($obj->course);

        // Subsections.
        foreach ($modinfo->get_section_info_all() as $num => $subsection) {
            if ($subsection->parent == $obj->section && $num != $obj->section) {
                format_flexsections_get_sub_sections_cmids($cmids, $subsection->id);
            }
        }

        $cmids = array_filter($cmids);
        $cmids = array_unique($cmids);
    }
}

function format_flexsections_recently_viewed_section($sectionid) {
    global $COURSE, $DB;

    $cache = \cache::make_from_params(\cache_store::MODE_APPLICATION, 'format_flexsections_cache', 'recently_viewed_sections');
    $cachekey = 'data';

    // Get from cache.
    if (($result = $cache->get($cachekey)) === false) {
        if ($obj = $DB->get_record('config', ['name' => 'format_flexsections_recently_viewed_sections'])) {
            $result =json_decode($obj->value, true);
        } else {
            $result = [];
        }

        $cache->set($cachekey, $result);
    }

    $context = \context_course::instance($COURSE->id);
    if (is_siteadmin() || has_capability('moodle/course:update', $context)) {
        if (isset($result[$sectionid]) && $result[$sectionid] >= 5) {
            return true;
        }
    }

    return false;
}

function format_flexsections_prepare_recently_viewed_section() {
    global $DB;

    $userspersections = [];
    $cache = \cache::make_from_params(\cache_store::MODE_APPLICATION, 'format_flexsections_cache', 'recently_viewed_sections');
    $cachekey = 'data';

    $cache->delete($cachekey);

    $delta = time() - 7*24*60*60;

    // Prepare data for user submit.
    $sql = "
        SELECT gg.`id`, gg.`userid`, gi.`courseid`, gi.`itemtype`, gi.`itemmodule`,gi.`iteminstance`
        FROM {grade_grades} gg
        LEFT JOIN {grade_items} gi ON (gi.id = gg.itemid)
        WHERE gg.`timecreated` > ?
    ";

    foreach ($DB->get_records_sql($sql, [$delta]) as $mod) {

        // Check user role.
        $context = \context_course::instance($mod->courseid);
        $roles = get_user_roles($context, $mod->userid);

        $ifstudent = false;
        $rolespermitted = ['student'];
        foreach ($roles as $role) {
            if (in_array($role->shortname, $rolespermitted)) {
                $ifstudent = true;
            }
        }

        if ($ifstudent && $mod->itemtype == 'mod') {
            $cm = get_coursemodule_from_instance($mod->itemmodule, $mod->iteminstance);
            $userspersections[$cm->section][] = $mod->userid;
        }
    }

    // Prepare data for user last acces in to section.
    $sql = "
        SELECT *
        FROM {flexsections_lastaccess}
        WHERE sectionid > 0 AND timeaccess > ?        
    ";

    foreach ($DB->get_records_sql($sql, [$delta]) as $item) {

            // Check user role.
            $context = \context_course::instance($item->courseid);
            $roles = get_user_roles($context, $item->userid);

            $ifstudent = false;
            foreach ($roles as $role) {
                if (in_array($role->shortname, ['student'])) {
                    $ifstudent = true;
                }
            }

            if ($ifstudent) {
                $section = $DB->get_record('course_sections', ['id' => $item->sectionid]);
                $userspersections[$section->id][] = $item->userid;
            }
    }

    // Prepare result.
    $data = [];
    foreach ($userspersections as $sectionid => $userids) {
        $newdata = [];
        format_flexsections_get_sub_sections_data($newdata, $sectionid, $userspersections);

        $data[$sectionid] = $newdata;
    }

    $result = [];
    foreach ($data as $sectionid => $userids) {
        $userids = array_unique($userids);
        $result[$sectionid] = count($userids);
    }

    // Update DB and cache.
    if ($obj = $DB->get_record('config', ['name' => 'format_flexsections_recently_viewed_sections'])) {
        $obj->value = json_encode($result);
        $DB->update_record('config', $obj);
    } else {
        $obj = new \StdClass();
        $obj->name = 'format_flexsections_recently_viewed_sections';
        $obj->value = json_encode($result);
        $DB->insert_record('config', $obj);
    }

    $cache->set($cachekey, $result);

    return true;
}

function format_flexsections_get_sub_sections_data(&$newdata, $sectionid, $data): void {
    global $DB;

    if ($obj = $DB->get_record('course_sections', ['id' => $sectionid])) {
        if (isset($data[$sectionid])) {
            $newdata = array_merge($newdata, $data[$sectionid]);
        }
        $modinfo = get_fast_modinfo($obj->course);

        // Subsections.
        foreach ($modinfo->get_section_info_all() as $num => $subsection) {
            if ($subsection->parent == $obj->section && $num != $obj->section) {
                format_flexsections_get_sub_sections_data($newdata, $subsection->id, $data);
            }
        }
    }
}
