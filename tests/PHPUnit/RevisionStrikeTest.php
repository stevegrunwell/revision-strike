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
use RevisionStrikeSettings;

class RevisionStrikeTest extends TestCase {

	protected $testFiles = [
		'class-revision-strike.php',
		'class-settings.php',
	];

	public function test__construct() {
		$instance   = Mockery::mock( 'RevisionStrike' )->makePartial();
		$instance->shouldReceive( 'add_hooks' )->once();
		$statistics = new ReflectionProperty( $instance, 'statistics' );
		$statistics->setAccessible( true );

		$instance->__construct();

		$this->assertInstanceOf( 'RevisionStrikeSettings', $instance->settings );

		$stats = $statistics->getValue( $instance );
		$this->assertEquals( 0, $stats['count'] );
		$this->assertEquals( 0, $stats['deleted'] );
	}

	public function test_add_hooks() {
		$instance = new RevisionStrike;
		$settings = new RevisionStrikeSettings;
		$instance->settings = $settings;

		M::expectActionAdded( RevisionStrike::STRIKE_ACTION, array( $instance, 'strike' ) );
		M::expectActionAdded( 'admin_init', array( $settings, 'add_settings_section' ) );
		M::expectActionAdded( 'admin_menu', array( $settings, 'add_tools_page' ) );
		M::expectActionAdded( 'wp_delete_post_revision', array( $instance, 'count_deleted_revision' ) );

		$instance->add_hooks();
	}

	public function test_count_deleted_revision() {
		$instance = new RevisionStrike;
		$property = new ReflectionProperty( $instance, 'statistics' );
		$property->setAccessible( true );
		$property->setValue( $instance, array( 'deleted' => 5 ) );

		$instance->count_deleted_revision();

		$this->assertEquals( array( 'deleted' => 6 ), $property->getValue( $instance ) );
	}

	public function test_count_eligible_revisions() {
		global $wpdb;

		$instance = new RevisionStrike;

		$wpdb = Mockery::mock( '\WPDB' );
		$wpdb->shouldReceive( 'prepare' )
			->once()
			->with( Mockery::any(), 'post', Mockery::any() )
			->andReturnUsing( function ( $query ) {
				if ( 0 !== strpos( trim( $query ), 'SELECT COUNT(r.ID) FROM' ) ) {
					$this->fail( 'Results are not being limited to a count' );
				}
				return 'SQL STATEMENT';
			} );
		$wpdb->shouldReceive( 'get_var' )
			->once()
			->with( 'SQL STATEMENT' )
			->andReturn( 12 );
		$wpdb->posts = 'wp_posts';

		M::wpPassthruFunction( 'absint' );

		$this->assertEquals( 12, $instance->count_eligible_revisions( 30, 'post' ) );
		$wpdb = null;
	}

	public function test_get_stats() {
		$instance = new RevisionStrike;
		$value    = uniqid();
		$property = new ReflectionProperty( $instance, 'statistics' );
		$property->setAccessible( true );
		$property->setValue( $instance, $value );

		$this->assertEquals( $value, $instance->get_stats() );
	}

	public function test_strike() {
		$settings = Mockery::mock( 'RevisionStrikeSettings' )->makePartial();
		$settings->shouldReceive( 'get_option' )
			->once()
			->with( 'days' )
			->andReturn( 30 );
		$settings->shouldReceive( 'get_option' )
			->once()
			->with( 'limit' )
			->andReturn( 50 );

		$instance = Mockery::mock( 'RevisionStrike' )
			->shouldAllowMockingProtectedMethods()
			->makePartial();
		$instance->shouldReceive( 'get_revision_ids' )
			->once()
			->with( 14, 100, 'post' )
			->andReturn( array( 1, 2, 3 ) );
		$instance->settings = $settings;

		M::wpFunction( 'wp_parse_args', array(
			'times'  => 1,
			'args'   => array(
				array(
					'days'  => 14,
					'limit' => 100,
				),
				array(
					'days'      => 30,
					'limit'     => 50,
					'post_type' => null,
				),
			),
			'return' => array(
				'days'      => 14,
				'limit'     => 100,
				'post_type' => null,
			),
		) );

		M::wpFunction( 'wp_delete_post_revision', array(
			'times'  => 3
		) );

		$instance->strike( array( 'days' => 14, 'limit' => 100 ) );
	}

