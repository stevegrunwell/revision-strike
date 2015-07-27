<?php
/**
 * Tests for the plugin's settings.
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

class SettingsTest extends TestCase {

	protected $testFiles = [
		'class-settings.php',
	];

	public function test_add_settings_section() {
		$instance = Mockery::mock( 'RevisionStrikeSettings' )->makePartial();

		M::wpFunction( 'register_setting', array(
			'times'  => 1,
			'args'   => array( 'writing', 'revision-strike', array( $instance, 'sanitize_settings' ) ),
		) );

		M::wpFunction( 'add_settings_section', array(
			'times'  => 1,
			'args'   => array(
				'revision-strike',
				'*',
				null,
				'writing'
			),
		) );

		M::wpFunction( 'add_settings_field', array(
			'times'  => 1,
			'args'   => array(
				'revision-strike-days',
				'*',
				array( $instance, 'days_field' ),
				'writing',
				'revision-strike'
			),
		) );

		M::wpPassthruFunction( '__' );

		$instance->add_settings_section();
	}

	public function test_get_option() {
		$instance = Mockery::mock( 'RevisionStrikeSettings' )->makePartial();

		M::wpFunction( 'get_option', array(
			'times'  => 1,
			'args'   => array( 'revision-strike', array() ),
			'return' => array(
				'days' => 10,
			),
		) );

		$this->assertEquals( 10, $instance->get_option( 'days' ) );
	}

	public function test_get_option_uses_defaults() {
		$instance = Mockery::mock( 'RevisionStrikeSettings' )->makePartial();

		M::wpFunction( 'get_option', array(
			'times'  => 1,
			'args'   => array( 'revision-strike', array() ),
			'return' => array(
				'NOTdays' => 10,
			),
		) );

		$this->assertEquals( 15, $instance->get_option( 'days', 15 ) );
	}

	public function test_get_option_caches_value() {
		$instance = Mockery::mock( 'RevisionStrikeSettings' )->makePartial();
		$options  = array(
			'foo' => 'bar',
		);

		$property = new ReflectionProperty( $instance, 'options' );
		$property->setAccessible( true );
		$property->setValue( $instance, $options );

		M::wpFunction( 'get_option', array(
			'times'  => 0,
		) );

		$this->assertEquals( 'bar', $instance->get_option( 'foo' ) );
	}

	public function test_sanitize_settings() {
		$instance = Mockery::mock( 'RevisionStrikeSettings' )->makePartial();
		$input    = array(
			'days' => 16,
		);

		M::wpPassthruFunction( 'absint', array(
			'times'  => 1,
			'args'   => array( 16 ),
		) );

		$this->assertEquals(
			array(
				'days' => 16,
			),
			$instance->sanitize_settings( $input )
		);
	}
}