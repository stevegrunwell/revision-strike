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
use RevisionStrikeCLI;

class CLITest extends TestCase {

	protected $testFiles = [
		'class-revision-strike.php',
		'class-revision-strike-cli.php',
	];

	public function test_clean() {
		$cli = new RevisionStrikeCLI;

		M::expectAction( RevisionStrike::STRIKE_ACTION, false );

		$cli->clean( array(), array() );
	}

	public function test_clean_with_days_argument() {
		$cli = new RevisionStrikeCLI;

		M::wpPassthruFunction( 'absint', array(
			'times' => 1,
			'args'  => array( 7 ),
		) );

		M::expectAction( RevisionStrike::STRIKE_ACTION, 7 );

		$cli->clean( array(), array( 'days' => 7 ) );
	}

}