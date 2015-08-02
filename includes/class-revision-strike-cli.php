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
	 * @var RevisionStrike $instance The current instance of the RevisionStrike class.
	 */
	protected $instance;

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
	 */
	public function clean( $args, $assoc_args ) {
		add_action( 'wp_delete_post_revision', array( $this, 'count_deleted_revision' ) );

		if ( isset( $assoc_args['verbose'] ) ) {
			add_action( 'wp_delete_post_revision', array( $this, 'log_deleted_revision' ), 10, 2 );
		}

		$instance = $this->get_instance();
		$args     = array(
			'days'      => isset( $assoc_args['days'] ) ? $assoc_args['days'] : null,
			'limit'     => isset( $assoc_args['limit'] ) ? $assoc_args['limit'] : null,
			'post_type' => isset( $assoc_args['post_type'] ) ? $assoc_args['post_type'] : null,
		);

		$instance->strike( $args );

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
