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

		$instance->add_hooks();
	}

	public function test_strike() {
		$instance = Mockery::mock( 'RevisionStrike' )
			->shouldAllowMockingProtectedMethods()
			->makePartial();
		$instance->shouldReceive( 'get_revision_expiration_time' )
			->once()
			->andReturn( 60 );
		$instance->shouldReceive( 'get_revision_ids' )
			->once()
			->andReturn( array( 1, 2, 3 ) );

		M::wpFunction( 'wp_delete_post_revision', array(
			'times'  => 3
		) );

		$instance->strike();
	}

	public function test_get_revision_expiration_time() {
		$instance = new RevisionStrike;

		$method = new ReflectionMethod( $instance, 'get_revision_expiration_time' );
		$method->setAccessible( true );

		$property = new ReflectionProperty( $instance, 'revision_expiration_time' );
		$property->setAccessible( true );

		M::onFilter( 'revisionstrike_expiration_time' )
			->with( 60 * 60 * 24 * 30 ) // 30 days
			->reply( 60 );

		$this->assertEmpty( $property->getValue( $instance ) );
		$this->assertEquals( 60, $method->invoke( $instance ) );
		$this->assertEquals( 60, $property->getValue( $instance ) );
	}

	public function test_get_revision_expiration_time_uses_cached_value() {
		$instance = new RevisionStrike;

		$method = new ReflectionMethod( $instance, 'get_revision_expiration_time' );
		$method->setAccessible( true );

		$property = new ReflectionProperty( $instance, 'revision_expiration_time' );
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
			->with( Mockery::any(), '2015-01-01', 25 )
			->andReturn( 'SQL STATEMENT' );
		$wpdb->shouldReceive( 'get_results' )
			->once()
			->with( 'SQL STATEMENT' )
			->andReturn( array( 1, 2, 3 ) );
		$wpdb->posts = 'wp_posts';

		M::wpPassthruFunction( 'absint' );

		$result = $method->invoke( $instance, '2015-01-01', 25 );
		$wpdb   = null;

		$this->assertEquals( array( 1, 2, 3 ), $result );
	}

}