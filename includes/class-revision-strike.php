<?php
/**
 * Primary plugin functionality.
 *
 * @package Revision Strike
 * @author Steve Grunwell
 */

/**
 * Class that hooks into WordPress, determines revision IDs, and strikes them from the database.
 */
class RevisionStrike {

	/**
	 * The plugin settings.
	 * @var RevisionStrikeSettings $settings
	 */
	public $settings;

	/**
	 * Information about Revision Strike's current state.
	 * @var array $statistics
	 */
	protected $statistics;

	/**
	 * The batch size when striking revisions.
	 */
	const BATCH_SIZE = 50;

	/**
	 * The action called to trigger the clean-up process.
	 */
	const STRIKE_ACTION = 'revisionstrike_strike_old_revisions';

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->settings   = new RevisionStrikeSettings( $this );
		$this->statistics = array(
			'count'   => 0, // Number of revision IDs found.
			'deleted' => 0, // Number of revisions deleted.
		);

		$this->add_hooks();
	}

	/**
	 * Register hooks within WordPress.
	 */
	public function add_hooks() {
		add_action( self::STRIKE_ACTION, array( $this, 'strike' ) );
		add_action( 'admin_init', array( $this->settings, 'add_settings_section' ) );
		add_action( 'admin_menu', array( $this->settings, 'add_tools_page' ) );
		add_action( 'wp_delete_post_revision', array( $this, 'count_deleted_revision' ) );
	}

	/**
	 * Increment $this->statistics['deleted'] by one every time a post revision is removed.
	 */
	public function count_deleted_revision() {
		$this->statistics['deleted']++;
	}

	/**
	 * Return the current statistics for this RevisionStrike instance.
	 *
	 * The statistics array contains the following keys:
	 * - count: The number of revision IDs found, up to the limit that was passed to strike().
	 * - deleted: The number of revisions that have been deleted. This number should always be <= the
	 *            value of "count".
	 *
	 * @return array An array of statistics.
	 */
	public function get_stats() {
		return $this->statistics;
	}

	/**
	 * Clean up ("strike") post revisions for posts that have been published for at least $days days.
	 *
	 * @param array $args {
	 *   Optional. An array of arguments.
	 *
	 *   @type int    $days      The number of days old a post must be in order for its revisions to
	 *                           be struck. Default is 30.
	 *   @type int    $limit     The maximum number of posts to delete in a single pass (when run as
	 *                           cron, per day). Default is 50.
	 *   @type string $post_type A comma-separated list of post types for which the revisions should
	 *                           be purged. By default, only revisions for posts will be purged.
	 * }
	 */
	public function strike( $args = array() ) {
		$default_args = array(
			'days'      => $this->settings->get_option( 'days', 30 ),
			'limit'     => $this->settings->get_option( 'limit', 50 ),
			'post_type' => null,
		);
		$args         = wp_parse_args( $args, $default_args );

		if ( null === $args['post_type'] ) {

			/**
			 * Set the default post type(s) for which revisions should be struck.
			 *
			 * @param string $post_type A comma-separated list of post types.
			 */
			$args['post_type'] = apply_filters( 'revisionstrike_post_types', 'post' );
		}

		// Calculate the number of batches to run.
		$limit       = self::BATCH_SIZE >= $args['limit'] ? $args['limit'] : self::BATCH_SIZE;
		$batch_count = ceil( $args['limit'] / $limit );

		for ( $i = 0; $i < $batch_count; $i++ ) {
			$revision_ids = $this->get_revision_ids( $args['days'], $limit, $args['post_type'] );

			if ( ! empty( $revision_ids ) ) {
				foreach ( $revision_ids as $revision_id ) {
					wp_delete_post_revision( $revision_id );
				}
			}
		}

	}

	/**
	 * Find revisions eligible to be removed from the database.
	 *
	 * @global $wpdb
	 *
	 * @param int   $days      The number of days since a post's publish date that must pass before
	 *                         we can purge the post revisions.
	 * @param int   $limit     The maximum number of revision IDs to retrieve.
	 * @param array $post_type The post types for which revisions should be located.
	 *
	 * @return array An array of post IDs (unless 'fields' is manipulated in $args).
	 */
	protected function get_revision_ids( $days, $limit, $post_type ) {
		global $wpdb;

		// Return early if we don't have any eligible post types.
		if ( ! $post_type ) {
			return array();
		}

		$post_type    = array_map( 'trim', explode( ',', $post_type ) );
		$revision_ids = $wpdb->get_col( $wpdb->prepare(
			"
			SELECT r.ID FROM $wpdb->posts r
			LEFT JOIN $wpdb->posts p ON r.post_parent = p.ID
			WHERE r.post_type = 'revision' AND p.post_type IN ('%s') AND p.post_date < %s
			ORDER BY p.post_date ASC
			LIMIT %d
			",
			implode( "', '", $post_type ),
			date( 'Y-m-d', time() - ( absint( $days ) * DAY_IN_SECONDS ) ),
			absint( $limit )
		) );

		$this->statistics['count'] = count( $revision_ids );

		return array_map( 'absint', $revision_ids );
	}

}
