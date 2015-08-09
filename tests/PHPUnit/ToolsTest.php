<?php
/**
 * Tests for the Tools > Revision Strike page.
 *
 * @package Revision Strike
 * @author Steve Grunwell
 */

namespace Grunwell\RevisionStrike;

use WP_Mock as M;
use Mockery;
use ReflectionMethod;
use ReflectionProperty;
use RevisionStrike;

class ToolsTest extends TestCase {

	protected $tools_file;

	public function setup() {
		parent::setup();

		$this->tools_file = PROJECT . 'tools.php';

		M::wpPassthruFunction( '__' );
		M::wpPassthruFunction( '_x' );
		M::wpPassthruFunction( 'absint' );
		M::wpPassthruFunction( 'esc_html_e' );
	}

	public function test_default_load() {
		M::wpFunction( 'wp_nonce_url', array(
			'times'  => 1,
			'args'   => array( 'tools.php?page=revision-strike', 'revision-strike', 'nonce' ),
		) );

		M::wpFunction( 'submit_button', array(
			'times'  => 1,
		) );

		$defaults = array(
			'days'  => 15,
			'limit' => 50,
		);

		ob_start();
		include $this->tools_file;
		$results = ob_get_contents();
		ob_end_clean();

		$this->assertContains(
			'<input name="days" type="number" class="small-text" value="15" />',
			$results,
			'Days field is not being populated'
		);
		$this->assertContains(
			'<input name="limit" type="number" class="small-text" value="50" />',
			$results,
			'Limit field is not being populated'
		);
	}

	public function test_load_when_defaults_array_is_undefined() {
		M::wpFunction( 'wp_die', array(
			'times'  => 1,
		) );

		ob_start();
		include $this->tools_file;
		ob_end_clean();
	}

	public function test_load_with_valid_nonce() {
		$defaults     = array(
			'days'  => 30,
			'limit' => 50,
		);
		$_GET['nonce'] = 'MYNONCE';
		$_POST         = $defaults;

		M::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'MYNONCE', 'revision-strike' ),
			'return' => true,
		) );

		ob_start();
		include $this->tools_file;
		ob_end_clean();

		unset( $_GET['nonce'] );
		unset( $_POST );
	}

}