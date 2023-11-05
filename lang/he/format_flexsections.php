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
 * Strings for component Flexible sections course format.
 *
 * @package   format_flexsections
 * @copyright 2023 Devlion.co
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addsections'] = 'הוספת יחידת הוראה';
$string['addsubsection'] = 'הוספת תת יחידת הוראה';
$string['backtocourse'] = 'חזרה לעמוד קורס ראשי \'{$a}\'';
$string['backtosection'] = 'חזרה ל \'{$a}\'';
$string['confirmdelete'] = 'Are you sure you want to delete this section? All activities and subsections will also be deleted';
$string['confirmmerge'] = 'Are you sure you want to merge this section content with the parent? All activities and subsections will be moved';
$string['currentsection'] = 'יחידת הוראה זו';
$string['deletesection'] = 'מחיקת יחידת הוראה';
$string['displaycontent'] = 'תצוגת תוכן';
$string['editsection'] = 'עריכת יחידת הוראה';
$string['editsectionname'] = 'עריכת שם יחידת הוראה';
$string['hidefromothers'] = 'הסתרת יחידת הוראה';
$string['mergeup'] = 'שילוב יחידה זו ביחידה הראשית';
$string['moveassubsection'] = 'כתת יחידה של \'{$a}\'';
$string['movebeforecm'] = 'לפני פעילות \'{$a}\'';
$string['movebeforesection'] = 'לפני \'{$a}\'';
$string['movecmendofsection'] = 'בסוף יחידת ההוראה \'{$a}\'';
$string['movecmsection'] = 'ליחידת הוראה \'{$a}\'';
$string['moveendofsection'] = 'לתת יחידה האחרונה של \'{$a}\'';
$string['movesectiontotheend'] = 'לסוף';
$string['newsectionname'] = 'שם חדש ליחידת הוראה {$a}';
$string['page-course-view-flexsections'] = 'Any course main page in Flexible sections format';
$string['page-course-view-flexsections-x'] = 'Any course page in Flexible sections format';
$string['pluginname'] = 'יחידות ותתי יחידות בתמונות';
$string['privacy:metadata'] = 'The Flexible sections format plugin does not store any personal data.';
$string['section0name'] = 'מבוא';
$string['sectionname'] = 'יחידת הוראה';
$string['showcollapsed'] = 'תצוגה כקישור לעמוד עצמאי';
$string['showexpanded'] = 'תצוגה באופן מלא';
$string['showfromothers'] = 'תצוגת יחידת הוראה';

// Deprecated but still can be used in 4.0, to be removed when we have a branch for Moodle 4.1 or later.
$string['addsection'] = 'הוספת יחידת הוראה';
$string['addsubsectionfor'] = 'הוספת תת יחידת הוראה ל \'{$a}\'';
$string['cancelmoving'] = 'ביטול העברת \'{$a}\'';
$string['removemarker'] = 'ביטול סימון יחידה בלמידה';
$string['setmarker'] = 'סימון כיחידת בלמידה';

$string['image'] = 'תמונה';
$string['editcard'] = 'הוספת תמונה';
$string['editimage:resizefailed'] = 'Failed to resize the selected image. The card will use the image at it\'s original size. You can try re-uploading the image later.';

// Settings
$string['settings:name'] = 'Flexsections format settings';
$string['form:course:usedefault'] = 'בררת מחדל ({$a})';

$string['form:course:hiddensections'] = 'הסתרת יחידות הוראה';
$string['form:course:hiddensections_help'] = 'Whether hidden sections are displayed to students as not available (perhaps for a course in weekly format to indicate holidays) or are completely hidden.';

