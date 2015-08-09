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
	 * The current instance of the RevisionStrike class.
	 * @var RevisionStrike $instance
	 */
	protected $instance;

	/**
	 * Remove old post revisions.
	 *
	 * ## OPTIONS
	 *
	 * [--days=<days>]
	 * : Remove revisions on posts published at least <days> days ago.
	 *
	 * [--limit=<limit>]
	 * : The number of days a post should be published before its revisions are
	 * eligible to be struck.
	 *
	 * [--post_type=<post_type>]
	 * : One or more post types (comma-separated) for which revisions should be struck.
	 *
	 * [--verbose]
	 * : Enable verbose logging of deleted revisions.
	 *
	 * ## EXAMPLES
	 *
	 *   wp revision-strike clean
	 *   wp revision-strike clean --days=45
	 *   wp revision-strike clean --limit=75
	 *   wp revision-strike clean --post_type=post,page
	 *
	 * @synopsis [--days=<days>] [--limit=<limit>] [--post_type=<post_type>] [--verbose]
	 *
	 * @param array $args       A numeric array of position-based arguments.
	 * @param array $assoc_args An associative array of key-based arguments.
	 */
	public function clean( $args, $assoc_args ) {
		if ( isset( $assoc_args['verbose'] ) ) {
			add_action( 'wp_delete_post_revision', array( $this, 'log_deleted_revision' ), 10, 2 );
		}

		$instance = $this->get_instance();
		$args     = array();

		foreach ( array( 'days', 'limit', 'post_type' ) as $arg ) {
			if ( isset( $assoc_args[ $arg ] ) ) {
				$args[ $arg ] = $assoc_args[ $arg ];
			}
		}

		$instance->strike( $args );

		WP_CLI::line();

		$stats = $instance->get_stats();
		if ( 0 === $stats['deleted'] ) {
			return WP_CLI::success(
				esc_html__( 'No errors occurred, but no post revisions were removed.', 'revision-strike' )
			);

		} else {
			return WP_CLI::success( sprintf( _n(
				'One post revision was deleted successfully',
				'%d post revisions were deleted successfully',
				$stats['deleted'],
				'revision-strike'
			), $stats['deleted'] ) );
		}
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

	/**
	 * Get the current RevisionStrike instance.
	 *
	 * @return RevisionStrike The current instance in $this->instance.
	 */
	protected function get_instance() {
		if ( null === $this->instance ) {
			$this->instance = new RevisionStrike;
		}
		return $this->instance;
	}

}

WP_CLI::add_command( 'revision-strike', 'RevisionStrikeCLI' );
