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
 * @copyright 2022 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['accordion'] = 'Accordion effect';
$string['accordiondesc'] = 'When one section is expanded, collapse all others.';
$string['addsections'] = 'Add section';
$string['addsubsection'] = 'Add subsection';
$string['backtocourse'] = 'Back to course \'{$a}\'';
$string['backtosection'] = 'Back to \'{$a}\'';
$string['cmbacklink'] = 'Display back link in activities';
$string['cmbacklinkdesc'] = 'Display link \'Back to ...\' allowing to return to the course section inside the section activities.';
$string['confirmdelete'] = 'Are you sure you want to delete this section? All activities and subsections will also be deleted';
$string['confirmmerge'] = 'Are you sure you want to merge this section content with the parent? All activities and subsections will be moved';
$string['courseindexdisplay'] = 'Display course index';
$string['courseindexdisplaydesc'] = 'Defines how to display the course index on the left side of the course page.';
$string['courseindexfull'] = 'Sections and activities';
$string['courseindexnone'] = 'Do not display';
$string['courseindexsections'] = 'Only sections';
$string['currentsection'] = 'This section';
$string['deletesection'] = 'Delete section';
$string['displaycontent'] = 'Display content';
$string['editsection'] = 'Edit section';
$string['editsectionname'] = 'Edit section name';
$string['errorsectiondepthexceeded'] = 'Subsection depth has exceeded configured value.';
$string['hidefromothers'] = 'Hide section';
$string['maxsectiondepth'] = 'Max subsection depth';
$string['maxsectiondepthdesc'] = 'Maximum number of subsection levels.';
$string['mergeup'] = 'Merge with parent';
$string['moveassubsection'] = 'As a subsection of \'{$a}\'';
$string['movebeforecm'] = 'Before activity \'{$a}\'';
$string['movebeforesection'] = 'Before \'{$a}\'';
$string['movecmendofsection'] = 'To the end of section \'{$a}\'';
$string['movecmsection'] = 'To the section \'{$a}\'';
$string['moveendofsection'] = 'As the last subsection of \'{$a}\'';
$string['movesectiontotheend'] = 'To the end';
$string['newsectionname'] = 'New name for section {$a}';
$string['page-course-view-flexsections'] = 'Any course main page in Flexible sections format';
$string['page-course-view-flexsections-x'] = 'Any course page in Flexible sections format';
$string['pluginname'] = 'Flexible sections format';
$string['privacy:metadata'] = 'The Flexible sections format plugin does not store any personal data.';
$string['section0name'] = 'General';
$string['sectionname'] = 'Topic';
$string['showcollapsed'] = 'Display as a link';
$string['showexpanded'] = 'Display on the same page';
$string['showfromothers'] = 'Show section';
$string['showsection0title'] = 'Show top section title';
$string['showsection0title_help'] = 'When enabled, the general section will have a title and will be collapsible, same as it behaves in the Topics format.';
$string['showsection0titledefault'] = 'Show top section title by default';
$string['showsection0titledefaultdesc'] = 'This defines default setting that will be used for new and existing courses, it can be changed for individual courses in their settings.';

// Deprecated but still can be used in 4.0, to be removed when we have a branch for Moodle 4.1 or later.
$string['addsection'] = 'Add section';
$string['addsubsectionfor'] = 'Add subsection for \'{$a}\'';
$string['cancelmoving'] = 'Cancel moving \'{$a}\'';
$string['removemarker'] = 'Do not mark as current';
$string['setmarker'] = 'Mark as current';

$string['image'] = 'Image';
$string['editcard'] = 'Add image';
$string['editimage:resizefailed'] = 'Failed to resize the selected image. The card will use the image at it\'s original size. You can try re-uploading the image later.';

// Settings
$string['settings:name'] = 'Flexsections format settings';
$string['form:course:usedefault'] = 'Default ({$a})';

$string['form:course:hiddensections'] = 'Hidden sections';
$string['form:course:hiddensections_help'] = 'Whether hidden sections are displayed to students as not available (perhaps for a course in weekly format to indicate holidays) or are completely hidden.';

