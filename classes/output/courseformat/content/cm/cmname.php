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
 * Contains the default activity name inplace editable.
 *
 * @package   core_courseformat
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_flexsections\output\courseformat\content\cm;

use stdClass;

/**
 * Base class to render a course module inplace editable header.
 *
 * @package   core_courseformat
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cmname extends \core_courseformat\output\local\content\cm\cmname {

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): array {
        $mod = $this->mod;
        $displayoptions = $this->displayoptions;

        if (!$this->has_name()) {
            // Nothing to be displayed to the user.
            return [];
        }

        $data = [
            'url' => $mod->url->out(),
            'icon' => $mod->get_icon_url()->out(),
            'modname' => $mod->modname,
            'textclasses' => $displayoptions['textclasses'] ?? '',
            'purpose' => plugin_supports('mod', $mod->modname, FEATURE_MOD_PURPOSE, MOD_PURPOSE_OTHER),
            'activityname' => $this->get_title_data($output),
        ];

        if ($this->format->show_editor()) {
            $data['pluginname'] = get_string('pluginname', 'mod_' . $mod->modname);
        }

        return $data;
    }
}
