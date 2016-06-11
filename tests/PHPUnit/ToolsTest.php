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
		M::wpPassthruFunction( '_n' );
		M::wpPassthruFunction( '_x' );
		M::wpPassthruFunction( 'absint' );
		M::wpPassthruFunction( 'esc_attr' );
		M::wpPassthruFunction( 'esc_html' );
		M::wpPassthruFunction( 'esc_html__' );
		M::wpPassthruFunction( 'esc_html_e' );
		M::wpPassthruFunction( 'esc_url' );
	}

	public function test_default_load() {
		M::wpFunction( 'wp_nonce_url', array(
			'times'  => 1,
			'args'   => array( 'tools.php?page=revision-strike', 'revision-strike', 'nonce' ),
		) );

		M::wpFunction( 'submit_button', array(
			'times'  => 1,
		) );

		$instance = new \stdClass;
		$defaults = array(
			'days'  => 15,
			'limit' => 50,
			'keep'  => 0,
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
		$this->assertContains(
			'<input name="keep" type="number" class="small-text" value="0" />',
			$results,
			'Keep field is not being populated'
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
			'keep'  => 0,
		);
		$_GET     = array( 'nonce' => 'MYNONCE' );
		$_POST    = $defaults;
		$instance = Mockery::mock( 'RevisionStrike' )->makePartial();
		$instance->shouldReceive( 'strike' )
			->once()
			->with( $defaults );
		$instance->shouldReceive( 'get_stats' )
			->once()
			->andReturn( array( 'count' => 0 ) );

		M::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'MYNONCE', 'revision-strike' ),
			'return' => true,
		) );

		ob_start();
		include $this->tools_file;
		ob_end_clean();

		unset( $_GET, $_POST );
	}

	public function test_load_message_scenarios() {
		$defaults     = array(
			'days'  => 30,
			'limit' => 50,
			'keep'  => 0,
		);
		$_GET     = array( 'nonce' => true );
		$_POST    = $defaults;
		$instance = Mockery::mock( 'RevisionStrike' )->makePartial();
		$instance->shouldReceive( 'strike' )
			->with( $defaults );
		$instance->shouldReceive( 'get_stats' )
			->times( 3 )
			->andReturn(
				array( 'count' => 0 ), // No revisions found
				array( 'count' => 5, 'deleted' => 0 ), // Something went wrong
				array( 'count' => 5, 'deleted' => 5 ) // We're good
			);

		M::wpFunction( 'wp_verify_nonce', array(
			'return' => true,
		) );

		M::wpPassthruFunction( 'wp_nonce_url' );
		M::wpPassthruFunction( 'submit_button' );

		// Pass 1: no revisions found
		ob_start();
		include $this->tools_file;
		$result = ob_get_contents();
		ob_end_clean();
		$this->assertContains( '<div class="error"><p>', $result, 'No revisions found did not trigger an error message' );

		// Pass 2: revisions found, but nothing deleted
		ob_start();
		include $this->tools_file;
		$result = ob_get_contents();
		ob_end_clean();
		$this->assertContains( '<div class="error"><p>', $result, 'The user should be warned when revisions were found be we can\'t delete them' );

		// Pass 3: revisions deleted successfully
		ob_start();
		include $this->tools_file;
		$result = ob_get_contents();
		ob_end_clean();
		$this->assertContains( '<div class="updated"><p>', $result, 'Notify the user when revisions are deleted' );

		unset( $_GET, $_POST );
	}

}