<?php
/**
 * Tests for the plugin's bootstrapping.
 *
 * @package Revision Strike
 * @author Steve Grunwell
 */

namespace Grunwell\RevisionStrike;

use WP_Mock as M;
use RevisionStrike;

class PluginBootstrapTest extends TestCase {

	protected $testFiles = [
		'../revision-strike.php',
	];

	public function setUp() {
		M::passthruFunction( 'register_activation_hook' );
		M::passthruFunction( 'register_deactivation_hook' );

		parent::setUp();
	}

	public function test_revisionstrike_init() {
		M::userFunction( 'load_plugin_textdomain', array(
			'times'  => 1,
			'args'   => array( 'revision-strike', false, '/path/to/plugin/languages' ),
		) );

		M::userFunction( 'plugin_basename', array(
			'times'  => 1,
			'return' => '/path/to/plugin/file.php',
		) );

		revisionstrike_init();
	}

	public function test_revisionstrike_register_cron() {
		M::userFunction( 'wp_next_scheduled', array(
			'times'  => 1,
			'args'   => array( RevisionStrike::STRIKE_ACTION ),
			'return' => false,
		) );

		M::userFunction( 'wp_schedule_event', array(
			'times'  => 1,
			'args'   => array( M\Functions::type( 'int' ), 'daily', RevisionStrike::STRIKE_ACTION ),
		) );

		\revisionstrike_register_cron();
	}

	public function test_revisionstrike_deregister_cron() {
		M::userFunction( 'wp_clear_scheduled_hook', array(
			'times'  => 1,
			'args'   => array( RevisionStrike::STRIKE_ACTION ),
		) );

		\revisionstrike_deregister_cron();
	}
}