$string['form:course:showprogress'] = 'מעקב השלמה';
$string['form:course:showprogress:description'] = 'Whether to display progress within each section on the card';
$string['form:course:showprogress:show'] = 'מוצג';
$string['form:course:showprogress:hide'] = 'מוסתר';
$string['form:course:progressformat'] = 'תצורת תצוגת השלמה';
$string['form:course:progressformat:count'] = 'מספר פעילויות שהושלמו';
$string['form:course:progressformat:percentage'] = 'אחוז פעיליות שהושלמו';
$string['form:course:progressmode'] = 'אופן תצוגת השלמה';
$string['form:course:progressmode:circle'] = 'גרף עגול';
$string['form:course:progressmode:line'] = 'סרגל השלמה';
$string['form:course:cardorientation'] = 'עימוד תמונת יחידת הוראה';
$string['form:course:cardorientation:vertical'] = 'מאונך';
$string['form:course:cardorientation:horizontal'] = 'מאוזן';
$string['form:course:showsummary'] = 'תקציר יחידת הוראה';
$string['form:course:showsummary:showfull'] = 'מוצג באופן מלא';
$string['form:course:showsummary:show'] = 'מוצג באופן מקוצר';
$string['form:course:showsummary:hide'] = 'מוסתר';
$string['form:course:showsummary_help'] = 'האם להציג את תקציר היחידה על התמונה';
$string['form:course:section0'] = 'יחידת מבוא';
$string['form:course:section0_help'] = 'The general section is the first section in your course, which usually contains the course\'s announcements page. You can choose to have this visible either only on the course\'s main page, on top of the card deck, or visible on the main page and each individual section page.';
$string['form:course:section0:coursepage'] = 'תוצג בעמוד קורס הראשי בלבד';
$string['form:course:section0:allpages'] = 'תוצג בכל עמודי ותת עמודי הקורס';

$string['form:course:sectionview:cards'] = 'כרטיסים (תמונות)';
$string['form:course:sectionview:list'] = 'רשימה';
$string['form:course:sectionviewoption'] = 'תצוגת יחידת הוראה';

$string['lastseen'] = 'צפית ביחידה זו לאחרונה';
$string['section:completion:percentage'] = 'הושלמו {$a->percentage}%';
$string['section:completion:count'] = 'הושלם {$a->completed} מתוך {$a->total}';
$string['course:completion:percentage'] = 'הושלמו {$a->percentage}%';
$string['course:completion:count'] = 'הושלם {$a->completed} מתוך {$a->total}';

$string['quizinprogress'] = 'בתהליך';
$string['quizwithgrades'] = 'הוגש וניתן ציון';
$string['quizsubmittedwitgrades'] = 'הוגש וניתן ציון';
$string['quizsubmitted'] = 'הוגש';
$string['quizwithoutgrades'] = 'ממתין לבדיקה';
$string['quiznosubmit'] = 'טרם הוגש';
$string['quizwithoutstarted'] = 'טרם הגיש';
$string['assignsubmitted'] = 'ממתין לבדיקה';
$string['assignhavegrade'] = 'הוגש וניתן ציון';
$string['assignnotsubmitted'] = 'טרם הוגש';
$string['questionnairesubmitted'] = 'הגישו';
$string['questionnairenotsubmitted'] = 'טרם התחילו';
$string['hvphavegrade'] = 'הוגש וניתן ציון';
$string['hvpnotsubmitted'] = 'טרם הוגש';
$string['studentfailed'] = '{$a} תלמידים נכשלו';
$string['cut_of_date'] = 'לא הוגש';
$string['cut_of_date_label'] = 'להגיש עד {$a->date}';
$string['cut_of_date_less_days_label'] = '<span>להגיש תוך {$a} <i class="fa fa-exclamation-circle red" style="color: red" aria-hidden="true"></i> </span>';
$string['no_submission_date'] = 'ללא תאריך הגשה';
$string['complete'] = 'הושלם';
$string['waitgrade'] = 'הוגש וטרם נבדק';
$string['editimage'] = 'עריכת תמונה';

// Student status.
$string['statuswaitingforsubmission'] = 'פעילויות ממתינות להגשה שלך';
$string['statusfailed'] = 'פעילויות בהן נכשלת';
$string['statusnotsubmittedintime'] = 'פעילויות לא הוגשו בזמן';
$string['statuscmwaitingforsubmission'] = 'פעילויות ממתינות לבדיקה';
$string['statuscmfailed'] = 'פעילויות בהן תלמידים נכשלו';
$string['cmlastaccess'] = 'פעילות אחרונה בה צפיתם ביחידה הזו:';

// Collapse button.
$string['collapsebuttonopen'] = 'תצוגה מלאה';
$string['collapsebuttonclose'] = 'תצוגה מצומצמת';
$string['loading'] = 'תוכן היחידה והפעילויות בטעינה...';

// Task.
$string['taskrecentlyviewedsections'] = 'צפית ביחידה לאחרונה';

// Popup upload image.
$string['uploadimage'] = 'העלאת תמונה';
$string['cancel'] = 'ביטול';
$string['upload'] = 'העלה';
