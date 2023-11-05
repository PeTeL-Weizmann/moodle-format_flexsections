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
 * Javascript for collapse button.
 *
 * @package
 * @copyright  2020 Devlion <info@devlion.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
    [
        'jquery',
        'core/str'
    ],
    function($, Str) {

        const collapseAction = (type) => {
            $('.course-section').each(function() {
                let number = $(this).data('number');

                if (number > 0) {
                    let id = '#collapssesection' + number;
                    let status = $(id).attr("aria-expanded");

                    if ((type === 'open' && status === 'false') || (type === 'close' && status === 'true')) {
                        $(id).click();
                    }
                }
            });
        };

        const getHashNumber = () => {
            let queryString = window.location;
            let urlParams = new URLSearchParams(queryString);
            let hash = urlParams.get('hash');

            if (hash.length > 0) {
                return hash.match(/\d+/)[0];
            } else {
                return false;
            }
        };

        const openHash = () => {
            let number = getHashNumber();
            if (number !== false) {
                let id = '#collapssesection' + number;
                let status = $(id).attr("aria-expanded");

                $('.course-section').each(function() {
                    let number2 = $(this).data('number');

                    let id2 = '#collapssesection' + number2;
                    let status2 = $(id2).attr("aria-expanded");

                    if (id !== id2 && status2 === 'true') {
                        $(id2).click();
                    }

                    if (id === id2 && status === 'false') {
                        $(id2).click();
                    }
                });
            }
        };

        return {
            init: function() {

                // Default state.
                setTimeout(function() {
                    if (getHashNumber() !== false) {
                        openHash();
                    } else {
                        collapseAction('open');
                    }
                }, 700);

                // Change in url string. Hash.
                window.addEventListener('hashchange', function() {
                    openHash();
                });

                Str.get_strings([
                    {key: 'collapsebuttonopen', component: 'format_flexsections'},
                    {key: 'collapsebuttonclose', component: 'format_flexsections'}
                ]).done(function(strings) {

                    $('#collapse_button').on("click", function() {
                        let action = $(this).data('action');

                        switch (action) {
                            case 'close':
                                $(this).data('action', 'open').text(strings[1]);
                                collapseAction('open');
                                break;

                            case 'open':
                                $(this).data('action', 'close').text(strings[0]);
                                collapseAction('close');
                                break;

                            default:
                                $(this).data('action', 'close').text(strings[0]);
                        }
                    });
                });
            },
        };
    });