	public function test_strike_filters_post_types() {
		$settings = Mockery::mock( 'RevisionStrikeSettings' )->makePartial();
		$settings->shouldReceive( 'get_option' )
			->once()
			->with( 'days' )
			->andReturn( 30 );
		$settings->shouldReceive( 'get_option' )
			->once()
			->with( 'limit' )
			->andReturn( 50 );

		$instance = Mockery::mock( 'RevisionStrike' )
			->shouldAllowMockingProtectedMethods()
			->makePartial();
		$instance->shouldReceive( 'get_revision_ids' )
			->once()
			->with( 30, 50, 'POST_TYPE' )
			->andReturn( array() );
		$instance->settings = $settings;

		M::onFilter( 'revisionstrike_post_types' )
			->with( 'post' )
			->reply( 'POST_TYPE' );

		M::wpPassthruFunction( 'wp_parse_args', array(
			'times'  => 1,
		) );

		$instance->strike( array( 'days' => 30, 'limit' => 50, 'post_type' => null, ) );
	}

	public function test_get_revision_ids() {
		global $wpdb;

		$instance = new RevisionStrike;
		$method   = new ReflectionMethod( $instance, 'get_revision_ids' );
		$method->setAccessible( true );
		$property = new ReflectionProperty( $instance, 'statistics' );
		$property->setAccessible( true );
		$property->setValue( $instance, array( 'count' => 0 ) );

		$wpdb = Mockery::mock( '\WPDB' );
		$wpdb->shouldReceive( 'prepare' )
			->once()
			->with( Mockery::any(), 'post', Mockery::any(), 25 )
			->andReturnUsing( function ( $query ) {
				if ( false === strpos( $query, 'ORDER BY p.post_date ASC' ) ) {
					$this->fail( 'Revisions are not being ordered from oldest to newest' );
				}
				return 'SQL STATEMENT';
			} );
		$wpdb->shouldReceive( 'get_col' )
			->once()
			->with( 'SQL STATEMENT' )
			->andReturn( array( 1, 2, 3 ) );
		$wpdb->posts = 'wp_posts';

		M::wpPassthruFunction( 'absint' );

		$result = $method->invoke( $instance, 90, 25, 'post' );
		$wpdb   = null;

		$this->assertEquals( array( 1, 2, 3 ), $result );

		$stats = $property->getValue( $instance );
		$this->assertEquals( 3, $stats['count'], 'The "count" statistic is not being updated' );
	}

	public function test_get_revision_ids_with_multiple_post_types() {
		global $wpdb;

		$instance = new RevisionStrike;
		$method   = new ReflectionMethod( $instance, 'get_revision_ids' );
		$method->setAccessible( true );

		$wpdb = Mockery::mock( '\WPDB' );
		$wpdb->shouldReceive( 'prepare' )
			->once()
			->with( Mockery::any(), "post', 'page", Mockery::any(), 50 )
			->andReturn( 'SQL STATEMENT' );
		$wpdb->shouldReceive( 'get_col' )
			->once()
			->with( 'SQL STATEMENT' )
			->andReturn( array( 1, 2, 3 ) );
		$wpdb->posts = 'wp_posts';

		M::wpPassthruFunction( 'absint' );

		$result = $method->invoke( $instance, 30, 50, 'post,page' );
		$wpdb   = null;
	}

	public function test_get_revision_ids_returns_early_with_empty_post_types() {
		$instance = new RevisionStrike;
		$method   = new ReflectionMethod( $instance, 'get_revision_ids' );
		$method->setAccessible( true );

		$this->assertEquals( array(), $method->invoke( $instance, 30, 50, '' ) );
	}

}