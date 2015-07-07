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
	 * ## OPTIONS
	 *
	 * [--days=<days>]
	 * : Remove revisions on posts published at least <days> days ago.
	 *
	 * ## EXAMPLES
	 *
	 *   wp revisionstrike clean
	 *   wp revisionstrike clean --days=45
	 */
	public function clean( $args, $assoc_args ) {
		$days = isset( $assoc_args['days'] ) ? absint( $assoc_args['days'] ) : false;

		do_action( RevisionStrike::STRIKE_ACTION, $days );
	}

}

WP_CLI::add_command( 'revision-strike', 'RevisionStrikeCLI' );