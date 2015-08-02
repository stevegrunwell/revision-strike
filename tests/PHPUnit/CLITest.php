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
use ReflectionProperty;
use RevisionStrike;
use RevisionStrikeCLI;

class CLITest extends TestCase {

	protected $testFiles = [
		'class-revision-strike.php',
		'class-revision-strike-cli.php',
	];

	public function test_clean() {
		$instance = Mockery::mock( 'RevisionStrike' )->makePartial();
		$instance->shouldReceive( 'strike' )->once();

		$cli = Mockery::mock( 'RevisionStrikeCLI' )
			->shouldAllowMockingProtectedMethods()
			->makePartial();
		$cli->shouldReceive( 'get_instance' )
			->once()
			->andReturn( $instance );

		$progress = new ReflectionProperty( $cli, 'progress' );
		$progress->setAccessible( true );
		$progress->setValue( $cli, array( 'success' => 5 ) );

		M::wpPassthruFunction( '_n' );

		M::expectActionAdded( 'wp_delete_post_revision', array( $cli, 'count_deleted_revision' ) );

		$cli->clean( array(), array() );
	}

	public function test_clean_with_days_argument() {
		$instance = Mockery::mock( 'RevisionStrike' )->makePartial();
		$instance->shouldReceive( 'strike' )
			->once()
			->with( array( 'days' => 7, 'post_types' => null, ) );

		$cli = Mockery::mock( 'RevisionStrikeCLI' )
			->shouldAllowMockingProtectedMethods()
			->makePartial();
		$cli->shouldReceive( 'get_instance' )
			->once()
			->andReturn( $instance );

		M::wpPassthruFunction( 'esc_html__' );

		$cli->clean( array(), array( 'days' => 7 ) );
	}

	public function test_clean_with_verbose_argument() {
		$instance = Mockery::mock( 'RevisionStrike' )->makePartial();
		$instance->shouldReceive( 'strike' )->once();

		$cli = Mockery::mock( 'RevisionStrikeCLI' )
			->shouldAllowMockingProtectedMethods()
			->makePartial();
		$cli->shouldReceive( 'get_instance' )
			->once()
			->andReturn( $instance );

		M::wpPassthruFunction( 'esc_html__' );

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

		$cli = Mockery::mock( 'RevisionStrikeCLI' )
			->shouldAllowMockingProtectedMethods()
			->makePartial();
		$cli->shouldReceive( 'get_instance' )
			->once()
			->andReturn( $instance );

		$property = new ReflectionProperty( $cli, 'progress' );
		$property->setAccessible( true );
		$property->setValue( $cli, 5 );

		M::wpPassthruFunction( 'esc_html__', array(
			'times' => 0,
		) );
		M::wpPassthruFunction( '_n' );

		$cli->clean( array(), array() );
	}

}