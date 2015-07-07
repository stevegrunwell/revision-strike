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

		M::expectAction( RevisionStrike::STRIKE_ACTION );

		$cli->clean( array(), array() );
	}

}