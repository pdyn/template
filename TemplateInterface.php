<?php
/*
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * @copyright 2010 onwards James McQuillan (http://pdyn.net)
 * @author James McQuillan <james@pdyn.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace pdyn\template;

/**
 * Interface for Template classes.
 */
interface TemplateInterface {

	/**
	 * Constructor.
	 *
	 * @param string $tpl The name of the template set to use.
	 * @param string $tpl_dir The full-path to where all template sets are stored.
	 */
	public function __construct($tpl, $tpl_dir);

	/**
	 * Assign template files.
	 *
	 * @param array $files An array of template files indexed by an alias.
	 * @return bool Success/Failure.
	 */
	public function assign_files(array $files);

	/**
	 * Get the template file assigned to a alias.
	 *
	 * @param string $tname The file alias.
	 * @return string|null The file, or null.
	 */
	public function get_assigned_file($tname);

	/**
	 * Clear all root and section assignments.
	 */
	public function reset_vars();

	/**
	 * Unassign a section.
	 *
	 * @param string $sect The name of the section.
	 */
	public function reset_sectvars($sect = null);

	/**
	 * Clear all assigned root-level variables.
	 */
	public function reset_rootvars();

	/**
	 * Clear all rendered template caches.
	 */
	public function clear_rendered_tpl_cache();

	/**
	 * Assign a single root-level variable.
	 *
	 * @param string $key The variable key.
	 * @param string $val The variable value.
	 * @return bool Success/Failure.
	 */
	public function assign_var($key, $val);

	/**
	 * Assign root-level variables.
	 *
	 * These will be available throughout the template.
	 *
	 * @param array $vars Key-value array of variable names and values.
	 * @param string $ns An optional prefix to add to all variables.
	 * @return bool Success/Failure.
	 */
	public function assign_vars(array $vars, $ns = null);

	/**
	 * Determine whether a particular section is assigned.
	 *
	 * @param string $s_name The fully-qualified section name.
	 * @return bool Whether the section is assigned or not.
	 */
	public function sect_assigned($s_name);

	/**
	 * Assign a new template section.
	 *
	 * @param string $s_name The name of the section.
	 * @param array $vars An array of section variables
	 */
	public function assign_sect($s_name, array $vars = array());

	/**
	 * Assigns a non-heirarichal section.
	 * @param string $name The name of the switch.
	 */
	public function assign_switch($name);

	/**
	 * Render and display a template file.
	 *
	 * @param string $t_name The alias of a template file to render.
	 * @param bool $return If true, will return the rendered template. Otherwise will echo directly.
	 * @return string Returns rendered template if $return is true.
	 */
	public function display($t_name, $return = false);
}
