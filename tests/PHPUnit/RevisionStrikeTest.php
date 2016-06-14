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

	public function test_get_defaults() {
		$instance = new RevisionStrike;
		$value    = uniqid();
		$property = new ReflectionProperty( $instance, 'defaults' );
		$property->setAccessible( true );
		$property->setValue( $instance, $value );

		$this->assertEquals( $value, $instance->get_defaults() );
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
		$settings->shouldReceive( 'get_option' )
			->once()
			->with( 'keep' )
			->andReturn( 0 );
		$settings->shouldReceive( 'get_option' )
			->once()
			->with( 'post_type' )
			->andReturn( 'post' );

		$instance = Mockery::mock( 'RevisionStrike' )
			->shouldAllowMockingProtectedMethods()
			->makePartial();
		$instance->shouldReceive( 'get_revision_ids' )
			->times( 2 )
			->with( 14, 50, 'post', 0 )
			->andReturn( array( 1, 2, 3 ) );
		$instance->settings = $settings;

		M::wpFunction( 'wp_parse_args', array(
			'times'  => 1,
			'args'   => array(
				array(
					'days'  => 14,
					'limit' => 100,
					'keep'  => 0,
				),
				array(
					'days'      => 30,
					'limit'     => 50,
					'keep'      => 0,
					'post_type' => 'post',
				),
			),
			'return' => array(
				'days'      => 14,
				'limit'     => 100,
				'keep'      => 0,
				'post_type' => 'post',
			),
		) );

		M::wpFunction( 'wp_delete_post_revision', array(
			'times'  => 6,
		) );

		$instance->strike( array( 'days' => 14, 'limit' => 100, 'keep' => 0 ) );
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
		$settings->shouldReceive( 'get_option' )
			->once()
			->with( 'keep' )
			->andReturn( 0 );
		$settings->shouldReceive( 'get_option' )
			->once()
			->with( 'post_type' )
			->andReturn( 'post' );

		$instance = Mockery::mock( 'RevisionStrike' )
			->shouldAllowMockingProtectedMethods()
			->makePartial();
		$instance->shouldReceive( 'get_revision_ids' )
			->once()
			->with( 30, 50, 'POST_TYPE', 0 )
			->andReturn( array() );
		$instance->settings = $settings;

		M::onFilter( 'revisionstrike_post_types' )
			->with( 'post' )
			->reply( 'POST_TYPE' );

		M::wpPassthruFunction( 'wp_parse_args', array(
			'times'  => 1,
		) );

		$instance->strike( array( 'days' => 30, 'limit' => 50, 'post_type' => null, 'keep' => 0, ) );
	}

	public function test_strike_batches_results() {
		$settings = Mockery::mock( 'RevisionStrikeSettings' )->makePartial();
		$settings->shouldReceive( 'get_option' )
			->once()
			->with( 'days' )
			->andReturn( 30 );
		$settings->shouldReceive( 'get_option' )
			->once()
			->with( 'limit' )
			->andReturn( 50 );
		$settings->shouldReceive( 'get_option' )
			->once()
			->with( 'keep' )
			->andReturn( 0 );
		$settings->shouldReceive( 'get_option' )
			->once()
			->with( 'post_type' )
			->andReturn( 'post' );

		$instance = Mockery::mock( 'RevisionStrike' )
			->shouldAllowMockingProtectedMethods()
			->makePartial();
		$instance->shouldReceive( 'get_revision_ids' )
			->times( 2 )
			->with( 14, 50, 'post', 0 )
			->andReturn(
				array_fill( 0, 50, 'key' ),
				array( 'key' )
			);
		$instance->settings = $settings;

		M::wpFunction( 'wp_parse_args', array(
			'times'  => 1,
			'return' => array(
				'days'      => 14,
				'limit'     => 51,
				'post_type' => null,
				'keep'      => 0,
			),
		) );

		M::wpFunction( 'wp_delete_post_revision', array(
			'times'  => 51,
			'args'   => 'key',
		) );

		$instance->strike( array( 'days' => 14, 'limit' => 51 ) );
	}

	public function test_get_revision_ids() {
		global $wpdb;

		$instance = new RevisionStrike;
		$method   = new ReflectionMethod( $instance, 'get_revision_ids' );
		$method->setAccessible( true );

		$scrub_posts_list_method   = new ReflectionMethod( $instance, 'scrub_posts_list' );
		$scrub_posts_list_method->setAccessible( true );

		$property = new ReflectionProperty( $instance, 'statistics' );
		$property->setAccessible( true );
		$property->setValue( $instance, array( 'count' => 0 ) );

		$wpdb = Mockery::mock( '\WPDB' );
		$wpdb->shouldReceive( 'prepare' )
			->once()
			->with( Mockery::any(), 'post', Mockery::any() )
			->andReturnUsing( function ( $query ) {
				if ( false === strpos( $query, 'ORDER BY p.post_date ASC' ) ) {
					$this->fail( 'Revisions are not being ordered from oldest to newest' );
				}
				return 'SQL STATEMENT';
			} );


		$posts_and_revision_objects = $this->mock_query_post_and_revision_ids_results();

		$wpdb->shouldReceive( 'get_results' )
			->once()
			->with( 'SQL STATEMENT' )
			->andReturn( $posts_and_revision_objects );
		$wpdb->posts = 'wp_posts';

		// scrub the query results into array of post IDs and revisions sorted by dates
		$scrubed_posts_list = $scrub_posts_list_method->invoke( $instance, $posts_and_revision_objects, 0 );

		// revision IDs for the post ID 1
		// scrub_posts_list() calls wp_list_pluck
		M::wpFunction( 'wp_list_pluck', array(
			'args' => array(
				$scrubed_posts_list["1"],
				'revision_id',
				),
			'times' => 1,
			'return' => array( 12, 10, 11 ),
			)
		);

		// revision IDs for the post ID 2
		M::wpFunction( 'wp_list_pluck', array(
			'args' => array(
				$scrubed_posts_list["2"],
				'revision_id',
				),
			'times' => 1,
			'return' => array( 13, 14 ),
			)
		);

		// revisions for the post ID 3
		M::wpFunction( 'wp_list_pluck', array(
			'args' => array(
				$scrubed_posts_list["3"],
				'revision_id',
				),
			'times' => 1,
			'return' => array( 16, 15, 17, 18 ),
			)
		);


		M::wpPassthruFunction( 'absint' );

		$result = $method->invoke( $instance, 90, 25, 'post', 0 );
		$wpdb   = null;

		$this->assertEquals( array( 12, 10, 11, 13, 14, 16, 15, 17, 18 ), $result );

		$stats = $property->getValue( $instance );
		$this->assertEquals( 9, $stats['count'], 'The "count" statistic is not being updated' );
	}

	public function test_get_revision_ids_with_multiple_post_types() {
		global $wpdb;

		$instance = new RevisionStrike;
		$method   = new ReflectionMethod( $instance, 'get_revision_ids' );
		$method->setAccessible( true );

		$scrub_posts_list_method   = new ReflectionMethod( $instance, 'scrub_posts_list' );
		$scrub_posts_list_method->setAccessible( true );

		$wpdb = Mockery::mock( '\WPDB' );
		$wpdb->shouldReceive( 'prepare' )
			->once()
			->with( Mockery::any(), "post', 'page", Mockery::any() )
			->andReturn( 'SQL STATEMENT' );

		$posts_and_revision_objects = $this->mock_query_post_and_revision_ids_results();

		$wpdb->shouldReceive( 'get_results' )
			->once()
			->with( 'SQL STATEMENT' )
			->andReturn( $posts_and_revision_objects );
		$wpdb->posts = 'wp_posts';

		M::wpPassthruFunction( 'absint' );

		// scrub the query results into array of post IDs and revisions sorted by dates
		$scrubed_posts_list = $scrub_posts_list_method->invoke( $instance, $posts_and_revision_objects, 0 );

		// revision IDs for the post ID 1
		// scrub_posts_list() calls wp_list_pluck
		M::wpFunction( 'wp_list_pluck', array(
			'args' => array(
				$scrubed_posts_list["1"],
				'revision_id',
				),
			'times' => 1,
			'return' => array( 12, 10, 11 ),
			)
		);

		// revision IDs for the post ID 2
		M::wpFunction( 'wp_list_pluck', array(
			'args' => array(
				$scrubed_posts_list["2"],
				'revision_id',
				),
			'times' => 1,
			'return' => array( 13, 14 ),
			)
		);

		// revisions for the post ID 3
		M::wpFunction( 'wp_list_pluck', array(
			'args' => array(
				$scrubed_posts_list["3"],
				'revision_id',
				),
			'times' => 1,
			'return' => array( 16, 15, 17, 18 ),
			)
		);

		$result = $method->invoke( $instance, 30, 50, 'post,page', 0 );
		$wpdb   = null;
	}

	public function test_get_revision_ids_returns_early_with_empty_post_types() {
		$instance = new RevisionStrike;
		$method   = new ReflectionMethod( $instance, 'get_revision_ids' );
		$method->setAccessible( true );

		$this->assertEquals( array(), $method->invoke( $instance, 30, 50, '' ) );
	}

	/**
	 * Creates array of mock query results from the wpdb SELECT query
	 *
	 * @return array
	 */
	private function mock_query_post_and_revision_ids_results() {
		/*
			get_results is running:
			SELECT r.ID as revision_id, r.post_date as revision_date, p.ID as post_id
		 */
		$posts_and_revision_objects = array();

		// post id 1 with three revisions
		$posts_and_revision_objects[] = $this->mock_query_result( 10, '2016-04-01', 1 );
		$posts_and_revision_objects[] = $this->mock_query_result( 11, '2016-04-10', 1 );
		$posts_and_revision_objects[] = $this->mock_query_result( 12, '2016-03-01', 1 );

		// post id 2 with two revisions
		$posts_and_revision_objects[] = $this->mock_query_result( 13, '2016-01-01', 2 );
		$posts_and_revision_objects[] = $this->mock_query_result( 14, '2016-02-10', 2 );

		// post id 3 with four revisions
		$posts_and_revision_objects[] = $this->mock_query_result( 15, '2016-03-01', 3 );
		$posts_and_revision_objects[] = $this->mock_query_result( 16, '2016-02-10', 3 );
		$posts_and_revision_objects[] = $this->mock_query_result( 17, '2016-05-01', 3 );
		$posts_and_revision_objects[] = $this->mock_query_result( 18, '2016-05-07', 3 );

		return $posts_and_revision_objects;
	}

	/**
	 * Creates mock stdClass based on the SELECT query
	 *
	 * @return object
	 */
	function mock_query_result( $revision_id, $revision_date, $post_id ) {
		$result = new \stdClass();
		$result->revision_id   = $revision_id;
		$result->revision_date = $revision_date;
		$result->post_id       = $post_id;
		return $result;
	}

}