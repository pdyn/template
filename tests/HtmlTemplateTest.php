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

namespace pdyn\template\tests;

require_once(__DIR__.'/../TemplateInterface.php');
require_once(__DIR__.'/../HtmlTemplate.php');

/**
 * A mock HtmlTemplate implementation allowing access to all protected properties/methods.
 */
class MockHtmlTemplate extends \pdyn\template\HtmlTemplate {
	/** @var array Holding array for rendered templates to allow for re-use. */
	public $rendered_tpl;

	/** @var array List of files in the form 'uniq_name' => 'filename'. */
	public $files;

	/** @var array Holding array for unrendered template code, ready for processing. */
	public $raw_tpl;

	/**
	 * Magic method run protected/private methods.
	 *
	 * @param string $name The called method name.
	 * @param array $arguments Array of arguments.
	 */
	public function __call($name, $arguments) {
		if (method_exists($this, $name)) {
			return call_user_func_array([$this, $name], $arguments);
		}
	}

	/**
	 * Magic method run protected/private static methods.
	 *
	 * @param string $name The called method name.
	 * @param array $arguments Array of arguments.
	 */
	public static function __callStatic($name, $arguments) {
		$class = get_called_class();
		if (method_exists($class, $name)) {
			return forward_static_call_array([$class, $name], $arguments);
		}
	}

	/**
	 * Magic isset function inspect protected/private properties.
	 *
	 * @param string $name The name of the property.
	 * @return bool Whether the property is set.
	 */
	public function __isset($name) {
		return (isset($this->$name)) ? true : false;
	}

	/**
	 * Magic unset function to unset protected/private properties.
	 *
	 * @param string $name The name property to unset.
	 */
	public function __unset($name) {
		if (isset($this->$name)) {
			unset($this->$name);
		}
	}

	/**
	 * Get the value of a protected/private property.
	 *
	 * @param string $name The name of the property.
	 * @return mixed The value.
	 */
	public function __get($name) {
		return (isset($this->$name)) ? $this->$name : false;
	}

	/**
	 * Set the value of a protected/private property.
	 *
	 * @param string $name The name of the property.
	 * @param mixed $val The value to set.
	 */
	public function __set($name, $val) {
		$this->$name = $val;
	}
}

/**
 * Test HtmlTemplate
 * @group pdyn
 * @group pdyn_template
 */
class HtmlTemplateTest extends \PHPUnit_Framework_TestCase {
	/**
	 * Test assign_files method.
	 */
	public function test_assign_files() {

		$TPL = new MockHtmlTemplate(__DIR__.'/fixtures/testtemplatedir/testtemplate', __DIR__.'/fixtures/testtemplatedir/default');

		// Test assign absolute path.
		$TPL->assign_files(array('test1' => __DIR__.'/fixtures/test_template.tpl'));
		$this->assertArrayHasKey('test1', $TPL->files);
		$this->assertEquals(__DIR__.'/fixtures/test_template.tpl', $TPL->files['test1']);

		// Test nonexistent absolute path.
		try {
			$TPL->assign_files(array('test2' => __DIR__.'/fixtures/test_template_nonexistent.tpl'));
			$this->assertTrue(false, 'Should not get here');
		} catch (\Exception $e) {
			$this->assertTrue(true);
		}
		$this->assertFalse(isset($TPL->files['test2']));

		// Test assign relative path.
		$TPL->assign_files(array('test3' => 'index.tpl'));
		$this->assertArrayHasKey('test3', $TPL->files);
		$this->assertEquals(__DIR__.'/fixtures/testtemplatedir/testtemplate/index.tpl', $TPL->files['test3']);

		// Test fail to fallback.
		$TPL->assign_files(array('test4' => 'index2.tpl'));
		$this->assertArrayHasKey('test4', $TPL->files);
		$this->assertEquals(__DIR__.'/fixtures/testtemplatedir/default/index2.tpl', $TPL->files['test4']);

		// Test nonexistent relative path.
		try {
			$TPL->assign_files(array('test5' => 'index3.tpl'));
			$this->assertTrue(false, 'Should not get here');
		} catch (\Exception $e) {
			$this->assertTrue(true);
		}
		$this->assertFalse(isset($TPL->files['test5']));

		// Test assigning multiple.
		$TPL->assign_files(array(
			'test6' => __DIR__.'/fixtures/test_template.tpl',
			'test7' => 'index.tpl',
			'test8' => 'index2.tpl'
		));
		$this->assertArrayHasKey('test6', $TPL->files);
		$this->assertArrayHasKey('test7', $TPL->files);
		$this->assertArrayHasKey('test8', $TPL->files);
		$this->assertEquals(__DIR__.'/fixtures/test_template.tpl', $TPL->files['test6']);
		$this->assertEquals(__DIR__.'/fixtures/testtemplatedir/testtemplate/index.tpl', $TPL->files['test7']);
		$this->assertEquals(__DIR__.'/fixtures/testtemplatedir/default/index2.tpl', $TPL->files['test8']);
	}

