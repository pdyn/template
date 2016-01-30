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
 * Template class that uses HTML templates.
 */
class HtmlTemplate implements TemplateInterface {

	/** @var array List of files in the form 'uniq_name' => 'filename'. */
	protected $files = [];

	/** @var string Directory of all templates files. */
	protected $tpldir = null;

	/** @var array Holding array for rendered templates to allow for re-use. */
	protected $rendered_tpl = [];

	/** @var array Holding array for unrendered template code, ready for processing. */
	protected $raw_tpl = [];

	/** @var array Holding array for root-level template variables. */
	protected $rootvars = [];

	/** @var array Holding array for section variables. */
	public $sectvars = [];

	/** @var array Holding array for section count.. */
	public $sectcount = [];

	/** @var string Variable namespace separator. */
	protected $var_ns_separator = ':';

	/** @var string Regex for extracting sections. */
	protected $regex_sect_extractor = '#<!-- (BEGIN|SWITCH) (.+) -->(.*)<!-- (END) \\2 -->#iuUs';

	/** @var string Cache-array of parsed templates. */
	protected $codesplit = [];

	/** @var array Array of non-heirarichal sections that have been enabled. */
	protected $switches = [];

	/**
	 * Constructor.
	 *
	 * @param string $tpl The name of the template set to use.
	 * @param string $tpldir The full-path to where all template sets are stored.
	 */
	public function __construct($tpldir, $fallbacktpldir = '') {
		$this->tpldir = $tpldir;
		$this->fallbacktpldir = (!empty($fallbacktpldir)) ? $fallbacktpldir : $tpldir;
	}

	/**
	 * Get a set variable.
	 *
	 * @param string $name Variable name.
	 * @return mixed Variable value or false.
	 */
	public function __get($name) {
		return (isset($this->$name)) ? $this->$name : false;
	}

	/**
	 * Determine whether an internal variable is set.
	 *
	 * @param string $name The name of the variable.
	 * @return bool Whether the variable is set or not.
	 */
	public function __isset($name) {
		return (isset($this->$name)) ? true : false;
	}

	/**
	 * Get the full filename and path of a file within the active template or the fallback.
	 *
	 * @param string $file A file and path relative to the template root.
	 * @return string The full, absolute path to the file in either the active or fallback templates. If exists.
	 */
	public function file($file) {
		$activetplfile = $this->tpldir.$file;
		$fallbackfile = $this->fallbacktpldir.$file;

		if (file_exists($activetplfile)) {
			return $activetplfile;
		} elseif (file_exists($fallbackfile)) {
			return $fallbackfile;
		} else {
			throw new \Exception('Could not find "'.$file.'" in active or fallback template.', 500);
		}
	}

	/**
	 * Assign template files.
	 *
	 * @param array $files An array of template files indexed by an alias.
	 * @return bool Success/Failure.
	 */
	public function assign_files(array $files) {
		foreach ($files as $tname => $filename) {
			if (mb_strpos($filename, '/') === 0) {
				if (file_exists($filename)) {
					$this->files[$tname] = $filename;
				} else {
					throw new \Exception('Could not find template file "'.$filename.'".');
				}
			} else {
				$this->files[$tname] = $this->file('/'.$filename);
			}
		}
		return true;
	}

	/**
	 * Get the template file assigned to a alias.
	 *
	 * @param string $tname The file alias.
	 * @return string|null The file, or null.
	 */
	public function get_assigned_file($tname) {
		if (isset($this->files[$tname])) {
			return $this->files[$tname];
		} else {
			return null;
		}
	}

	/**
	 * Clear all root and section assignments.
	 */
	public function reset_vars() {
		$this->reset_rootvars();
		$this->reset_sectvars();
	}

	/**
	 * Remove a root-level section and clear the rendered template cache.
	 *
	 * Note: This currently only works for root-level sections.
	 *
	 * @param string $sect The name of the section.
	 */
	public function reset_sectvars($sect = null) {
		if (!empty($sect)) {
			$sect = '.'.$sect;
			foreach ($this->sectcount as $k => $v) {
				if ($sect === $k) {
					unset($this->sectcount[$sect]);
				}
				if (mb_strpos($k, $sect.'{') === 0) {
					unset($this->sectcount[$k]);
				}
			}
			foreach ($this->sectvars as $k => $v) {
				if ($sect === $k) {
					unset($this->sectvars[$sect]);
				}
				if (mb_strpos($k, $sect.'{') === 0) {
					unset($this->sectvars[$k]);
				}
			}
		} else {
			$this->sectcount = [];
			$this->sectvars = [];
		}
		$this->clear_rendered_tpl_cache();
		$this->codesplit = [];
	}

