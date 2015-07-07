<?php
/**
 * WP-CLI commands for Revision Strike.
 *
 * @package Revision Strike
 * @author Steve Grunwell
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Remove revisions on old posts to clean up the database.
 */
class RevisionStrikeCLI extends WP_CLI {

	/**
	 * Remove old post revisions.
	 *
	 * ## EXAMPLES
	 *
	 *   wp revisionstrike clean
	 */
	public function clean( $args, $assoc_args ) {
		do_action( RevisionStrike::STRIKE_ACTION );
	}

}

WP_CLI::add_command( 'revisionstrike', 'RevisionStrikeCLI' );