	/**
	 * Test reset_sectvars method.
	 */
	public function test_reset_sectvars() {
		$sectvars = [
			'.one{1}.two' => [
				'three' => 'THREE',
				'four' => 'FOUR'
			],
			'.seven' => [
				'eight' => 'EIGHT',
				'nine' => 'NINE'
			],
		];
		$sectcount = [
			'.seven' => 1,
			'.one' => 1,
			'.one{1}.two' => 1
		];

		$TPL = new MockHtmlTemplate(__DIR__.'/fixtures');

		// Test resetting a single section.
		$TPL->sectvars = $sectvars;
		$TPL->sectcount = $sectcount;
		$TPL->rendered_tpl['test'] = 'Some rendered template text';
		$TPL->reset_sectvars('one');
		$this->assertEmpty($TPL->rendered_tpl);
		$this->assertArrayHasKey('.seven', $TPL->sectvars);
		$this->assertEquals($sectvars['.seven'], $TPL->sectvars['.seven']);
		$this->assertFalse(isset($TPL->sectvars['.one{1}.two']));

		// Test resetting all sections.
		$TPL->sectvars = $sectvars;
		$TPL->rendered_tpl['test'] = 'Some rendered template text';
		$TPL->reset_sectvars();
		$this->assertEmpty($TPL->rendered_tpl);
		$this->assertEmpty($TPL->sectvars);
	}

	/**
	 * Test reset_rootvars method.
	 */
	public function test_reset_rootvars() {
		$TPL = new MockHtmlTemplate(__DIR__.'/fixtures');
		$TPL->rootvars = array(
			'one' => 'ONE!',
			'two' => 'TWO!',
			'three' => 'THREE!',
		);
		$TPL->rendered_tpl['test'] = 'Some rendered template text';

		$TPL->reset_rootvars();
		$this->assertEmpty($TPL->rootvars);
		$this->assertEmpty($TPL->rendered_tpl);
	}

	/**
	 * Test clear_rendered_tpl_cache() function.
	 */
	public function test_clear_rendered_tpl_cache() {
		$TPL = new MockHtmlTemplate(__DIR__.'/fixtures');
		$TPL->rendered_tpl['test'] = 'Some rendered template text';
		$TPL->clear_rendered_tpl_cache();
		$this->assertEmpty($TPL->rendered_tpl);
	}

	/**
	 * Test import_tpl() function.
	 */
	public function test_import_tpl() {
		$TPL = new MockHtmlTemplate(__DIR__.'/fixtures');

		// Test successful import.
		$TPL->files['test'] = __DIR__.'/fixtures/test_template.tpl';
		$TPL->import_tpl('test');
		$this->assertArrayHasKey('test', $TPL->raw_tpl);
		$expected = file_get_contents(__DIR__.'/fixtures/test_template.tpl');
		$this->assertEquals($expected, $TPL->raw_tpl['test']);

		// Test exception thrown on bad import.
		$TPL->files['test2'] = __DIR__.'/fixtures/test_template_nonexistent.tpl';
		try {
			$TPL->import_tpl('test2');
			$this->assertTrue(false, 'Should not get here');
		} catch (\Exception $e) {
			$this->assertTrue(true);
		}
		$this->assertFalse(isset($TPL->raw_tpl['test2']));
	}