	/**
	 * Clear all assigned root-level variables.
	 */
	public function reset_rootvars() {
		$this->rootvars = array();
		$this->clear_rendered_tpl_cache();
	}

	/**
	 * Clear all rendered template caches.
	 */
	public function clear_rendered_tpl_cache($tname = null) {
		if (!empty($tname)) {
			if (isset($this->rendered_tpl[$tname])) {
				unset($this->rendered_tpl[$tname]);
			}
			if (isset($this->codesplit[$tname])) {
				unset($this->codesplit[$tname]);
			}
		} else {
			$this->rendered_tpl = array();
			$this->codesplit = array();
		}
	}

	/**
	 * Assign a single root-level variable.
	 *
	 * @param string $key The variable key.
	 * @param string $val The variable value.
	 * @return bool Success/Failure.
	 */
	public function assign_var($key, $val) {
		$this->rootvars = array_merge($this->rootvars, array('{'.$key.'}'=>$val));
		return true;
	}

	/**
	 * Assign root-level variables.
	 *
	 * These will be available throughout the template.
	 *
	 * @param array $vars Key-value array of variable names and values.
	 * @param string $ns An optional prefix to add to all variables.
	 * @return bool Success/Failure.
	 */
	public function assign_vars(array $vars, $ns = null) {
		if (empty($vars)) {
			return false;
		}

		if (!empty($ns)) {
			$ns .= $this->var_ns_separator;
		}

		$ks = array_keys($vars);
		array_walk(
			$ks,
			function(&$v, &$i) use ($ns) {
				$v = '{'.$ns.$v.'}';
			}
		);
		$newvars = array_combine($ks, $vars);
		foreach ($newvars as $k => $v) {
			if (!is_scalar($v)) {
				unset($newvars[$k]);
			}
		}
		$this->rootvars = array_merge($this->rootvars, $newvars);
		return true;
	}

