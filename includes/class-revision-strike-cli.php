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
	 * @var array $progress An array that holds increments as needed by commands.
	 */
	protected $progress = array(
		'success' => 0,
	);

	/**
	 * Remove old post revisions.
	 *
	 * ## OPTIONS
	 *
	 * [--days=<days>]
	 * : Remove revisions on posts published at least <days> days ago.
	 *
	 * [--verbose]
	 * : Enable verbose logging of deleted revisions.
	 *
	 * ## EXAMPLES
	 *
	 *   wp revisionstrike clean
	 *   wp revisionstrike clean --days=45
	 *
	 * @synopsis [--days=<days>] [--verbose]
	 */
	public function clean( $args, $assoc_args ) {
		add_action( 'wp_delete_post_revision', array( $this, 'count_deleted_revision' ) );

		if ( isset( $assoc_args['verbose'] ) ) {
			add_action( 'wp_delete_post_revision', array( $this, 'log_deleted_revision' ), 10, 2 );
		}

		$days = isset( $assoc_args['days'] ) ? absint( $assoc_args['days'] ) : false;

		do_action( RevisionStrike::STRIKE_ACTION, $days );

		WP_CLI::line();
		if ( 0 === $this->progress['success'] ) {
			return WP_CLI::success(
				esc_html__( 'No errors occurred, but no post revisions were removed.', 'revision-strike' )
			);

		} else {
			return WP_CLI::success( _n(
				'One post revision was deleted successfully',
				'%d post revisions were deleted successfully',
				$this->progress['success'],
				'revision-strike'
			) );
		}
	}

	/**
	 * Increment $this->progress['success'] by one.
	 */
	public function count_deleted_revision() {
		$this->progress['success']++;
	}

	/**
	 * Log a deleted post revision.
	 *
	 * @param int          $revision_id Post revision ID.
	 * @param object|array $revision    Post revision object or array.
	 */
	public function log_deleted_revision( $revision_id, $revision ) {
		WP_CLI::log( sprintf(
			esc_html__( 'Revision ID %d has been deleted.', 'revision-strike' ),
			$revision_id
		) );
	}

}

WP_CLI::add_command( 'revision-strike', 'RevisionStrikeCLI' );
