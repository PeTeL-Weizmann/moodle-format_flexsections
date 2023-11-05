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
 * Javascript for lazy load of statuses.
 *
 * @package
 * @copyright  2020 Devlion <info@devlion.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
    [
        'jquery',
        'core/ajax',
        'local_petel/inview',
        'core/notification'
    ],
    function($, Ajax, inView, Notification) {
        var courseid = null;

        let getStatus = function(cmids) {
            if (courseid) {
                Ajax.call([{
                    methodname: 'format_flexsections_get_activity_grade_status',
                    args: {
                        cmids: cmids,
                        courseid: courseid
                    },
                    done: function(resp) {
                        let response = JSON.parse(resp);
                        response.result.forEach(function(item) {
                            $('#activity-row-grade-' + item.cmid).html(item.gradestatus);
                            $('#activity-oer-version-' + item.cmid).html(item.oerversion);
                        });
                    },
                    fail: Notification.exception
                }]);
            }
        };
        let inview = function() {
            let stack = [];
            inView('.inviewgrade')
                .on('enter', function(e) {
                    if (!$(e).hasClass('inview-done')) {
                        $(e).addClass('inview-done');
                        let cmid = $(e).attr("data-cmid");
                        courseid = $(e).attr("data-courseid");

                        stack.push(cmid);
                    }
                });

            setInterval(function() {
                if (stack.length > 0) {
                    let cmids = JSON.stringify(stack);
                    getStatus(cmids);
                    stack = [];
                }
            }, 500);

        };
        return {
            inview: inview,
        };
    });
