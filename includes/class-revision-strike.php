<?php
/**
 * Primary plugin functionality.
 *
 * @package Revision Strike
 * @author  Steve Grunwell
 */

/**
 * Class that hooks into WordPress, determines revision IDs, and strikes them from the database.
 */
class RevisionStrike {

	/**
	 * The plugin settings.
	 *
	 * @var RevisionStrikeSettings $settings
	 */
	public $settings;

	/**
	 * The canonical source for default settings.
	 *
	 * @var array $defaults
	 */
	protected $defaults;

	/**
	 * Information about Revision Strike's current state.
	 *
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
		$this->defaults   = array(
			'days'      => 30,
			'limit'     => 50,
			'post_type' => 'post',
		);
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
	 * Count the number of post revisions that are eligible to be struck for the given threshold and
	 * post types.
	 *
	 * @global $wpdb
	 *
	 * @param int    $days      The number of days old a post must be in order for its revisions to
	 *                          be struck.
	 * @param string $post_type Post types for which revisions should be struck.
	 * @return int The number of matching post revisions in the database.
	 */
	public function count_eligible_revisions( $days, $post_type ) {
		global $wpdb;

		$post_type_in_string = $this->get_slug_in_string( $post_type );
		$count     = $wpdb->get_var( $wpdb->prepare(
			"
			SELECT COUNT(r.ID) FROM $wpdb->posts r
			LEFT JOIN $wpdb->posts p ON r.post_parent = p.ID
			WHERE r.post_type = 'revision' AND p.post_type IN ($post_type_in_string) AND p.post_date < %s
			",
			date( 'Y-m-d', time() - ( absint( $days ) * DAY_IN_SECONDS ) )
		) );

		return absint( $count );
	}

	/**
	 * Return the current default Revision Strike settings.
	 *
	 * @return array An array of default settings.
	 */
	public function get_defaults() {
		return $this->defaults;
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
			'days'      => $this->settings->get_option( 'days' ),
			'limit'     => $this->settings->get_option( 'limit' ),
			'post_type' => $this->settings->get_option( 'post_type' ),
		);
		$args         = wp_parse_args( $args, $default_args );

		/**
		 * Set the post type(s) for which revisions should be struck.
		 *
		 * @param string $post_type A comma-separated list of post types.
		 */
		$args['post_type'] = apply_filters( 'revisionstrike_post_types', $args['post_type'] );

		// Calculate the number of batches to run.
		$limit       = self::BATCH_SIZE >= $args['limit'] ? $args['limit'] : self::BATCH_SIZE;
		$batch_count = ceil( $args['limit'] / $limit );

		for ( $i = 0; $i < $batch_count; $i++ ) {
			$revision_ids = $this->get_revision_ids( $args['days'], $limit, $args['post_type'] );

			if ( ! empty( $revision_ids ) ) {
				array_map( 'wp_delete_post_revision', $revision_ids );
			}
		}

	}

	/**
	 * Converts a comma-delimited list of slugs into a string usable
	 * as with an SQL IN statement.
	 *
	 * @param  string $post_type Comma-delimited list of slugs (post,page).
	 * @return string List of slugs for IN statement ('post','page').
	 */
	protected function get_slug_in_string( $slugs ) {

		/*
		This mimics the functionality in core for building IN strings.
		From post.php:
		$post_types = esc_sql( $post_types );
		$post_type_in_string = "'" . implode( "','", $post_types ) . "'";
		$sql = "
			SELECT ID, post_name, post_parent, post_type
			FROM $wpdb->posts
			WHERE post_name IN ($in_string)
			AND post_type IN ($post_type_in_string)
		";
		 */

		// Split the list into an array.
		$slugs = explode( ',', $slugs );

		// Run esc_sql on the array of slugs
		$slugs = esc_sql( $slugs );

		// Return a string usable in an IN statement
		return "'" . implode( "','", $slugs ) . "'";
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

		$post_type_in_string = $this->get_slug_in_string( $post_type );
		$revision_ids = $wpdb->get_col( $wpdb->prepare(
			"
			SELECT r.ID FROM $wpdb->posts r
			LEFT JOIN $wpdb->posts p ON r.post_parent = p.ID
			WHERE r.post_type = 'revision' AND p.post_type IN ($post_type_in_string) AND p.post_date < %s
			ORDER BY p.post_date ASC
			LIMIT %d
			",
			date( 'Y-m-d', time() - ( absint( $days ) * DAY_IN_SECONDS ) ),
			absint( $limit )
		) );


		/**
		 * Filter the list of eligible revision IDs.
		 *
		 * @since 0.3.0
		 *
		 * @param array $revision_ids Revision IDs to be struck.
		 * @param int   $days      The number of days since a post's publish date that must pass before
		 *                         we can purge the post revisions.
		 * @param int   $limit     The maximum number of revision IDs to retrieve.
		 * @param array $post_type The post types for which revisions should be located.
		 */
		$revision_ids = apply_filters( 'revisionstrike_get_revision_ids', $revision_ids, $days, $limit, $post_type );

		$this->statistics['count'] = count( $revision_ids );

		return $revision_ids;
	}
}