	/**
	 * Determine whether a particular section is assigned.
	 *
	 * @param string $sect The fully-qualified section name.
	 * @return bool Whether the section is assigned or not.
	 */
	public function sect_assigned($sect) {
		$pattern = str_replace('.', '(?:\{[0-9]+\})?\.', $sect);
		$pattern = '#'.$pattern.'#';
		foreach ($this->sectcount as $k => $v) {
			$nummatch = preg_match($pattern, $val);
			if (!empty($nummatch)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Assigns a non-heirarichal section.
	 * @param string $name The name of the switch.
	 */
	public function assign_switch($name) {
		$this->switches[$name] = true;
	}

	/**
	 * Assign a new template section.
	 *
	 * @param string $sectname The name of the section.
	 * @param array $vars An array of section variables
	 */
	public function assign_sect($sectname, array $vars = array()) {
		$sectparts = explode('.', $sectname);
		$lastindex = count($sectparts) - 1;

		$curpath = '';
		foreach ($sectparts as $i => $sect) {
			$curpath .= '.'.$sect;
			$loop = (isset($this->sectcount[$curpath])) ? $this->sectcount[$curpath] : 0;

			if ($i === $lastindex) {
				$loop++;
				$this->sectcount[$curpath] = $loop;
			} else {
				if ($loop === 0) {
					$loop = 1;
					$this->sectcount[$curpath] = $loop;
				}
			}
			$curpath .= '{'.$loop.'}';
		}

		foreach ($vars as $k => $v) {
			$this->sectvars[$curpath]['{'.$sectname.'.'.$k.'}'] = $v;
		}
	}

	/**
	 * Read a template file and store the raw template in the object.
	 *
	 * @param string $t_name The alias of the template to import. Must have already been assigned to a file using assign_files()
	 */
	protected function import_tpl($t_name) {
		if (empty($this->files[$t_name])) {
			throw new \Exception('Template file "'.$t_name.'" could not be found.', 500);
		}
		if (!is_readable($this->files[$t_name])) {
			throw new \Exception($t_name.' ('.$this->files[$t_name].') could not be read.', 500);
		}
		$this->raw_tpl[$t_name] = @file_get_contents($this->files[$t_name]);
	}

	/**
	 * Render a section.
	 *
	 * @param string $path The path to the current section, not including the section name.
	 *                     For example, if we're rendering 'one.two.three', this would be 'one.two'
	 * @param string $code The template HTML for this section.
	 * @param string $t_name The template name to use.
	 * @param bool $switch Whether we are rendering a switch or not.
	 * @return string The rendered section.
	 */
	protected function render_sect($path, $code, $t_name = '.', array $parentvars = array(), $switch = false) {
		$rendered = '';

		if ($switch === true) {
			$loopcount = 1;
		} else {
			if (!isset($this->sectcount[$path])) {
				return $rendered;
			}
			$loopcount = $this->sectcount[$path];
		}

		$codecrc = crc32($code);
		if (!isset($this->codesplit[$t_name][$codecrc])) {
			$this->codesplit[$t_name][$codecrc] = preg_split($this->regex_sect_extractor, $code, null, PREG_SPLIT_DELIM_CAPTURE);
		}
		$subsects =& $this->codesplit[$t_name][$codecrc];
		$subsectscount = count($subsects);

		for ($loop = 1; $loop <= $loopcount; $loop++) {
			$thisloopcode = '';

			// Add vars.
			$vars = [];
			$varpath = ($path{(strlen($path) - 1)} === '}') ? $path : $path.'{'.$loop.'}';
			if (!empty($this->sectvars[$varpath])) {
				$vars = array_merge($parentvars, $this->sectvars[$varpath]);
			}

			for ($subsecti = 0; $subsecti < $subsectscount; $subsecti++) {
				$subsect =& $subsects[$subsecti];
				if ($subsect === 'BEGIN') {
					// Process a section.
					$subsectname = $subsects[$subsecti + 1];
					$subsectpath = $path;
					if (substr($subsectpath, -1) !== '}') {
						$subsectpath .= '{'.$loop.'}';
					}
					$subsectpath .= '.'.$subsectname;
					if (!empty($this->sectcount[$subsectpath])) {
						$thisloopcode .= $this->render_sect($subsectpath, $subsects[$subsecti + 2], $t_name, $vars);
					}
					$subsecti += 3;
				} elseif ($subsect === 'SWITCH') {
					if (isset($this->switches[$subsects[$subsecti + 1]])) {
						$subpath = (substr($path, -1) !== '}') ? $path.'{'.$loop.'}' : $path;
						$thisloopcode .= $this->render_sect($subpath, $subsects[$subsecti + 2], $t_name, $parentvars, true);
					}
					$subsecti += 3;
				} else {
					$thisloopcode .= trim($subsect);
				}
			}
			if (!empty($vars)) {
				$thisloopcode = trim(str_replace(array_keys($vars), array_values($vars), $thisloopcode));
			}
			$rendered .= $thisloopcode;
		}

		return $rendered;
	}

	/**
	 * Render a template.
	 *
	 * @param string $t_name A template alias to render. Alias must have already been assigned with assign_files.
	 * @return string The rendered template.
	 */
	protected function render($t_name) {
		$rendered = '';
		$tplcode = $this->raw_tpl[$t_name];
		$tfile = $this->files[$t_name];
		$matchfunc = function($matches) use ($tfile) {
			$tpldir = dirname($tfile);
			$includefile = str_replace(['../', './'], '', $matches[1]);
			$includefile = $tpldir.'/'.$includefile;
			return (file_exists($includefile)) ? file_get_contents($includefile) : '';
		};
		$tplcode = preg_replace_callback('#{INCLUDE:(.+)}#iUs', $matchfunc, $tplcode);

		$parts = preg_split($this->regex_sect_extractor, $tplcode, null, PREG_SPLIT_DELIM_CAPTURE);

		// Go through root level sections.
		$num_parts = count($parts);
		for ($i = 0; $i < $num_parts; $i++) {
			if ($parts[$i] === 'BEGIN') {
				$rendered .= $this->render_sect('.'.$parts[$i + 1], $parts[$i + 2], $t_name);
				unset($parts[$i], $parts[$i + 1], $parts[$i + 2], $parts[$i + 3]);
				$i += 3;
			} elseif ($parts[$i] === 'SWITCH') {
				if (isset($this->switches[$parts[$i + 1]])) {
					$rendered .= $this->render_sect('.', $parts[$i + 2], $t_name, [], true);
				}
				unset($parts[$i], $parts[$i + 1], $parts[$i + 2], $parts[$i + 3]);
				$i += 3;
			} else {
				// Root level content - do var replacement.
				$rendered .= $parts[$i];
				unset($parts[$i]);
			}
		}

		// Replace rootvars.
		$rendered = str_replace(array_keys($this->rootvars), $this->rootvars, $rendered);
		return $rendered;
	}

	/**
	 * Render and display a template file.
	 *
	 * @param string $t_name The alias of a template file to render.
	 * @param bool $return If true, will return the rendered template. Otherwise will echo directly.
	 * @return string Returns rendered template if $return is true.
	 */
	public function display($t_name, $return = false) {
		// Import and render template, or use cache if available.
		if (!isset($this->rendered_tpl[$t_name])) {
			$this->import_tpl($t_name);
			$this->rendered_tpl[$t_name] = $this->render($t_name);
		}

		if ($return === false) {
			echo $this->rendered_tpl[$t_name];
			return '';
		} else {
			return $this->rendered_tpl[$t_name];
		}
	}
}