	/**
	 * Test rendering templates.
	 */
	public function test_render() {
		$TPL = new MockHtmlTemplate(__DIR__.'/fixtures');
		$TPL->files['test'] = __DIR__.'/fixtures/test_template.tpl';
		$TPL->raw_tpl['test'] = file_get_contents(__DIR__.'/fixtures/test_template.tpl');

		$TPL->assign_sect('one', array('var' => 'a'));
		$TPL->assign_sect('one.two_a');
		$TPL->assign_sect('one.two_b');
		$TPL->assign_sect('one.two_a.three_a', array('var' => 'b'));
		$TPL->assign_sect('one.two_a.three_a.four_a', array('var' => 'c'));
		$TPL->assign_sect('one.two_a.three_a', array('var' => 'd'));
		$TPL->assign_sect('one.two_a.three_b', array('var' => 'e'));
		$TPL->assign_sect('one.two_a.three_a', array('var' => 'f'));
		$TPL->assign_sect('one.two_a.three_b', array('var' => 'g'));
		$TPL->assign_sect('one.two_b.three_c', array('var' => 'h'));
		$TPL->assign_sect('one.two_a');
		$TPL->assign_sect('one.two_a.three_d', array('var' => 'i'));
		$TPL->assign_sect('one', array('var' => 'j'));
		$TPL->assign_sect('one.two_a');

		$result = $TPL->render('test');
		$expected = file_get_contents(__DIR__.'/fixtures/test_template_rendered.html');
		$this->assertEquals($expected, $result);
	}

	/**
	 * Test rendering templates.
	 */
	public function test_render_performance() {
		return true;
		$TPL = new MockHtmlTemplate(__DIR__.'/fixtures');
		$TPL->files['test'] = __DIR__.'/fixtures/test_template.tpl';
		$TPL->raw_tpl['test'] = file_get_contents(__DIR__.'/fixtures/test_template.tpl');

		$path = '';
		for($j=0; $j <= 10; $j++) {
			$cursect = 'sect'.$j;
			if ($j !== 0) {
				$path .= '.';
			}
			$path .= $cursect;
			$TPL->raw_tpl['test'] .= "\n".'<!-- BEGIN '.$cursect.' -->'."\n";
			$TPL->raw_tpl['test'] .= 'Depth test var: {'.$path.'.var}';
		}
		for($j=10; $j >= 0; $j--) {
			$cursect = 'sect'.$j;
			$TPL->raw_tpl['test'] .= "\n".'<!-- END '.$cursect.' -->'."\n";
		}

		$start = microtime(true);
		for($i = 0; $i < 1000; $i++) {
		$TPL->assign_sect('one', array('var' => 'a'));
		$TPL->assign_sect('one.two_a');
		$TPL->assign_sect('one.two_b');
		$TPL->assign_sect('one.two_a.three_a', array('var' => 'b'));
		$TPL->assign_sect('one.two_a.three_a.four_a', array('var' => 'c'));
		$TPL->assign_sect('one.two_a.three_a', array('var' => 'd'));
		$TPL->assign_sect('one.two_a.three_b', array('var' => 'e'));
		$TPL->assign_sect('one.two_a.three_a', array('var' => 'f'));
		$TPL->assign_sect('one.two_a.three_b', array('var' => 'g'));
		$TPL->assign_sect('one.two_b.three_c', array('var' => 'h'));
		$TPL->assign_sect('one.two_a');
		$TPL->assign_sect('one.two_a.three_d', array('var' => 'i'));
		$TPL->assign_sect('one', array('var' => 'j'));
		$TPL->assign_sect('one.two_a');

			$path = '';
			for($j=0; $j <= 10; $j++) {
				$cursect = 'sect'.$j;
				if ($j !== 0) {
					$path .= '.';
				}
				$path .= $cursect;
				$TPL->assign_sect($path, array('var' => '['.$j.']'));
			}

		}

		$result = $TPL->render('test');
		$end = microtime(true);
		echo $end-$start;
		return;
	}
}
