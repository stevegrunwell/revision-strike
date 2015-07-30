<?php
/**
 * Tests for the plugin's RevisionStrike class.
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

class RevisionStrikeTest extends TestCase {

	protected $testFiles = [
		'class-revision-strike.php',
	];

	public function test__construct() {
		$instance = Mockery::mock( 'RevisionStrike' )->makePartial();
		$instance->shouldReceive( 'add_hooks' )->once();

		$instance->__construct();
	}

	public function test_add_hooks() {
		$instance = new RevisionStrike;
		M::expectActionAdded( RevisionStrike::STRIKE_ACTION, array( $instance, 'strike' ) );
		M::expectActionAdded( 'admin_init', array( $instance, 'settings' ) );

		$instance->add_hooks();
	}

	public function test_settings() {
		$this->markTestSkipped( 'Need a better instantiation method' );
		$instance = Mockery::mock( 'RevisionStrike' )->makePartial();

		$instance->settings();

		$this->assertInstanceOf( 'RevisionStrikeSettings', $instance->settings );
	}

	public function test_strike() {
		$instance = Mockery::mock( 'RevisionStrike' )
			->shouldAllowMockingProtectedMethods()
			->makePartial();
		$instance->shouldReceive( 'get_revision_expiration_threshold' )
			->once()
			->andReturn( 30 );
		$instance->shouldReceive( 'get_revision_ids' )
			->once()
			->with( 30, 50 )
			->andReturn( array( 1, 2, 3 ) );

		M::wpFunction( 'wp_delete_post_revision', array(
			'times'  => 3
		) );

		$instance->strike();
	}

	public function test_strike_with_days_argument() {
		$instance = Mockery::mock( 'RevisionStrike' )
			->shouldAllowMockingProtectedMethods()
			->makePartial();
		$instance->shouldReceive( 'get_revision_expiration_threshold' )
			->never();
		$instance->shouldReceive( 'get_revision_ids' )
			->once()
			->with( 90, 50 )
			->andReturn( array( 1, 2, 3 ) );

		M::wpFunction( 'wp_delete_post_revision', array(
			'times'  => 3
		) );

		$instance->strike( 90 );
	}

	public function test_get_revision_expiration_threshold() {
		$instance = new RevisionStrike;

		$method = new ReflectionMethod( $instance, 'get_revision_expiration_threshold' );
		$method->setAccessible( true );

		$property = new ReflectionProperty( $instance, 'expiration_threshold' );
		$property->setAccessible( true );

		$this->assertEmpty( $property->getValue( $instance ) );
		$this->assertEquals( 30, $method->invoke( $instance ) );
		$this->assertEquals( 30, $property->getValue( $instance ) );
	}

	public function test_get_revision_expiration_threshold_uses_cached_value() {
		$instance = new RevisionStrike;

		$method = new ReflectionMethod( $instance, 'get_revision_expiration_threshold' );
		$method->setAccessible( true );

		$property = new ReflectionProperty( $instance, 'expiration_threshold' );
		$property->setAccessible( true );
		$property->setValue( $instance, 12345 );

		$this->assertEquals( 12345, $method->invoke( $instance ) );
	}

	public function test_get_revision_ids() {
		global $wpdb;

		$instance = new RevisionStrike;
		$method   = new ReflectionMethod( $instance, 'get_revision_ids' );
		$method->setAccessible( true );

		$wpdb = Mockery::mock( '\WPDB' );
		$wpdb->shouldReceive( 'prepare' )
			->once()
			->with( Mockery::any(), "post', 'page", Mockery::any(), 25 )
			->andReturn( 'SQL STATEMENT' );
		$wpdb->shouldReceive( 'get_col' )
			->once()
			->with( 'SQL STATEMENT' )
			->andReturn( array( 1, 2, 3 ) );
		$wpdb->posts = 'wp_posts';

		M::onFilter( 'revisionstrike_post_types' )
			->with( array( 'post' ) )
			->reply( array( 'post', 'page' ) );

		M::wpPassthruFunction( 'absint' );

		$result = $method->invoke( $instance, 90, 25 );
		$wpdb   = null;

		$this->assertEquals( array( 1, 2, 3 ), $result );
	}

	public function test_get_revision_ids_ensures_post_types_are_set() {
		$instance = new RevisionStrike;
		$method   = new ReflectionMethod( $instance, 'get_revision_ids' );
		$method->setAccessible( true );

		M::onFilter( 'revisionstrike_post_types' )
			->with( array( 'post' ) )
			->reply( false );

		M::wpPassthruFunction( 'absint' );

		$result = $method->invoke( $instance, 90, 25 );
		$this->assertEquals( array(), $result );
	}

}