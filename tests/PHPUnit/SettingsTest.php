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

	public function test__construct() {
		$revisionstrike = new \stdClass;
		$instance       = Mockery::mock( 'RevisionStrikeSettings', array( $revisionstrike ) );
		$property       = new ReflectionProperty( $instance, 'instance' );
		$property->setAccessible( true );

		$this->assertEquals( $revisionstrike, $property->getValue( $instance ) );
	}

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

	public function test_add_tools_page() {
		$instance = Mockery::mock( 'RevisionStrikeSettings' )->makePartial();

		M::wpFunction( 'add_management_page', array(
			'times'  => 1,
			'args'   => array(
				'Revision Strike',
				'Revision Strike',
				'edit_others_posts',
				'revision-strike',
				array( $instance, 'tools_page' ),
			),
		) );

		M::wpPassthruFunction( '__' );
		M::wpPassthruFunction( '_x' );
		M::wpPassthruFunction( 'esc_html_e' );

		$instance->add_tools_page();
	}

	public function test_tools_page() {
		$instance = Mockery::mock( 'RevisionStrikeSettings' )->makePartial();
		$instance->shouldReceive( 'get_option' )
			->once()
			->with( 'days', 30 )
			->andReturn( 15 );

		M::wpPassthruFunction( 'wp_die' );

		$tools_template = PROJECT . 'tools.php';
		$this->assertNotContains( $tools_template, get_included_files() );

		// We don't care what's *in* the file, just that it's loaded
		ob_start();
		$instance->tools_page();
		ob_end_clean();

		$this->assertContains( $tools_template, get_included_files() );
	}

	public function test_days_field() {
		$instance = Mockery::mock( 'RevisionStrikeSettings' )->makePartial();
		$instance->shouldReceive( 'get_option' )
			->with( 'days', 30 )
			->andReturn( 30 );

		M::wpPassthruFunction( 'absint', array(
			'times' => 1,
			'args'  => array( 30 ),
		) );

		M::wpPassthruFunction( 'esc_html__', array(
			'times' => 1,
		) );

		M::wpPassthruFunction( 'esc_html_x', array(
			'times' => 1,
		) );

		ob_start();
		$instance->days_field();
		$result = ob_get_contents();
		ob_end_clean();

		$this->assertContains( 'name="revision-strike[days]"', $result );
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
			'days'  => 16,
			'limit' => 50,
		);

		M::wpPassthruFunction( 'absint', array(
			'times'  => 1,
			'args'   => array( 16 ),
		) );

		M::wpPassthruFunction( 'absint', array(
			'times'  => 1,
			'args'   => array( 50 ),
		) );

		$this->assertEquals(
			array(
				'days'  => 16,
				'limit' => 50,
			),
			$instance->sanitize_settings( $input )
		);
	}

	public function test_sanitize_settings_doesnt_permit_limit_0() {
		$instance = Mockery::mock( 'RevisionStrikeSettings' )->makePartial();
		$input    = array(
			'limit' => 0,
		);

		M::wpPassthruFunction( 'absint' );

		$this->assertEquals( array( 'limit' => 50 ), $instance->sanitize_settings( $input ) );
	}
}