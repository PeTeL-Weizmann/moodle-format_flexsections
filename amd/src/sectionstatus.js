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

// import Section from 'core_courseformat/local/content/section';

/**
 * Course section format component.
 *
 * @module     format_flexsections/sectionstatus
 * @copyright  2023 Alex P devlion.co
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/notification', 'core/templates'],
    function(Ajax, Notification, Templates) {
        return {
            init: function(sectionid) {
                this.getSectionStatus(sectionid);
            },
            renderSectionStatus: function(data) {

                Templates.renderForPromise('format_flexsections/sectionstatus', data)

                // It returns a promise that needs to be resoved.
                .then(({html, js}) => {
                    Templates.appendNodeContents('#sectionStatus', html, js);
                })

                // Deal with this exception (Using core/notify exception function is recommended).
                .catch(() => Notification.exception);
            },
            getSectionStatus: function(sectionid) {

                const request = {
                    methodname: 'format_flexsections_get_section_status',
                    args: {
                        sectionid: sectionid,
                    }
                };

                Ajax.call([request])[0]
                    .done((data) => {
                        this.renderSectionStatus(JSON.parse(data));
                    })
                    .fail(Notification.exception);
            },
        };
    });
