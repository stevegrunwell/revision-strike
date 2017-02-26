<?php
/**
 * Tests for the plugin's bootstrapping.
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
use RevisionStrikeCLI;
use WP_CLI;

class CLITest extends TestCase {

	protected $testFiles = [
		'class-revision-strike.php',
		'class-revision-strike-cli.php',
		'class-settings.php',
	];

	public function tearDown() {
		parent::tearDown();

		\WP_CLI::tearDown();
	}

	public function test_clean() {
		$wp_cli = WP_CLI::getInstance();
		$wp_cli->shouldReceive( '_line' )->once();
		$wp_cli->shouldReceive( '_success' )->once();

		$instance = Mockery::mock( 'RevisionStrike' )->makePartial();
		$instance->shouldReceive( 'strike' )->once();
		$instance->shouldReceive( 'get_stats' )
			->once()
			->andReturn( array( 'deleted' => 50, ) );

		$cli = Mockery::mock( 'RevisionStrikeCLI' )
			->shouldAllowMockingProtectedMethods()
			->makePartial();
		$cli->shouldReceive( 'get_instance' )
			->once()
			->andReturn( $instance );

		M::passthruFunction( '_n' );

		$cli->clean( array(), array() );
	}

	public function test_clean_with_days_argument() {
		$instance = Mockery::mock( 'RevisionStrike' )->makePartial();
		$instance->shouldReceive( 'strike' )
			->once()
			->with( array( 'days' => 7, ) );
		$instance->shouldReceive( 'get_stats' )
			->once()
			->andReturn( array( 'deleted' => 0, ) );

		$cli = Mockery::mock( 'RevisionStrikeCLI' )
			->shouldAllowMockingProtectedMethods()
			->makePartial();
		$cli->shouldReceive( 'get_instance' )
			->once()
			->andReturn( $instance );

		M::passthruFunction( 'esc_html__' );

		$cli->clean( array(), array( 'days' => 7 ) );
	}

	public function test_clean_with_limit_argument() {
		$instance = Mockery::mock( 'RevisionStrike' )->makePartial();
		$instance->shouldReceive( 'strike' )
			->once()
			->with( array( 'limit' => 100, ) );
		$instance->shouldReceive( 'get_stats' )
			->once()
			->andReturn( array( 'deleted' => 0, ) );

		$cli = Mockery::mock( 'RevisionStrikeCLI' )
			->shouldAllowMockingProtectedMethods()
			->makePartial();
		$cli->shouldReceive( 'get_instance' )
			->once()
			->andReturn( $instance );

		M::passthruFunction( 'esc_html__' );

		$cli->clean( array(), array( 'limit' => 100 ) );
	}

	public function test_clean_with_post_type_argument() {
		$instance = Mockery::mock( 'RevisionStrike' )->makePartial();
		$instance->shouldReceive( 'strike' )
			->once()
			->with( array( 'post_type' => 'page', ) );
		$instance->shouldReceive( 'get_stats' )
			->once()
			->andReturn( array( 'deleted' => 0 ) );

		$cli = Mockery::mock( 'RevisionStrikeCLI' )
			->shouldAllowMockingProtectedMethods()
			->makePartial();
		$cli->shouldReceive( 'get_instance' )
			->once()
			->andReturn( $instance );

		M::passthruFunction( 'esc_html__' );

		$cli->clean( array(), array( 'post_type' => 'page' ) );
	}

	public function test_clean_with_verbose_argument() {
		$instance = Mockery::mock( 'RevisionStrike' )->makePartial();
		$instance->shouldReceive( 'strike' )->once();
		$instance->shouldReceive( 'get_stats' )
			->once()
			->andReturn( array( 'deleted' => 0, ) );

		$cli = Mockery::mock( 'RevisionStrikeCLI' )
			->shouldAllowMockingProtectedMethods()
			->makePartial();
		$cli->shouldReceive( 'get_instance' )
			->once()
			->andReturn( $instance );

		M::passthruFunction( 'esc_html__' );

		M::expectActionAdded(
			'wp_delete_post_revision',
			array( $cli, 'log_deleted_revision' ),
			10,
			2
		);

		$cli->clean( array(), array( 'verbose' => true ) );
	}

	public function test_clean_reporting() {
		$instance = Mockery::mock( 'RevisionStrike' )->makePartial();
		$instance->shouldReceive( 'strike' )->once();
		$instance->shouldReceive( 'get_stats' )
			->once()
			->andReturn( array( 'deleted' => 50, ) );

		$cli = Mockery::mock( 'RevisionStrikeCLI' )
			->shouldAllowMockingProtectedMethods()
			->makePartial();
		$cli->shouldReceive( 'get_instance' )
			->once()
			->andReturn( $instance );

		M::passthruFunction( 'esc_html__', array(
			'times' => 0,
		) );
		M::passthruFunction( '_n' );

		$cli->clean( array(), array() );
	}

	public function test_clean_all() {
		$wp_cli = WP_CLI::getInstance();

		$instance = Mockery::mock( 'RevisionStrike' )->makePartial();
		$instance->shouldReceive( 'count_eligible_revisions' )
			->once()
			->with( 45, 'post,page' )
			->andReturn( 777 );

		$settings = Mockery::mock( 'RevisionStrikeSettings' )->makePartial();
		$settings->shouldReceive( 'get_option' )
			->never()
			->with( 'days' );
		$settings->shouldReceive( 'get_option' )
			->once()
			->with( 'post_type' )
			->andReturn( 'post,page' );
		$instance->settings = $settings;

		$cli = Mockery::mock( 'RevisionStrikeCLI' )
			->shouldAllowMockingProtectedMethods()
			->makePartial();
		$cli->shouldReceive( 'get_instance' )
			->once()
			->andReturn( $instance );
		$cli->shouldReceive( 'clean' )
			->once()
			->with( array(), array( 'days' => 45, 'limit' => 777 ) );

		$cli->clean_all( array(), array( 'days' => 45, ) );
	}

	public function test_log_deleted_revision() {
		$rs_cli = new RevisionStrikeCLI;
		$wp_cli = WP_CLI::getInstance();
		$wp_cli->shouldReceive( '_log' )->once();

		M::passthruFunction( 'esc_html__' );

		$rs_cli->log_deleted_revision( 4, new \stdClass );
	}

	public function test_get_instance() {
		$instance = new RevisionStrikeCLI;
		$method   = new ReflectionMethod( $instance, 'get_instance' );
		$method->setAccessible( true );
		$property = new ReflectionProperty( $instance, 'instance' );
		$property->setAccessible( true );

		$this->assertNull( $property->getValue( $instance ) );

		$method->invoke( $instance );

		$this->assertInstanceOf( 'RevisionStrike', $property->getValue( $instance ) );
	}

}