$string['form:course:showprogress'] = 'Section progress';
$string['form:course:showprogress:description'] = 'Whether to display progress within each section on the card';
$string['form:course:showprogress:show'] = 'Shown';
$string['form:course:showprogress:hide'] = 'Hidden';
$string['form:course:progressformat'] = 'Display progress as';
$string['form:course:progressformat:count'] = 'A count of items';
$string['form:course:progressformat:percentage'] = 'A percentage';
$string['form:course:progressmode'] = 'Progress mode';
$string['form:course:progressmode:circle'] = 'Circle';
$string['form:course:progressmode:line'] = 'Line';
$string['form:course:cardorientation'] = 'Card orientation';
$string['form:course:cardorientation:vertical'] = 'Vertical';
$string['form:course:cardorientation:horizontal'] = 'Horizontal';
$string['form:course:showsummary'] = 'Section summary';
$string['form:course:showsummary:showfull'] = 'Shown fully';
$string['form:course:showsummary:show'] = 'Shown';
$string['form:course:showsummary:hide'] = 'Hidden';
$string['form:course:showsummary_help'] = 'Whether to show the section summary on cards';
$string['form:course:section0'] = 'General section';
$string['form:course:section0_help'] = 'The general section is the first section in your course, which usually contains the course\'s announcements page. You can choose to have this visible either only on the course\'s main page, on top of the card deck, or visible on the main page and each individual section page.';
$string['form:course:section0:coursepage'] = 'Only show on the main course page';
$string['form:course:section0:allpages'] = 'Show on all pages, including individual sections';

$string['form:course:sectionview:cards'] = 'Cards';
$string['form:course:sectionview:list'] = 'List';
$string['form:course:sectionviewoption'] = 'Show section as';

$string['lastseen'] = 'You have recently viewed this unit'; // צפית ביחידה זו לאחרונה
$string['section:completion:percentage'] = 'Completed {$a->percentage}%'; //'הושלמו {a->percentage}%';
$string['section:completion:count'] = '{$a->completed} from {$a->total}';
$string['course:completion:percentage'] = '{$a->percentage}% of the course has been completed'; //'הושלמו {a->percentage}%';
$string['course:completion:count'] = '{$a->completed} from {$a->total}';

// Progress bar
$string['quizinprogress'] = 'In progress';
$string['quizwithgrades'] = 'Graded';
$string['quizsubmittedwitgrades'] = 'Submitted and graded';
$string['quizsubmitted'] = 'Submitted';
$string['quizwithoutgrades'] = 'Needs grading';
$string['quiznosubmit'] = 'Not submitted';
$string['quizwithoutstarted'] = 'Not started';
$string['assignsubmitted'] = 'Submitted';
$string['assignhavegrade'] = 'Graded';
$string['assignnotsubmitted'] = 'Not submitted';
$string['questionnairesubmitted'] = 'Submitted';
$string['questionnairenotsubmitted'] = 'Not started';
$string['hvphavegrade'] = 'Graded';
$string['hvpnotsubmitted'] = 'Not submitted';
$string['studentfailed'] = 'Failed {$a} students';
$string['no_submission_date'] = 'No submission date';
$string['complete'] = 'Complete';
$string['waitgrade'] = 'Submitted and not yet reviewed';
$string['cut_of_date'] = 'Not submitted';
$string['cut_of_date_label'] = 'Submit until {$a->date}';
$string['cut_of_date_less_days_label'] = 'Submit within ';

$string['editimage'] = 'Edit image';

// Student status.
$string['statuswaitingforsubmission'] = 'activities are waiting for your submission';
$string['statusfailed'] = 'activities in which you fail';
$string['statusnotsubmittedintime'] = 'activities were not submitted on time';
$string['statuscmwaitingforsubmission'] = 'activities are waiting for check';
$string['statuscmfailed'] = 'activities were students failed';
$string['cmlastaccess'] = 'Last activity you watched in this unit:';

// Collapse button.
$string['collapsebuttonopen'] = 'Full view';
$string['collapsebuttonclose'] = 'Collapse all';
$string['loading'] = 'Loading activity list...';

// Task.
$string['taskrecentlyviewedsections'] = 'Recently viewed sections';

// Popup upload image.
$string['uploadimage'] = 'Upload image';
$string['cancel'] = 'Cancel';
$string['upload'] = 'Upload';
